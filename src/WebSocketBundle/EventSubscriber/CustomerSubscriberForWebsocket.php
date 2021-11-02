<?php

namespace WebSocketBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use AppBundle\Helper\Publisher;
use CustomerBundle\Event\CustomerProductSaveEvent;
use CustomerBundle\Event\CustomerProductActivatedEvent;
use CustomerBundle\Event\CustomerProductSuspendedEvent;
use MemberBundle\Event\MemberProductRequestEvent;
use CustomerBundle\Events as CustomerEvents;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\Customer as Member;
use MemberBundle\Events as MemberEvents;
use MemberBundle\Event\ReferralEvent;
use MemberBundle\Event\VerifyEvent;
use WebSocketBundle\Topics;
use MemberRequestBundle\WebsocketTopics;
use AppBundle\Event\GenericEntityEvent;
use DbBundle\Entity\User;

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
            MemberEvents::EVENT_REFERRAL_UNLINKED => ['onReferralUnlinked'],
            MemberEvents::EVENT_MEMBER_PRODUCT_REQUESTED => ['onMemberProductRequested', 300],
            MemberEvents::EVENT_MEMBER_KYC_FILE_UPLOADED => ['onMemberKycFileUploaded', 300],
            MemberEvents::MEMBER_VERIFICATION => ['onMemberVerification', 300],
            MemberEvents::EVENT_ADMIN_USER_LOGIN => ['onAdminUserLogin', 300],
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

    public function onMemberVerification(VerifyEvent $event)
    {
        $member = $event->getMember();
        $channel = $member->getWebsocketDetails()['channel_id'];
        $payload['isVerified'] = $member->isVerified();

        $this->publisher->publishUsingWamp(WebsocketTopics::TOPIC_MEMBER_REQUEST_PROCESSED . '.' . $channel, $payload);
    }

    public function onCustomerProductSuspended(CustomerProductSuspendedEvent $event)
    {
        $customerProduct = $event->getCustomerProduct();
        $customer = $customerProduct->getCustomer();
        $channel = $customer->getWebsocketDetails()['channel_id'];

        $payload = $this->createPayloadFromCustomerProduct($customerProduct);

        $this->publisher->publishUsingWamp(Topics::TOPIC_CUSTOMER_PRODUCT_SUSPENDED . '.' . $channel, $payload);
    }

    public function onCustomerProductActivated(CustomerProductActivatedEvent $event)
    {
        $customerProduct = $event->getCustomerProduct();
        $customer = $customerProduct->getCustomer();
        $channel = $customer->getWebsocketDetails()['channel_id'];
        $payload = $this->createPayloadFromCustomerProduct($customerProduct);
        $payload['details'] = $event->getDetails();
        $this->publisher->publishUsingWamp(Topics::TOPIC_CUSTOMER_PRODUCT_ACTIVATED . '.' . $channel, $payload);
    }

    protected function createPayloadFromCustomerProduct(CustomerProduct $customerProduct): array
    {
        $customer = $customerProduct->getCustomer();
        $channel = $customer->getWebsocketDetails()['channel_id'];

        return [
            'username' => $customerProduct->getUserName(),
            'isActive' => $customerProduct->getIsActive(),
            'product' =>  $customerProduct->getProduct()->getCode()
        ];
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

    public function onMemberKycFileUploaded($event): void
    {
        $member = $event->getCustomer();
        $details = $event->getDetails();
        $channel = $member->getWebsocketDetails()['channel_id'];
        $member = $event->getCustomer();

        $this->publisher->publishUsingWamp(MemberEvents::EVENT_MEMBER_KYC_FILE_UPLOADED, [
            'title' => $details['notificationTitle'],
            'message' => $details['notificationMessage'],
            'type' => 'docs',
            'otherDetails' => [
                'id' => $details['id'],
                'type' => $details['type'],
            ],
        ]);
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

    public function onMemberProductRequested(MemberProductRequestEvent $event): void
    {
        $member = $event->getMember();
        $memberProducts = [];

        foreach ($event->getMemberProducts() as $memberProduct) {
            $memberProducts[] = $memberProduct->getProductName();
        }

        $channel = $member->getWebsocketDetails()['channel_id'];
        $payload['message'] = sprintf('Product/s %s has been added.', implode($memberProducts, ', '));

        $this->createNotification($member, $payload['message']);
        $this->publisher->publish(Topics::TOPIC_MEMBER_PRODUCT_REQUESTED . '.' . $channel, json_encode($payload));

        foreach ($memberProducts as $memberProduct) {
            $payload['title'] = 'Product Request';
            $payload['message'] = sprintf('Product %s has been added.', $memberProduct) . ' (' . $member->getFullName() . ')';
            $payload['otherDetails'] = ['type' => 'product', 'id' => $member->getId()];
            $this->publisher->publish(Topics::TOPIC_MEMBER_PRODUCT_REQUESTED, json_encode($payload));
        }
    }

    public function onAdminUserLogin(GenericEntityEvent $event){
        $user = $event->getEntity();
        if($user instanceof User){
            $username = $user->getUsername();
            $lastLoginIP = $user->getPreference('lastLoginIP');

            $this->publisher->publishUsingWamp(Topics::TOPIC_ADMIN_USER_LOGIN, ['title' => 'User Logged in', 'message' => "${username} logged in on ${lastLoginIP}."]);
        }
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
