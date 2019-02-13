<?php

namespace ApiBundle\RequestHandler;

use Doctrine\ORM\EntityManagerInterface;
use MemberBundle\Event\MemberProductRequestEvent;
use MemberBundle\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use DbBundle\Repository\ProductRepository;
use ApiBundle\Request\CreateMemberProductRequest\MemberProductList as CreateMemberProductRequest;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\Product;
use Doctrine\Common\Collections\ArrayCollection;
use DateTime;

class CreateMemberProductRequestHandler
{
    private $entityManager;
    private $productRepository;
    private $eventDispatcher;

    public function __construct(EntityManagerInterface $entityManager, ProductRepository $productRepository, EventDispatcherInterface $eventDispatcher)
    {
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function handle(CreateMemberProductRequest $createMemberProductRequest, Member $member): ArrayCollection
    {
        $memberProducts = new ArrayCollection();
        $entityManager = $this->getEntityManager();
        $productRepository = $this->getProductRepository();

        foreach ($createMemberProductRequest->getMemberProducts() as $memberProductRequest) {
            $product = $productRepository->findOneByCode($memberProductRequest->getProduct());

            $memberProduct = MemberProduct::create($member);
            $memberProduct->setProduct($product);

            if ($product->hasUsername()) {
                $memberProduct->setUserName($memberProductRequest->getUsername());
            } else {
                $memberProduct->setUserName($this->generateUsername($product));
            }

            $memberProduct->activate();
            $memberProduct->setBalance(0);
            $memberProduct->setRequestedAt(new DateTime('now'));
            $entityManager->persist($memberProduct);

            $memberProducts->add($memberProduct);
        }

        $this->notifyOnCreate($memberProducts, $member);

        $entityManager->flush();

        return $memberProducts;
    }

    private function notifyOnCreate(ArrayCollection $memberProducts, Member $member): void
    {
        $this->getEventDispatcher()->dispatch(
            Events::EVENT_MEMBER_PRODUCT_REQUESTED,
            new MemberProductRequestEvent($member, $memberProducts)
        );
    }

    private function generateUsername(Product $product): string
    {
        return uniqid('tmp_' . str_replace(' ', '', $product->getName()) . '_');
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->productRepository;
    }
}