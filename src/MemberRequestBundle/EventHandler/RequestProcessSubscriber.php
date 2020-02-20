<?php

namespace MemberRequestBundle\EventHandler;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use MemberRequestBundle\Event\RequestProcessEvent;
use Symfony\Component\Workflow\Event\Event as WorkflowEvent;
use Symfony\Component\Workflow\Event\GuardEvent as WorkflowGuardEvent;
use TransactionBundle\Exceptions\TransitionGuardException;
use Symfony\Component\Workflow\Workflow;
use AppBundle\Manager\SettingManager;
use MemberRequestBundle\Manager\MemberRequestManager;
use Symfony\Component\Translation\TranslatorInterface;
use DbBundle\Entity\Customer as Member;
use MemberRequestBundle\WebsocketTopics;
use AppBundle\Helper\Publisher;

class RequestProcessSubscriber implements EventSubscriberInterface
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            'request.saving' => [
                ['onRequestSaving', 100],
            ],
            'workflow.request.entered' => [
                ['onRequestEntered', 100],
            ],
        ];
    }

    public function onRequestSaving(RequestProcessEvent $event): void
    {
        $memberRequest = $event->getRequest();
        $action = $event->getAction();
        if ($memberRequest->isNew()) {
            $transitionName = 'customer-new';
        } else {
            $transitionName = $memberRequest->getStatus() . '_' . $action['status'];
        }

        if ($this->getRequestWorkflow()->can($memberRequest, $memberRequest->getTypeText() . '-' . $transitionName)) {
            $this->getRequestWorkflow()->apply($memberRequest, $memberRequest->getTypeText() . '-' . $transitionName);
        } elseif ($this->getRequestWorkflow()->can($memberRequest, $transitionName)) {
            $this->getRequestWorkflow()->apply($memberRequest, $transitionName);
        } else {
            throw new TransitionGuardException('Unable to transition the request');
        }
    }

    public function onRequestEntered(WorkflowEvent $event): void
    {
        $memberRequest = $event->getSubject();
        $newStatus = $this->getMemberRequestManager()->getSettingStatus($memberRequest);

        if (array_get($newStatus, 'end', false)) {
            $this->getMemberRequestManager()->endRequest($memberRequest);
        } elseif (array_get($newStatus, 'decline', false)) {
            $this->getMemberRequestManager()->declineRequest($memberRequest);
        }
    }

    private function getRequestWorkflow(): Workflow
    {
        return $this->container->get('member_request.workflow');
    }

    private function getMemberRequestManager(): MemberRequestManager
    {
        return $this->container->get('member_request.manager');
    }
}
