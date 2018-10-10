<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use JMS\Serializer\SerializationContext;

use AppBundle\Exceptions\FormValidationException;

abstract class AbstractController extends Controller
{
    abstract protected function getManager();

    protected function getContainer()
    {
        return $this->container;
    }

/** 
     * @throws FormValidationException if the form is invalid.
     */
    protected function validateForm(Form $form): void
    {   
        if (!$form->isValid()) {
            throw new FormValidationException($form);
        }
    }

    protected function isFormProcessable(Form $form): bool
    {
        $isSubmitted = $form->isSubmitted();

        if ($isSubmitted) {
            $this->validateForm($form);
        }

        return $isSubmitted;
    }

    /**
     * Get Router.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    protected function getRouter()
    {
        return $this->getContainer()->get('router');
    }

    /**
     * Get Session.
     *
     * @return \Symfony\Component\HttpFoundation\Session\Session
     */
    protected function getSession()
    {
        return $this->getContainer()->get('session');
    }

    /**
     * Get setting manager.
     *
     * @return \AppBundle\Manager\SettingManager
     */
    protected function getSettingManager()
    {
        return $this->getContainer()->get('app.setting_manager');
    }

    /**
     * Get translator.
     *
     * @return \Symfony\Component\Translation\DataCollectorTranslator
     */
    protected function getTranslator()
    {
        return $this->getContainer()->get('translator');
    }

    /**
     * Get Menu manager.
     *
     * @return \AppBundle\Manager\MenuManager
     */
    protected function getMenuManager()
    {
        return $this->getContainer()->get('app.menu_manager');
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getDoctrine()->getManager();
    }

    /**
     * Gets the repository for an entity class.
     *
     * @param string $entityName The name of the entity.
     *
     * @return \Doctrine\ORM\EntityRepository The repository class.
     */
    protected function getRepository($entityName)
    {
        return $this->getEntityManager()->getRepository($entityName);
    }

    /**
     * @return AppBundle\Helper\NotificationHelper
     */
    protected function getNotificationHelper()
    {
        return $this->getContainer()->get('app.notification_helper');
    }

    protected function transform($dataTransferClass, $data, $options = [], $returnResponse = false)
    {
        $dataTransfer = $this->get('app.component.data_transfer');
        $tranformed = $dataTransfer->transform($dataTransferClass, $data, $options);

        return $tranformed;
    }

    protected function getResponseTypeFromRequest(Request $request)
    {
        $contentType = $request->headers->get('Content-Type');
        $type = null;
        switch ($contentType) {
            case 'application/json':
                $type = 'json';
                break;
            case 'application/xml':
                $type = 'xml';
                break;
        }

        return $type;
    }

    protected function serialize($data, $args = [], $format = 'json')
    {
        $context = new \JMS\Serializer\SerializationContext();
        $context->setSerializeNull(true);
        if (array_has($args, 'groups')) {
            $context->setGroups(array_get($args, 'groups'));
        }

        return $this->getJmsSerializer()->serialize($data, $format, $context);
    }

    protected function jsonResponse($data, int $status = 200, array $headers = [], ?SerializationContext $context = null)
    {
        if ($this->container->has('jms_serializer')) {
            $json = $this->container->get('jms_serializer')->serialize($data, 'json', $context);

            return new JsonResponse($json, $status, $headers, true);
        }

        return new JsonResponse($data, $status, $headers);
    }


    protected function createSerializationContext($groups = [])
    {
        $context = new \JMS\Serializer\SerializationContext();
        $context->setSerializeNull(true);

        $context->setGroups($groups);

        return $context;
    }

    /**
     *
     * @return \JMS\Serializer\Serializer
     */
    protected function getJmsSerializer()
    {
        return $this->getContainer()->get('jms_serializer');
    }

    protected function response(Request $request, $data, $args, $status = 200)
    {
        $format = $request->get('_format', 'json');
        if ($format === null) {
            $accepts = explode(',', $request->headers->get('Accept'));
            foreach ($accepts as &$accept) {
                $accept = trim($accept);
                $format = $request->getFormat($accept);
                if ($format !== null) {
                    break;
                }
            }
        }
        $serialized = $this->serialize($data, $args, $format);
        $response = new Response($serialized, $status);
        $mimeType = $request->getMimeType($format);
        $response->headers->set('Content-Type', $mimeType);

        return $response;
    }

    protected function getWidgetManager(): \AppBundle\Manager\WidgetManager
    {
        return $this->getContainer()->get('app.widget_manager');
    }

    public function dispatchEvent($eventName, $event)
    {
        $dispatcher = $this->getContainer()->get('event_dispatcher');
        $dispatcher->dispatch($eventName, $event);
    }
}
