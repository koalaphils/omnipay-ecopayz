<?php

namespace WebSocketBundle\Security;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Thruway\Event\MessageEvent;
use Thruway\Event\NewRealmEvent;
use Thruway\Module\RealmModuleInterface;
use Thruway\Module\RouterModuleClient;
use Thruway\Message\Message;

class AuthorizationManager extends RouterModuleClient implements RealmModuleInterface
{
    use ContainerAwareTrait;

    private $subscriptions = [];
    private $callRequestIndex = [];

    /**
     * Listen for Router events.
     * Required to add the authorization module to the realm.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'new_realm' => ['handleNewRealm', 10],
        ];
    }

    public function handleNewRealm(NewRealmEvent $newRealmEvent)
    {
        $realm = $newRealmEvent->realm;
        if ($realm->getRealmName() == $this->getRealm()) {
            $realm->addModule($this);
        }
    }

    public function getSubscribedRealmEvents()
    {
        return [
            'SubscribeMessageEvent' => ['handleSubscribeMessage', 100],
            'PublishMessageEvent' => ['handlePublishMessage', 100],
            'UnsubscribeMessageEvent' => ['handleUnsubscribeMessage', 100],
            'SendSubscribedMessageEvent' => ['handleSubscribedMessage', 100],
            'CallMessageEvent' => ['handleCallMessage', 100],
            'SendResultMessageEvent' => ['handleResultMessage', 100],
        ];
    }

    public function handlePublishMessage(MessageEvent $event)
    {
        /* @var $message \Thruway\Message\PublishMessage */
        $message = $event->message;

        $topic = $this->_getTopic()->getTopic($message->getTopicName());
        if ($topic !== null) {
            $topic->onPublish($this, $event);
        }
    }

    public function handleSubscribeMessage(MessageEvent $event)
    {
        /* @var $message \Thruway\Message\SubscribeMessage */
        $message = $event->message;
        $topic = $this->_getTopic()->getTopic($message->getTopicName());

        if ($topic !== null) {
            $this->subscriptions[$message->getRequestId()] = $message->getTopicName();
            $topic->onSubscribe($this, $event);
        }
    }

    public function handleCallMessage(MessageEvent $event)
    {
        /* @var $message \Thruway\Message\CallMessage  */
        $message = $event->message;
        $this->callRequestIndex[$message->getRequestId()] = $message;

        $rpc = $this->_getRPC()->getRpc($message->getProcedureName());
        if ($rpc !== null && $rpc['event'] !== null) {
            call_user_func([$rpc['class'], $rpc['event']], $this, $event);
        }
    }

    public function handleResultMessage(MessageEvent $event)
    {
        /* @var $message \Thruway\Message\ResultMessage  */
        $message = $event->message;
        $call = $this->getCallByRequestId($message->getRequestId());
        if ($call instanceof \Thruway\Message\CallMessage) {
            $rpc = $this->_getRPC()->getRpc($call->getProcedureName());
            if ($rpc !== null && $rpc['then'] !== null) {
                call_user_func([$rpc['class'], $rpc['then']], $this, $event);
            }
        }
    }

    public function onMessage(\Thruway\Transport\TransportInterface $transport, Message $msg)
    {
        parent::onMessage($transport, $msg);
        $this->_closeDb();
    }

    /**
     * @param number $requestId
     *
     * @return \Thruway\Message\CallMessage
     */
    public function getCallByRequestId($requestId)
    {
        return array_get($this->callRequestIndex, $requestId, false);
    }

    /**
     * @param MessageEvent $event
     */
    public function handleUnsubscribeMessage(MessageEvent $event)
    {
        /* @var $message \Thruway\Message\UnsubscribeMessage */
        $message = $event->message;
        if (array_has($this->subscriptions, $message->getSubscriptionId())) {
            $topic = $this->_getTopic()->getTopic($this->subscriptions[$message->getSubscriptionId()]);
            if ($topic !== null) {
                array_forget($this->subscriptions, [$message->getSubscriptionId()]);
                $topic->onUnSubscribe($this, $event);
            }
        }
    }

    public function handleSubscribedMessage(MessageEvent $event)
    {
        /* @var $message \Thruway\Message\SubscribedMessage */
        $message = $event->message;
        if (array_has($this->subscriptions, $message->getRequestId())) {
            $topicName = array_get($this->subscriptions, $message->getRequestId());
            array_forget($this->subscriptions, [$message->getRequestId()]);
            $this->subscriptions[$message->getSubscriptionId()] = $topicName;
        }
    }

    public function onSessionStart($session, $transport)
    {
        parent::onSessionStart($session, $transport);

        $transport->setSerializer(new \WebSocketBundle\Serializer\JsonSerializer());
        $this->session->getTransport()->setSerializer(new \WebSocketBundle\Serializer\JsonSerializer());

        $rpcManager = $this->container->get('websocket.rpc_manager');

        foreach ($rpcManager->getRpc() as $uri => $rpc) {
            $this->session->register($uri, [$rpc['class'], $rpc['method']], $rpc);
        }
    }

    /**
     * Get topic manager.
     *
     * @return \WebSocketBundle\Manager\TopicManager
     */
    protected function _getTopic()
    {
        return $this->container->get('websocket.topic_manager');
    }

    /**
     * Get RPC Manager.
     *
     * @return \WebSocketBundle\Manager\RpcManager
     */
    protected function _getRPC()
    {
        return $this->container->get('websocket.rpc_manager');
    }

    protected function _closeDb()
    {
        $this->container->get('doctrine')->getConnection()->close();
    }
}
