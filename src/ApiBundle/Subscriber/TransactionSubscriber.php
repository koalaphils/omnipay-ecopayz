<?php

namespace ApiBundle\Subscriber;

use AppBundle\Helper\Publisher;
use AppBundle\Service\TransactionService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ApiBundle\Event\TransactionCreatedEvent;

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
        ];
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
                'type' => 'deposit',
            ],
        ]);
    }
}
