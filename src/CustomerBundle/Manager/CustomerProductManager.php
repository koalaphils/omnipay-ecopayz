<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace CustomerBundle\Manager;

use DbBundle\Entity\CustomerProduct;
use DbBundle\Repository\CustomerProductRepository;
use PinnacleBundle\Service\PinnacleService;
use Symfony\Component\Routing\RouterInterface;

class CustomerProductManager
{
    /**
     * @var PinnacleService
     */
    private $pinnacleService;

    /**
     * @var CustomerProductRepository
     */
    private $memberProductRepository;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(PinnacleService $pinnacleService, CustomerProductRepository $memberProductRepository, RouterInterface $router)
    {
        $this->pinnacleService = $pinnacleService;
        $this->memberProductRepository = $memberProductRepository;
        $this->router = $router;
    }

    /**
     * @return \DbBundle\Repository\CustomerProductRepository
     */
    public function getRepository()
    {
        return $this->memberProductRepository;
    }

    public function getCustomerProductList($filters = null, bool $isSelect = false)
    {
        $status = true;
        $pinnacleProduct = $this->pinnacleService->getPinnacleProduct();
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
            $results = array_map(function ($record) use($pinnacleProduct) {
                if ($pinnacleProduct->getId() == $record['product']['id']) {
                    try {
                        $pinnaclePlayer = $this->pinnacleService->getPlayerComponent()->getPlayer($record['userName']);
                        $record['balance'] = $pinnaclePlayer->availableBalance();
                    } catch (PinnacleException $exception) {
                        $record['balance'] = "Unable to fetch balance";
                    } catch (PinnacleError $exception) {
                        $record['balance'] = "Unable to fetch balance";
                    }
                }

                return $record;
            }, $results);
        }

        return $results;
    }

    public function preventMultipleActiveSkypeBettingProduct(CustomerProduct $customerProduct)
    {
        $repo =  $this->getRepository();
        $customerProducts = $repo->getCustomerProducts($customerProduct->getCustomer());
        
        if ($customerProduct->isSkypeBetting() && $this->hasActiveSkypeBettingProduct($customerProducts) && $this->doesNotHaveOneActiveSkypeBettingProduct($customerProducts)) {
            $customerProduct->suspend();
        }
    }

    public function doesNotHaveOneActiveSkypeBettingProduct($customerProducts): bool
    {
        $countActiveSkypeBettingProduct = 0;
        foreach ($customerProducts as $product) {
            if ($product->isActiveSkypeBettingProduct()) {
                $countActiveSkypeBettingProduct++;
            }
        }

        return $countActiveSkypeBettingProduct > 1 ? true : false;
    }
    
    public function hasActiveSkypeBettingProduct($customerProducts): bool
    {
        foreach ($customerProducts as $product) {
            if ($product->isActiveSkypeBettingProduct()) {
                return true;
            }
        }

        return false;
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

    private function getRouter(): RouterInterface
    {
        return $this->router;
    }
}
