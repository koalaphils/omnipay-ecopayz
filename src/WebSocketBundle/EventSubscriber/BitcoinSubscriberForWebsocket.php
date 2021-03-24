<?php

namespace WebSocketBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use AppBundle\Helper\Publisher;
use PaymentBundle\Event\BitcoinRateSettingSaveEvent;
use WebSocketBundle\Topics;

class BitcoinSubscriberForWebsocket implements EventSubscriberInterface
{
    private $publisher;

    public function __construct(Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BitcoinRateSettingSaveEvent::NAME => ['onBitcoinRateSettingSaved', 300],
        ];
    }

    public function onBitcoinRateSettingSaved(BitcoinRateSettingSaveEvent  $event)
    {
        $bitcoinAdjustment = $event->getBitcoinAdjustment();
        $transactionType = $event->getTransactionType();
        $this->publisher->publish(Topics::TOPIC_BTC_EXCHANGE_RATE, $bitcoinAdjustment->createWebsocketPayload($transactionType));
    }
}
