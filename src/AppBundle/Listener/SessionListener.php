<?php

namespace AppBundle\Listener;

use SessionBundle\Manager\SessionManager;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class AppBundle\Listener\SessionListener.
 */
class SessionListener
{
    private $expirationTime;
    private $sessionManager;

    /**
     * @param int             $expirationTime
     * @param SecurityManager $sessionManager
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($expirationTime, SessionManager $sessionManager)
    {
        if (!is_integer($expirationTime)) {
            throw new \InvalidArgumentException(
                sprintf('$expirationTime is expected be of type integer, %s given', gettype($expirationTime))
            );
        }

        $this->expirationTime = $expirationTime;
        $this->sessionManager = $sessionManager;
    }

    /**
     * @param GetResponseEvent $event
     *
     * @return Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->isMasterRequest()) {
            if ($this->expirationTime > 0) {
                $request = $event->getRequest();
                $session = $request->getSession();
                $session->start();
                $metaData = $session->getMetadataBag();
                $timeDifference = time() - $metaData->getLastUsed();

                if ($timeDifference > $this->expirationTime) {
                    $logoutPath = $this->sessionManager->logout();
                    $response = new RedirectResponse($logoutPath);

                    $event->setResponse($response);
                }
            }
        }
    }
}
