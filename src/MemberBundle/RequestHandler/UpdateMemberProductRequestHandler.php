<?php

namespace MemberBundle\RequestHandler;

use DbBundle\Entity\Product;
use DbBundle\Repository\ProductRepository;
use DbBundle\Repository\CustomerProductRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\EventDispatcher\EventDispatcher;

use BrokerageBundle\Manager\BrokerageManager;
use CustomerBundle\Events;
use CustomerBundle\Event\CustomerProductSaveEvent;
use DbBundle\Entity\CustomerProduct;
use MemberBundle\Request\UpdateMemberProductRequest;
use CustomerBundle\Manager\CustomerProductManager;

class UpdateMemberProductRequestHandler
{
    private $doctrine;
    private $dispatcher;

    private $brokerageManager;
    private $customerProductManager;

    public function __construct(Registry $doctrine, $dispatcher, BrokerageManager $brokerageManager, CustomerProductManager $customerProductManager)
    {
        $this->doctrine = $doctrine;
        $this->dispatcher = $dispatcher;

        $this->brokerageManager = $brokerageManager;
        $this->customerProductManager = $customerProductManager;
    }

    public function handle(UpdateMemberProductRequest $request): CustomerProduct
    {
        $customerProduct = $this->getCustomerProductRepository()->find($request->getCustomerProduct());

        $customerProduct->setUserName($request->getUsername());
        
        if (!empty($request->getBrokerage())) {
            $customerProduct->setBrokerageSyncId($request->getBrokerage());
            $customerProduct->setBrokerageFirstName($request->getBrokerageFirstName());
            $customerProduct->setBrokerageLastName($request->getBrokerageLastName());
        } else {
            $customerProduct->unsetBrokerage();
        }

        $customerProduct->setBalance($request->getBalance());
        $this->customerProductManager->preventMultipleActiveSkypeBettingProduct($customerProduct);
        $this->dispatcher->dispatch(Events::EVENT_CUSTOMER_PRODUCT_SAVE, new CustomerProductSaveEvent($customerProduct));        

        $this->doctrine->getManager()->persist($customerProduct);
        $this->doctrine->getManager()->flush($customerProduct);

        return $customerProduct;
    }

    private function getCustomerProductRepository(): CustomerProductRepository
    {
        return $this->doctrine->getRepository(CustomerProduct::class);
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->doctrine->getRepository(Product::class);
    }
}
