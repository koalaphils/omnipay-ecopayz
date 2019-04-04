<?php

namespace AppBundle\Listener;

use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Description of MaintenanceListener.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class PageListener
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;
    use \AppBundle\Traits\UserAwareTrait;

    private $defaultLocale;
    private $unInclude = [
        '_wdt',
        '_profiler_home',
        '_profiler_search',
        '_profiler_search_bar',
        '_profiler_purge',
        '_profiler_info',
        '_profiler_phpinfo',
        '_profiler_search_results',
        '_profiler',
        '_profiler_router',
        '_profiler_exception',
        '_profiler_exception_css',
        '_twig_error_test',
    ];

    public function __construct($defaultLocale = 'en')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->defaultLocale = $event->getRequest()->getLocale();
        if ($event->isMasterRequest()) {
            $masterRequest = $event->getRequest();

            $timezone = $masterRequest->get('timezone', $this->container->getParameter('timezone'));

            if ($masterRequest->headers->has('REFERER')) {
                $url = parse_url($masterRequest->headers->get('REFERER'));
                $queries = [];
                parse_str($url['query'] ?? '', $queries);
                if (array_has($queries, 'timezone')) {
                    $timezone = array_get($queries, 'timezone');
                }
            }
            date_default_timezone_set($timezone);
        }

        $debug = in_array($this->container->get('kernel')->getEnvironment(), ['test', 'dev']);
        if (!in_array($event->getRequest()->attributes->get('_route'), $this->unInclude) && !$debug) {
            $maintenance = $this->_getSettingManager()->getSetting('maintenance.enabled');

            if ($this->container->get('security.token_storage')->getToken()
                && $this->container->get('security.authorization_checker')->isGranted('ROLE_MAINTENANCE')
            ) {
                $maintenance = false;
            }

            if ($maintenance) {
                $engine = $this->container->get('templating');
                $content = $engine->render('AppBundle:Default:maintenance.html.twig');
                $event->setResponse(new Response($content, Response::HTTP_SERVICE_UNAVAILABLE));
                $event->stopPropagation();
            }
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!in_array($event->getRequest()->attributes->get('_route'), $this->unInclude)) {
            $cookie = new Cookie('_locale', $this->defaultLocale, time() + (60 * 60 * 24 * 30));
            $event->getResponse()->headers->removeCookie('_locale');
            $event->getResponse()->headers->setCookie($cookie);
        }
    }

    public function onKernelController()
    {
        if ($this->getUser() instanceof \DbBundle\Entity\User) {
            $key = $this->container->getParameter('jwt_key');
            $token = [
                'authid' => $this->getAuthId(),
                'exp' => time() + $this->container->getParameter('session.expiration_time'),
            ];
            $jwt = JWT::encode($token, $key);

            $twig = $this->container->get('twig');
            $twig->addGlobal('jwt', $jwt);

            $counter = $this->_getSettingManager()->getSetting('counter');
            $twig->addGlobal('counter', $counter);
            unset($counter);
        }
    }

    protected function getAuthId(): string
    {
        $user = $this->getUser();

        return json_encode([
            'username' => $user->getUsername(),
            'userid' => $user->getId(),
            'from' => 'backoffice'
        ]);
    }

    /**
     * Get User.
     *
     * @return type
     *
     * @throws \LogicException
     */
    public function getUser()
    {
        return $this->_getUser();
    }

    /**
     * Get Router.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    protected function getRouter()
    {
        return $this->container->get('router');
    }

    protected function getSecurityTokenStorage()
    {
        return $this->container->get('security.token_storage');
    }

    protected function hasSecurityTokenStorage()
    {
        return $this->container->has('security.token_storage');
    }

    /**
     * Get setting manager.
     *
     * @return \AppBundle\Manager\SettingManager
     */
    protected function _getSettingManager()
    {
        return $this->container->get('app.setting_manager');
    }
}
