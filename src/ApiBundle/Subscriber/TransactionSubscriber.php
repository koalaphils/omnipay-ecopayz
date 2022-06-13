<?php

namespace ApiBundle\Subscriber;

use AppBundle\Helper\Publisher;
use AppBundle\Service\TransactionService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ApiBundle\Event\TransactionCreatedEvent;
use TransactionBundle\Event\TransactionDeclinedEvent;
use WebSocketBundle\Topics;

class TransactionSubscriber implements EventSubscriberInterface
{
    private $publisher;
    private $transactionService;

    public function __construct(Publisher $publisher, TransactionService $transactionService)
    {
        $this->publisher = $publisher;
        $this->transactionService = $transactionService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'transaction.created' => 'onTransactionCreated',
            TransactionDeclinedEvent::NAME => 'onTransactionDeclined'
        ];
    }

    public function onTransactionDeclined(TransactionDeclinedEvent $event): void
    {
        $transaction = $event->getTransaction();

        $this->publisher->publishUsingWamp(Topics::TOPIC_TRANSACTION_DECLINED . '.' . $transaction->getCustomer()->getWebsocketChannel(), [
            'title' => 'Transaction Declined',
            'message' => 'Transaction ' . $transaction->getNumber() . ' has been declined.',
            'id' => $transaction->getId(),
            'number' => $transaction->getNumber(),
            'type' => 'deposit',
            'status' => 'Declined',
        ]);
    }
    
    public function onTransactionCreated(TransactionCreatedEvent $event)
    {
        $transaction = $event->getTransaction();

        $this->transactionService->create([
            'transaction_id' => $transaction->getId(),
            'payment_option_type' => $transaction->getPaymentOptionType()
        ]);

        $this->publisher->publishUsingWamp('created.transaction', [
            'title' => 'Transaction Requested',
            'message' => 'Transaction ' . $transaction->getNumber() . ' has been requested.',
            'otherDetails' => [
                'id' => $transaction->getId(),
                'type' => strtolower($transaction->getTypeAsText()),
            ],
        ]);
    }
}
