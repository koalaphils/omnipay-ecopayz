<?php

namespace AppBundle\Helper;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use DbBundle\Entity\User;

/**
 * Helper for notification to SupermoonBet MS.
 *
 * Class AppBundle\Helper\NotificationHelper
 */
class NotificationHelper
{
    use ContainerAwareTrait;

    const CHANNEL_MESSAGE = 'message';

    /**
     * @param int         $identifier
     * @param string      $channel
     * @param User|string $user
     * @param string      $action
     * @param array       $otherOptions
     */
    public function push($identifier, $channel, $user, $action = 'new', $otherOptions = [])
    {
        $context = new \ZMQContext();
        $socket = $context->getSocket(\ZMQ::SOCKET_PUSH);
        $socket->connect($this->container->getParameter('gos_websocket.pusher.ms.url'));

        $topic = sprintf('%s/%s', $this->container->getParameter('gos_websocket.channel'), $user instanceof User ? $user->getChannelId() : $user);
        $session = $this->getSession();
        $data = [
            '_identifier' => $identifier,
            '_channel' => $channel,
            '_action' => $action,
            '_credentials' => [
                '_tokenKey' => $session->getId(),
                '_token' => $session->get('session_token'),
            ],
        ] + $otherOptions;

        $socket->send(json_encode(['topic' => $topic, 'data' => json_encode($data)]));
    }

    /**
     * @param User                            $user
     * @param unknown                         $channel
     * @param Ticket|Transaction|Notice|Bonus $entity
     * @param string                          $action
     */
    public function updateCounter(User $user, $channel, $entity, $action = 'new')
    {
        $normalizeEntity = \ZendeskBundle\Adapter\ZendeskAdapter::create($entity);
        $counters = $user->getPreference('counters');
        $count = 0;

        if (self::CHANNEL_MESSAGE == $channel) {
            $count = $counters[self::CHANNEL_MESSAGE];

            if ($action === 'new') {
                $count += 1;
            } else {
                $isRead = false;

                foreach ($normalizeEntity->getCustomFields() as $value) {
                    if ($this->container->getParameter('zendesk_ticket_is_read_id') === $value->id) {
                        $isRead = 'no' === $value->value ? false : true;
                    }
                }

                if ($isRead) {
                    $count += 1;
                }
            }
        }

        $this->getUserManager()->updateCounter($user, $channel, $count);
    }

    /**
     * @return Symfony\Component\HttpFoundation\Session\Session
     */
    private function getSession()
    {
        return $this->container->get('session');
    }

    /**
     * @return \UserBundle\Manager\UserManager
     */
    private function getUserManager()
    {
        return $this->container->get('user.manager');
    }
}
