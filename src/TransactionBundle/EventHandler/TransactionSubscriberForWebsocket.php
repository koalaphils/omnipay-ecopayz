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
use TransactionBundle\Event\BitcoinRateExpiredEvent;
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
            ],
            BitcoinRateExpiredEvent::NAME => [
                ['onTransactionBitcoinRateExpired', 50],
            ]
        ];
    }

    public function onTransactionBitcoinRateExpired(BitcoinRateExpiredEvent $event): void
    {
        $transaction = $event->getTransaction();
        $customer = $transaction->getCustomer();
        $channel = $customer->getWebsocketDetails()['channel_id'];

        $payload = [
            'transaction_id' => $transaction->getId(),
        ];
        
        $this->publisher->publish(WebsocketTopics::TOPIC_BITCOIN_RATE_TRANSACTION_EXPIRED . '.' . $channel, json_encode($payload));
    }

    public function onTransactionAutoDecline(TransactionProcessEvent $event): void
    { 
        $this->onTransactionPreSave($event);
    }

    public function onTransactionPreSave(TransactionProcessEvent $event): void
    {
        if ($event->getTransaction()->getType() !== Transaction::TRANSACTION_TYPE_DWL) {
            $transactionNumber = $event->getTransaction()->getNumber();
            $type = $event->getTransaction()->getTypeAsText();
            $status = empty($event->getAction()) ? $this->getPastTense('Decline') : $this->getPastTense($event->getAction()['label']);
            $payload['message'] = 'Transaction ' . $transactionNumber . ' ' . $type . ' has been ' . $status;
            $payload['id'] = $event->getTransaction()->getId();
            $payload['status'] = $status;
            $payload['date'] = $event->getTransaction()->getDate()->format('c');
            $payload['type'] = strtolower($event->getTransaction()->getTypeAsText());

            if ($event->getTransaction()->getPaymentOptionType() !== null) {
                $payload['payment_option'] = $event->getTransaction()->getPaymentOptionType()->getCode();
            } else {
                $payload['payment_option'] = null;
            }
            if ($payload['payment_option'] === 'BITCOIN') {
                $payload['btc_address'] = $event->getTransaction()->getBitcoinAddress();
            }
            $payload['reason'] = '';
            if ($event->getTransaction()->isVoided() || $event->getTransaction()->isDeclined()) {
                $payload['reason'] = $event->getTransaction()->getVoidingReason();
            }


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
                // zimi
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
        $this->publisher->publishUsingWamp(WebsocketTopics::TOPIC_TRANSACTION_PROCESSED . '.' . $channel, $payload);
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
            case 'Confirm':
                return 'Confirmed';
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
