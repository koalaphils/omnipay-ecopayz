<?php

namespace WebSocketBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use AppBundle\Helper\Publisher;
use CustomerBundle\Event\CustomerProductSaveEvent;
use CustomerBundle\Event\CustomerProductActivatedEvent;
use CustomerBundle\Event\CustomerProductSuspendedEvent;
use CustomerBundle\Events as CustomerEvents;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\Customer as Member;
use MemberBundle\Events as MemberEvents;
use MemberBundle\Event\ReferralEvent;
use WebSocketBundle\Topics;

class CustomerSubscriberForWebsocket implements EventSubscriberInterface
{
    private $publisher;

    public function __construct(Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CustomerEvents::EVENT_CUSTOMER_PRODUCT_SAVE => ['onCustomerProductSave', 300],
            CustomerEvents::EVENT_CUSTOMER_PRODUCT_SUSPENDED => ['onCustomerProductSuspended', 300],
            CustomerEvents::EVENT_CUSTOMER_PRODUCT_ACTIVATED => ['onCustomerProductActivated', 300],
            MemberEvents::EVENT_REFERRAL_LINKED => ['onReferralLinked'],
            MemberEvents::EVENT_REFERRAL_UNLINKED => ['onReferralUnlinked']
        ];
    }

    public function onCustomerProductSave(CustomerProductSaveEvent $event): void
    {
        $customerProduct = $event->getCustomerProduct();
        $customer = $customerProduct->getCustomer();
        $channel = $customer->getWebsocketDetails()['channel_id'];

        $payload = $this->createPayloadFromCustomerProduct($customerProduct);
        $this->publisher->publish(Topics::TOPIC_CUSTOMER_PRODUCT_SAVE . '.' . $channel, json_encode($payload));
    }

    public function onCustomerProductSuspended(CustomerProductSuspendedEvent $event)
    {
        $customerProduct = $event->getCustomerProduct();
        $customer = $customerProduct->getCustomer();
        $channel = $customer->getWebsocketDetails()['channel_id'];

        $payload = $this->createPayloadFromCustomerProduct($customerProduct);

        $this->publisher->publish(Topics::TOPIC_CUSTOMER_PRODUCT_SUSPENDED . '.' . $channel, json_encode($payload));
    }

    public function onCustomerProductActivated(CustomerProductActivatedEvent $event)
    {
        $customerProduct = $event->getCustomerProduct();
        $customer = $customerProduct->getCustomer();
        $channel = $customer->getWebsocketDetails()['channel_id'];

        $payload = $this->createPayloadFromCustomerProduct($customerProduct);

        $this->publisher->publish(Topics::TOPIC_CUSTOMER_PRODUCT_ACTIVATED . '.' . $channel, json_encode($payload));
    }

    protected function createPayloadFromCustomerProduct(CustomerProduct $customerProduct): array
    {
        $customer = $customerProduct->getCustomer();
        $channel = $customer->getWebsocketDetails()['channel_id'];

        $payload['username'] = $customerProduct->getUserName();
        $payload['isActive'] = $customerProduct->getIsActive();
        $payload['syncId'] = $customerProduct->getBrokerageSyncId();
        $payload['oldSyncId'] = $customerProduct->getOldBrokerageSyncId();

        return $payload;
    }

    public function onReferralLinked(ReferralEvent $event): void
    {
        $referrer = $event->getReferrer();
        $referral = $event->getReferral();
        $channel = $referrer->getWebsocketDetails()['channel_id'];
        $payload['message'] = 'New referral has been linked. Please contact customer support for more details.';

        $this->createNotification($referrer, 'New referral ' . $referral->getId() . ' has been linked. ' . '(' . $this->getCurrentDatetime() . ')');
        $this->publisher->publish(Topics::TOPIC_REFERRAL_LINKED . '.' . $channel, json_encode($payload));
    }

    public function onReferralUnlinked(ReferralEvent $event): void
    {
        $referrer = $event->getReferrer();
        $referral = $event->getReferral();
        $channel = $referrer->getWebsocketDetails()['channel_id'];
        $payload['message'] = 'Referral has been unlinked. Please contact customer support for more details.';
    
        $this->createNotification($referrer, 'Referral ' . $referral->getId() . ' has been unlinked. '. '(' . $this->getCurrentDatetime() . ')');
        $this->publisher->publish(Topics::TOPIC_REFERRAL_UNLINKED . '.' . $channel, json_encode($payload));
    }

    /**
     * Create database notification.
     */
    protected function createNotification(Member $member, string $message): void
    {
        $notification = new \stdClass();
        $notification->read = false;
        $notification->message = $message;

        $notification->dateTime = $this->getCurrentDatetime();

        $member->addNotification($notification);
    }

    protected function getCurrentDatetime(): string
    {
        $dateTime = new \DateTime('now');

        return $dateTime->format('M j, Y g:i a');
    }
}
