<?php

namespace MemberBundle\RequestHandler;

use AppBundle\Event\GenericEntityEvent;
use DbBundle\Entity\MemberCommission;
use DbBundle\Listener\VersionableListener;
use DbBundle\Repository\MemberCommissionRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use MemberBundle\Request\UpdateCommissionRequest;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Description of UpdateCommissionRequestHandler
 *
 * @author cydrick
 */
class UpdateCommissionRequestHandler
{
    private $doctrine;
    private $eventDisptacher;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function handle(UpdateCommissionRequest $request): MemberCommission
    {
        if (!empty(trim($request->getResourceId()))) {
            $commission = $this->getMemberCommissionRepository()->findCommissionByResourceId($request->getResourceId());
            $commission->preserveOriginal();

            $commission->setCommission($request->getCommission());
            // NOTE: see https://ac88dev.atlassian.net/browse/AC66-1017
            //        $commission->setStatus($request->getStatus());

            $this->dispatchEvent(VersionableListener::VERSIONABLE_SAVE, new GenericEntityEvent($commission));
        } else {
            $member = $request->getMember();
            $product = $this->doctrine->getRepository('DbBundle:Product')->findOneById($request->getProductId());
            $commissionPercentage = $request->getCommission();
            $commission = $this->createNewMemberCommissionRecord($product, $member, $commissionPercentage);
        }

        return $commission;
    }

    private function createNewMemberCommissionRecord($product, $member, $commissionPercentage): MemberCommission
    {
        $commission = new MemberCommission();
        $commission->setProduct($product);
        $commission->setMember($member);
        $commission->setCommission($commissionPercentage);
        $commission->createResourceIdForSelf();
        $commission->generateResourceId();
        $commission->setCreatedAt(new \DateTime());
        $commission->activate();

        $this->doctrine->getManager()->persist($commission);
        $this->doctrine->getManager()->flush();

        return $commission;
    }


    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDisptacher = $eventDispatcher;
    }

    protected function dispatchEvent(string $eventName, Event $event): void
    {
        $this->getEventDispatcher()->dispatch($eventName, $event);
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDisptacher;
    }

    private function getMemberCommissionRepository(): MemberCommissionRepository
    {
        return $this->doctrine->getRepository(MemberCommission::class);
    }
}
