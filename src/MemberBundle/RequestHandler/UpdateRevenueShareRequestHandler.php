<?php

namespace MemberBundle\RequestHandler;

use Doctrine\ORM\EntityManager;
use MemberBundle\Request\UpdateRevenueShareRequest;
use DbBundle\Entity\MemberRevenueShare;
use DbBundle\Entity\Product;
use DbBundle\Repository\ProductRepository;
use DbBundle\Repository\MemberRevenueShareRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use AppBundle\Event\GenericEntityEvent;
use DbBundle\Listener\VersionableListener;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Description of UpdateRevenueShareRequest
 *
 * @author bianca
 */
class UpdateRevenueShareRequestHandler
{
    private $entityManager;
    private $eventDisptacher;
    private $productRepository;
    private $memberRevenueShareRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function handle(UpdateRevenueShareRequest $request)
    {
        $customer = $request->getCustomer();
        if (!empty(trim($request->getResourceId()))) {
            $rangeSetting = $this->getMemberRevenueShareRepository()->findRevenueShareByResourceId($request->getResourceId());
            $rangeSetting->preserveOriginal();
            $rangeSetting->setSettings($request->getRevenueShareSettings());
            $this->dispatchEvent(VersionableListener::VERSIONABLE_SAVE, new GenericEntityEvent($rangeSetting));
        } else {
            $productEntity = $this->getProductRepository()->findOneById($request->getProductId());
            $rangeSetting = new MemberRevenueShare();
            $rangeSetting->setMember($customer);
            $rangeSetting->setProduct($productEntity);
            $rangeSetting->setSettings($request->getRevenueShareSettings());
            $rangeSetting->createResourceIdForSelf();
            $rangeSetting->generateResourceId();
            $rangeSetting->setCreatedAt(new \DateTime());
            $rangeSetting->setStatus(MemberRevenueShare::REVENUE_SHARE_STATUS_ACTIVE);

            $this->entityManager->persist($rangeSetting);
            $this->entityManager->flush();
        }

        return $customer;
    }

    public function setMemberRevenueShareRepository(MemberRevenueShareRepository $memberRevenueShareRepository): void
    {
        $this->memberRevenueShareRepository = $memberRevenueShareRepository;
    }

    private function getMemberRevenueShareRepository(): MemberRevenueShareRepository
    {
        return $this->memberRevenueShareRepository;
    }

    public function setProductRepository(ProductRepository $productRepository): void
    {
        $this->productRepository = $productRepository;
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->productRepository;
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

    public function getRepository($classname)
    {
        return $this->getDoctrine()->getRepository($classname);
    }
}