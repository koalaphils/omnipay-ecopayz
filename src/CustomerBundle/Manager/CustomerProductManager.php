<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace CustomerBundle\Manager;

use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\CustomerProduct;

class CustomerProductManager extends AbstractManager
{
    /**
     * @return \DbBundle\Repository\CustomerProductRepository
     */
    public function getRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:CustomerProduct');
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

            // Get Balance from BA
            $brokerageManger = $this->get('brokerage.brokerage_manager');
            foreach ($results['data'] as &$datum) {
                if (isset($datum['details']['brokerage']['sync_id'])) {
                    $syncId = $datum['details']['brokerage']['sync_id'];
                    $balance = $brokerageManger->getCustomerBalance($syncId);
                    $datum['ba_balance'] = $balance;
                }
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

}
