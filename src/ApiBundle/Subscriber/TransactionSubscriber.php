<?php

namespace ApiBundle\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ApiBundle\Event\TransactionCreatedEvent;

/**
 * Description of TransactionSubscriber
 *
 * @author cnonog
 */
class TransactionSubscriber implements EventSubscriberInterface
{
    
    public static function getSubscribedEvents(): array
    {
        return [
            'transaction.created' => 'onTransactionCreated',
        ];
    }
    
    public function onTransactionCreated(TransactionCreatedEvent $event)
    {
    }
}
