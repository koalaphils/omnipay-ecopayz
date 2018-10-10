<?php

namespace WebSocketBundle\Topic;

class NotificationTopic implements TopicInterface
{
    public function getName()
    {
        return 'notifications';
    }

    public function onPublish(\Thruway\Peer\Client $client, \Thruway\Event\MessageEvent $event)
    {
    }

    public function onSubscribe(\Thruway\Peer\Client $client, \Thruway\Event\MessageEvent $event)
    {
    }

    public function onUnSubscribe(\Thruway\Peer\Client $client, \Thruway\Event\MessageEvent $event)
    {
    }
}
