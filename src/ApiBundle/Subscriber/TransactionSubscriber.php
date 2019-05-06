<?php

namespace ApiBundle\Subscriber;

use AppBundle\Helper\Publisher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ApiBundle\Event\TransactionCreatedEvent;

/**
 * Description of TransactionSubscriber
 *
 * @author cnonog
 */
class TransactionSubscriber implements EventSubscriberInterface
{
    /**
     * @var Publisher
     */
    private $publisher;

    public function __construct(Publisher $publisher)
    {
        $this->publisher = $publisher;
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
