<?php

namespace TransactionBundle\EventHandler;

use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use ApiBundle\Event\CustomerCreatedEvent;
use AppBundle\Helper\Publisher;
use TransactionBundle\Event\TransactionProcessEvent;
use TransactionBundle\WebsocketTopics;
use DbBundle\Entity\Customer;
use DbBundle\Entity\Transaction;
use TransactionBundle\Repository\TransactionRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class TransactionSubscriberForWebsocket implements EventSubscriberInterface
{
    private $publisher;

    public function __construct(Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'transaction.pre_save' => [
                ['onTransactionPreSave', 200],
            ],
            'customer.created' => [
                ['onCustomerCreated', 100],
            ],
            'transaction.autoDeclined' => [
                ['onTransactionAutoDecline', 50],
            ]
        ];
    }

    public function onTransactionAutoDecline(TransactionProcessEvent $event): void
    { 
        $this->onTransactionPreSave($event);
    }

    public function onTransactionPreSave(TransactionProcessEvent $event): void
    {
        if ($event->getTransaction()->getType() !== Transaction::TRANSACTION_TYPE_DWL) {
            $transactionNumber = $event->getTransaction()->getNumber();
            $type = $event->getTransaction()->getTypeText();
            $status = empty($event->getAction()) ? $this->getPastTense('Decline') : $this->getPastTense($event->getAction()['label']);
            $payload['message'] = 'Transaction ' . $transactionNumber . ' ' . $type . ' has been ' . $status;
            $payload['status'] = $status;

            if ($event->getTransaction()->isP2pTransfer()) {
                $members = $event->getMembersInSubTransactions();
                foreach ($members as $member) {
                    $this->createNotification($member, $payload['message']);
                    $this->publishWebsocketTopic($member->getWebsocketDetails()['channel_id'], $payload);
                }
            } else {
                $customer = $event->getTransaction()->getCustomer();
                $channel = $customer->getWebsocketDetails()['channel_id'];

                $this->createNotification($customer, $payload['message']);
                $this->publishWebsocketTopic($channel, $payload);
            }
        }
    }

    public function onCustomerCreated(CustomerCreatedEvent $event): void
    {
        $response = [
            'title' => 'Member Created',
            'message' => 'Member '. $event->getCustomer()->getFullName() .' is created.',
            'otherDetails' => ['type' => 'profile', 'id' => $event->getCustomer()->getId()]
        ];

        $this->publisher->publish(WebsocketTopics::TOPIC_CUSTOMER_CREATED, json_encode($response));
    }

    protected function publishWebsocketTopic(string $channel, array $payload = []) :void
    {
        $this->publisher->publish(WebsocketTopics::TOPIC_TRANSACTION_PROCESSED . '.' . $channel, json_encode($payload));
    }

    /**
     * Create database notification.
     */
    protected function createNotification(Customer $customer, string $message): void
    {
        $notification = new \stdClass();
        $notification->read = false;
        $notification->message = $message;

        $dateTime = new \DateTime('now');
        $notification->dateTime = $dateTime->format('M j, Y g:i a');

        $customer->addNotification($notification);
    }

    protected function getPastTense(string $status): string
    {
        switch ($status) {
            case 'Void':
                return 'Voided';
            case 'Approve':
                return 'Approved';
            case 'Decline':
                return 'Declined';
            case 'Acknowledge':
                return 'Acknowledged';
            case 'Process':
                return 'Processed';
            case 'Save':
                return 'Saved';
            default:
                return $status;
        }
    }

    protected function getTransactionRepository(): TransactionRepository
    {
        return $this->getEntityManager()->getRepository(Transaction::class);
    }

    protected function getEntityManager(string $name = 'default'): EntityManager
    {
        return $this->getDoctrine()->getManager($name);
    }

    protected function getDoctrine(): RegistryInterface
    {
        return $this->getContainer()->get('doctrine');
    }
}
