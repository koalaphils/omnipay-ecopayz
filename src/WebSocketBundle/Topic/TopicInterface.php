<?php

namespace WebSocketBundle\Topic;

use Thruway\Event\MessageEvent;
use Thruway\Peer\Client;

interface TopicInterface
{
    public function onSubscribe(Client $client, MessageEvent $event);

    public function onUnSubscribe(Client $client, MessageEvent $event);

    public function onPublish(Client $client, MessageEvent $event);

    public function getName();
}
