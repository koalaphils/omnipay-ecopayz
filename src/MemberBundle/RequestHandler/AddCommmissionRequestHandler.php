<?php

namespace MemberBundle\RequestHandler;

use AppBundle\Event\GenericEntityEvent;
use DbBundle\Entity\MemberCommission;
use DbBundle\Entity\Product;
use DbBundle\Listener\VersionableListener;
use DbBundle\Repository\ProductRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use MemberBundle\Request\AddCommissionRequest;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Description of AddCommmissionRequestHandler
 *
 * @author cydrick
 */
class AddCommmissionRequestHandler
{
    private $doctrine;
    private $eventDisptacher;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function handle(AddCommissionRequest $request): MemberCommission
    {
        $product = $this->getProductRepository()->find($request->getProduct());
        $memberCommission = $request->getMemberCommission();
        $memberCommission->setCommission($request->getCommission());
        $memberCommission->setStatus($request->getStatus());
        $memberCommission->setProduct($product);
        $memberCommission->preserveOriginal();
        $this->dispatchEvent(VersionableListener::VERSIONABLE_SAVE, new GenericEntityEvent($memberCommission));

        return $memberCommission;
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

    private function save(MemberCommission $memberCommission): void
    {
        $this->getEntityManager()->persist($memberCommission);
        $this->getEntityManager()->flush($memberCommission);
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->doctrine->getRepository(Product::class);
    }

    private function getEntityManager(): EntityManager
    {
        return $this->doctrine->getManager();
    }
}
