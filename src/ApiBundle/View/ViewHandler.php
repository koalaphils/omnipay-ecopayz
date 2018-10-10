<?php

/*
 * This file is part of the FOSRestBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiBundle\View;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;
use FOS\RestBundle\View\View;

/**
 * View may be used in controllers to build up a response in a format agnostic way
 * The View class takes care of encoding your data in json, xml, or renders a
 * template for html via the Serializer component.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Lukas K. Smith <smith@pooteeweet.org>
 */
class ViewHandler extends \FOS\RestBundle\View\ViewHandler
{
    private $urlGenerator;
    private $serializer;
    private $templating;
    private $requestStack;

    /**
     * Constructor.
     *
     * @param UrlGeneratorInterface $urlGenerator         The URL generator
     * @param Serializer            $serializer
     * @param EngineInterface       $templating           The configured templating engine
     * @param RequestStack          $requestStack         The request stack
     * @param array                 $formats              the supported formats as keys and if the given formats uses templating is denoted by a true value
     * @param int                   $failedValidationCode The HTTP response status code for a failed validation
     * @param int                   $emptyContentCode     HTTP response status code when the view data is null
     * @param bool                  $serializeNull        Whether or not to serialize null view data
     * @param array                 $forceRedirects       If to force a redirect for the given key format, with value being the status code to use
     * @param string                $defaultEngine        default engine (twig, php ..)
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        Serializer $serializer,
        EngineInterface $templating = null,
        RequestStack $requestStack,
        array $formats = null,
        $failedValidationCode = Response::HTTP_BAD_REQUEST,
        $emptyContentCode = Response::HTTP_NO_CONTENT,
        $serializeNull = false,
        array $forceRedirects = null,
        $defaultEngine = 'twig'
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->serializer = $serializer;
        $this->templating = $templating;
        $this->requestStack = $requestStack;
        $this->formats = (array) $formats;
        $this->failedValidationCode = $failedValidationCode;
        $this->emptyContentCode = $emptyContentCode;
        $this->serializeNull = $serializeNull;
        $this->forceRedirects = (array) $forceRedirects;
        $this->defaultEngine = $defaultEngine;
    }

    public function handle(View $view, Request $request = null)
    {
        if (null === $request) {
            $request = $this->requestStack->getCurrentRequest();
        }

        $format = $view->getFormat() ?: $request->getRequestFormat();

        if (!$this->supports($format)) {
            $msg = "Format '$format' not supported, handler must be implemented";
            throw new UnsupportedMediaTypeHttpException($msg);
        }

        if (isset($this->customHandlers[$format])) {
            return call_user_func($this->customHandlers[$format], $this, $view, $request, $format);
        }

        return $this->createResponse($view, $request, $format);
    }

    /**
     * Handles creation of a Response using either redirection or the templating/serializer service.
     *
     * @param View    $view
     * @param Request $request
     * @param string  $format
     *
     * @return Response
     */
    public function createResponse(View $view, Request $request, $format)
    {
        $route = $view->getRoute();

        $location = $route
            ? $this->urlGenerator->generate($route, (array) $view->getRouteParameters(), UrlGeneratorInterface::ABSOLUTE_URL)
            : $view->getLocation();

        if ($location) {
            return $this->createRedirectResponse($view, $location, $format);
        }

        $response = $this->initResponse($view, $format);

        if (!$response->headers->has('Content-Type')) {
            $mimeType = $request->attributes->get('media_type');
            if (null === $mimeType) {
                $mimeType = $request->getMimeType($format);
            }

            $response->headers->set('Content-Type', $mimeType);
        }

        return $response;
    }

    public function createRedirectResponse(View $view, $location, $format)
    {
        $content = null;
        if (($view->getStatusCode() === Response::HTTP_CREATED || $view->getStatusCode() === Response::HTTP_ACCEPTED) && $view->getData() !== null) {
            $response = $this->initResponse($view, $format);
        } else {
            $response = $view->getResponse();
            if ('html' === $format && isset($this->forceRedirects[$format])) {
                $redirect = new RedirectResponse($location);
                $content = $redirect->getContent();
                $response->setContent($content);
            }
        }

        $code = isset($this->forceRedirects[$format])
            ? $this->forceRedirects[$format] : $this->getStatusCode($view, $content);

        $response->setStatusCode($code);
        $response->headers->set('Location', $location);

        return $response;
    }

    /**
     * Gets a response HTTP status code from a View instance.
     *
     * By default it will return 200. However if there is a FormInterface stored for
     * the key 'form' in the View's data it will return the failed_validation
     * configuration if the form instance has errors.
     *
     * @param View  $view
     * @param mixed $content
     *
     * @return int HTTP status code
     */
    protected function getStatusCode(View $view, $content = null)
    {
        $form = $this->getFormFromView($view);

        $statusCode = $view->getStatusCode();

        if ($form && $form->isSubmitted() && !$form->isValid()) {
            if ($statusCode === null) {
                return $this->failedValidationCode;
            }
        }

        if (null !== $statusCode) {
            return $statusCode;
        }

        return null !== $content ? Response::HTTP_OK : $this->emptyContentCode;
    }

    private function initResponse(View $view, $format)
    {
        $content = null;
        if ($this->isFormatTemplating($format)) {
            $content = $this->renderTemplate($view, $format);
        } elseif ($this->serializeNull || null !== $view->getData()) {
            $data = $this->getDataFromView($view);

            if ($data instanceof FormInterface && $data->isSubmitted() && !$data->isValid()) {
                if ($view->getStatusCode() === null) {
                    $view->getContext()->setAttribute('status_code', $this->failedValidationCode);
                } else {
                    $view->getContext()->setAttribute('status_code', $view->getStatusCode());
                }
            }

            $context = $this->getSerializationContext($view);
            $context->setAttribute('template_data', $view->getTemplateData());

            $content = $this->serializer->serialize($data, $format, $context);
        }

        $response = $view->getResponse();
        $response->setStatusCode($this->getStatusCode($view, $content));

        if (null !== $content) {
            $response->setContent($content);
        }

        return $response;
    }

    /**
     * Returns the data from a view.
     *
     * @param View $view
     *
     * @return mixed|null
     */
    private function getDataFromView(View $view)
    {
        $form = $this->getFormFromView($view);

        if (false === $form) {
            return $view->getData();
        }

        return $form;
    }
}
