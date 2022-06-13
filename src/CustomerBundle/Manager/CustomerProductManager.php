<?php

namespace CustomerBundle\Manager;

use DbBundle\Entity\CustomerProduct;
use DbBundle\Repository\CustomerProductRepository;
use ApiBundle\Service\JWTGeneratorService;
use AppBundle\Manager\AbstractManager;
use CustomerBundle\Events;
use CustomerBundle\Event\CustomerProductSaveEvent;
use CustomerBundle\Event\CustomerProductSuspendedEvent;
use CustomerBundle\Event\CustomerProductActivatedEvent;
use ProductIntegrationBundle\ProductIntegrationFactory;
use ProductIntegrationBundle\Exception\IntegrationException;
use ProductIntegrationBundle\Exception\IntegrationNotAvailableException;
use ProductIntegrationBundle\Exception\NoSuchIntegrationException;
use Symfony\Component\Routing\RouterInterface;

class CustomerProductManager extends AbstractManager 
{
    private $integrationFactory;
    private $jwtService;
    private $memberProductRepository;
    private $router;

    public function __construct(ProductIntegrationFactory $integrationFactory, 
        JWTGeneratorService $jwtService, 
        CustomerProductRepository $memberProductRepository, 
        RouterInterface $router)
    {
        $this->integrationFactory = $integrationFactory;
        $this->jwtService = $jwtService;
        $this->memberProductRepository = $memberProductRepository;
        $this->router = $router;
    }

    public function getRepository()
    {
        return $this->memberProductRepository;
    }

    public function getCustomerProductList($filters = null, bool $isSelect = false)
    {
        $status = true;
        $results = [];

        if (array_get($filters, 'datatable', 0)) {
            if (array_get($filters, 'search.value', false) !== false) {
                $filters['search'] = $filters['search']['value'];
            }
            $results['data'] = $this->getRepository()->getCustomerProductList($filters);
            if (array_get($filters, 'route', 0)) {
                $results['data'] = array_map(function ($data) {
                    $data['customerProduct'] = $data;
                    $data['routes'] = [
                        'update' => $this->getRouter()->generate('customerProduct.update_page', ['id' => $data['customerProduct']['id']]),
                        'view' => $this->getRouter()->generate('customerProduct.view_page', ['id' => $data['customerProduct']['id']]),
                    ];

                    return $data;
                }, $results['data']);
            }

            $results['draw'] = $filters['draw'];
            $results['recordsFiltered'] = $this->getRepository()->getCustomerProductListFilterCount($filters);
            $results['recordsTotal'] = $this->getRepository()->getCustomerProductListAllCount();
        } else {
            if ($isSelect === true) {
                $totalRecords = $this->getRepository()->getCustomerProductListAllCount();
                $results = $this->getRepository()->getCustomerProductList($filters, $order = [], $totalRecords, $offset = 0);
            } else {
                $results = $this->getRepository()->getCustomerProductList($filters);
            }
            
            $jwt = $this->jwtService->generate([]);

            $results = array_map(function ($record) use ($jwt) {
                try {
                    $record['balance'] = $this->integrationFactory->getIntegration($record['product']['code'])
                        ->getBalance($jwt, $record['userName']);
                } catch (\Exception $ex) {
                    $record['balance'] = 0;
                }
                return $record;
            }, $results);
        }

        return $results;
    }


    public function suspend(CustomerProduct $customerProduct)
    {
        $customerProduct->suspend();
        $integration = $this->integrationFactory->getIntegration($customerProduct->getProduct()->getCode());
        $jwt = $this->jwtService->generate([ 'roles' => ['ROLE_ADMIN'] ]);
        $integration->updateStatus($jwt, $customerProduct->getUsername(), false);
        $this->getRepository()->save($customerProduct);
    }

    public function activate(CustomerProduct $customerProduct): CustomerProductActivatedEvent
    {
        $customerProduct->activate();
        $customer = $customerProduct->getCustomer();
        $integration = $this->integrationFactory->getIntegration($customerProduct->getProduct()->getCode());
        $jwt = $this->jwtService->generate([ 'roles' => ['ROLE_ADMIN'] ]);
        $integration->updateStatus($jwt, $customerProduct->getUsername(), true);
        $this->getRepository()->save($customerProduct);

        $event = new CustomerProductActivatedEvent($customerProduct);
        // relogin if pinnacle
        if ($customerProduct->isPinnacle()) {
            $details = $integration->auth($customer->getPinUserCode(), ['locale' => $customer->getLocale()]);
            $event->setDetails($details);
        }

        return $event;
    }

    public function canSyncToCustomerProduct(string $syncId, string $customerProductId): bool
    {
        $repository = $this->getRepository();

        $customerProduct = $repository->getSyncedMemberProduct($syncId);
        if ($customerProduct instanceof CustomerProduct && ((string) $customerProduct->getId() === $customerProductId)) {
            return true;
        }

        if ($customerProduct instanceof CustomerProduct) {
            return false;
        }

        return true;
    }

    public function getRouter(): RouterInterface
    {
        return $this->router;
    }
}
