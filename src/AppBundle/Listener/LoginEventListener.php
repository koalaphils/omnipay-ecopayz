<?php

namespace AppBundle\Listener;

use DbBundle\Entity\AuditRevisionLog;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use DateTimeImmutable;
use MemberBundle\Events as MemberEvents;
use AppBundle\Event\GenericEntityEvent;

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
        $ips = array_merge(
            explode(',', str_replace(' ', '', $event->getRequest()->server->get('HTTP_X_FORWARDED_FOR'))),
            explode(',', str_replace(' ', '', $event->getRequest()->server->get('REMOTE_ADDR'))),
            $event->getRequest()->getClientIps()
        );
        $ips = array_filter($ips, function($value){return !is_null($value) && trim($value) !== '';});
        $userAdmin->setPreference('lastLoginIP', implode(',', array_unique($ips)));
        $userAdmin->setPreference('userAgent', $event->getRequest()->server->get('HTTP_USER_AGENT'));
        $userAdmin->setPreference('lastLoginDate', new DateTimeImmutable());
        $this->getUserRepository()->save($userAdmin);

        $session = $this->container->get('session');
        //save to sessions
        $sessionManager = $this->container->get('session.session_manager');
        $sessionToken = generate_code(16, false, 'luds');
        $session->set('session_token', $sessionToken);

        $currentSessions = $user->getSessions();
        $dispatcher = $this->container->get('event_dispatcher');
        $hadActiveSession = false;
        foreach($currentSessions as $oldSession){
            $hadActiveSession = true;
            $sessionManager->remove($oldSession);
        }
        /*if($hadActiveSession){
            $dispatcher->dispatch(MemberEvents::EVENT_ADMIN_EVICT_SESSION, new GenericEntityEvent($user));
        }*/

        $sessionManager->create([
            'sessionId' => $session->getId(),
            'key' => $sessionToken,
            'user' => $user,
        ]);

        if (!is_cli()) {
            dump('dispatch');
            $this->container->get('audit.manager')->audit(
                $user,
                AuditRevisionLog::OPERATION_LOGIN,
                AuditRevisionLog::CATEGORY_LOGIN
            );
            $dispatcher->dispatch(MemberEvents::EVENT_ADMIN_USER_LOGIN, new GenericEntityEvent($user));
        }
    }

    protected function getUserRepository(): \DbBundle\Repository\UserRepository
    {
        return $this->container->get('doctrine')->getRepository('DbBundle:User');
    }
}
