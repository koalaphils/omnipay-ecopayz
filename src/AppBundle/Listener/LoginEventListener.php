<?php

namespace AppBundle\Listener;

use DbBundle\Entity\AuditRevisionLog;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Class LoginEventListener.
 *
 * @author Paolo Abendanio <cesar.abendanio@zmtsys.com>
 */
class LoginEventListener
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();

        $userAdmin = $this->getUserRepository()->find($user->getId());
        $userAdmin->setPreference('lastLoginIP', $event->getRequest()->getClientIp());
        $this->getUserRepository()->save($userAdmin);

        $session = $this->container->get('session');
        //save to sessions
        $sessionManager = $this->container->get('session.session_manager');
        $sessionToken = generate_code(16, false, 'luds');
        $session->set('session_token', $sessionToken);

        $sessionManager->create([
            'sessionId' => $session->getId(),
            'key' => $sessionToken,
            'user' => $user,
        ]);

        if (!is_cli()) {
            $this->container->get('audit.manager')->audit(
                $user,
                AuditRevisionLog::OPERATION_LOGIN,
                AuditRevisionLog::CATEGORY_LOGIN
            );
        }
    }

    protected function getUserRepository(): \DbBundle\Repository\UserRepository
    {
        return $this->container->get('doctrine')->getRepository('DbBundle:User');
    }
}
