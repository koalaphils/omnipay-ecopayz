<?php

namespace MemberBundle\RequestHandler;

use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\Product;
use DbBundle\Repository\ProductRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use MemberBundle\Request\CreateMemberProductRequest;
use CustomerBundle\Events;
use CustomerBundle\Event\CustomerProductSaveEvent;
use CustomerBundle\Manager\CustomerProductManager;

class CreateMemberProductRequestHandler
{
    private $doctrine;
    private $dispatcher;

    private $brokerageManager;
    private $customerProductManager;

    public function __construct(Registry $doctrine, $dispatcher, CustomerProductManager $customerProductManager)
    {
        $this->doctrine = $doctrine;
        $this->dispatcher = $dispatcher;
        $this->customerProductManager = $customerProductManager;
    }

    public function handle(CreateMemberProductRequest $request): CustomerProduct
    {
        $product = $this->getProductRepository()->find($request->getProduct());

        $customerProduct = $request->getCustomerProduct();
        $customerProduct->setUserName($request->getUsername());
        $customerProduct->setBalance($request->getBalance());
        $customerProduct->setIsActive($request->getActive());
        $customerProduct->setProduct($product);
        $customerProduct->setUpdatedAt(new \DateTime());
        $this->dispatcher->dispatch(Events::EVENT_CUSTOMER_PRODUCT_SAVE, new CustomerProductSaveEvent($customerProduct));   
        $this->doctrine->getManager()->persist($customerProduct);
        $this->doctrine->getManager()->flush($customerProduct);

        return $customerProduct;
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->doctrine->getRepository(Product::class);
    }
}
