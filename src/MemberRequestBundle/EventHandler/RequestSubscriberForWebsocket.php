<?php

namespace MemberRequestBundle\EventHandler;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AppBundle\Helper\Publisher;
use Symfony\Component\Translation\TranslatorInterface;
use MemberRequestBundle\Event\RequestProcessEvent;
use MemberRequestBundle\WebsocketTopics;
use MemberRequestBundle\Events;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\MemberRequest;
use DbBundle\Entity\Notification;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RequestSubscriberForWebsocket implements EventSubscriberInterface
{
    private $publisher;
    private $translator;
    private $entityManager;
    private $route;
    private $eventDispatcher;

    public function __construct(
        Publisher $publisher,
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        Router $route,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->publisher = $publisher;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->route = $route;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'request.saved' => [
                ['onRequestSaved', 100],
            ],
        ];
    }

    public function onRequestSaved(RequestProcessEvent $event): void
    {
        $memberRequest = $event->getRequest();
        $requestNumber = $memberRequest->getNumber();
        $type = $memberRequest->getCleanTypeText();
        $status = $memberRequest->getStatusText();

        $member = $memberRequest->getMember();
        $locale = $member->getLocale();
        $channel = $member->getWebsocketDetails()['channel_id'];

        $payload['id'] = $memberRequest->getId();
        $payload['status'] = $status;
        $payload['type'] = $memberRequest->getTypeText();
        $payload['isVerified'] = $member->isVerified();

        $payload['message'] = $this->getTranslator()->trans('Request', array(), 'messages', $locale) . ' ' .
        $requestNumber . ' ' . $type . ' ' . $this->translator->trans('has been', array(), 'messages', $locale) . ' ' . $status;

        /*
        $notification = 'Request ' . $requestNumber . ' ' . $type . ' has been ' . $status;
        $this->createNotification($member, $notification);
        $this->publishWebsocketTopic($memberRequest, $channel, $payload);*/
        //$this->createAdminNotification($memberRequest);
        //$this->publisher->publish(Events::EVENT_MEMBER_REQUEST_PROCESSED . '.' . $channel, json_encode($payload));

        $this->publisher->publishUsingWamp(WebsocketTopics::TOPIC_MEMBER_REQUEST_PROCESSED . '.' . $channel, $payload);
        //$this->getEventDispatcher()->dispatch(Events::EVENT_MEMBER_REQUEST_PROCESSED, $event);
    }

    private function createNotification(Member $member, string $message): void
    {
        $entityManager = $this->getEntityManager();
        
        $notification = new \stdClass();
        $notification->read = false;
        $notification->message = $message;

        $dateTime = new \DateTime('now');
        $notification->dateTime = $dateTime->format('M j, Y g:i a');

        $member->addNotification($notification);
        $entityManager->persist($member);
        $entityManager->flush($member);
    }

    public function createAdminNotification(MemberRequest $memberRequest): void
    {
        if ($memberRequest->isProductPassword()) {
            $memberRequestRoute = $this->getRoute()->generate('member_request.update_page', [
                'type' => $memberRequest->getTypeText(),
                'id' => $memberRequest->getId()]
            );
            $entityManager = $this->getEntityManager();

            $notification = new Notification();
            $notification->setChannel(Notification::NOTIFICATION_CHANNEL_BACKOFFICE);
            $notification->setTitle('Product Password Request');
            $notification->setDetail('member_request_route', $memberRequestRoute);
            $notification->setMessage(sprintf('%s %s was %s', $memberRequest->getNumber(), $memberRequest->getCleanTypeText(), $memberRequest->getStatusText()));
            $notification->setMember($memberRequest->getMember());
            $notification->setType('member_request');
       
            $entityManager->persist($notification);
            $entityManager->flush($notification);
        }
    }

    private function publishWebsocketTopic(MemberRequest $memberRequest, string $channel, array $payload = []) :void
    {
        $this->publisher->publish(WebsocketTopics::TOPIC_MEMBER_REQUEST_SAVED . '.' . $channel, json_encode($payload));
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    protected function getRoute(): Router
    {
        return $this->route;
    }

    protected function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }
}
