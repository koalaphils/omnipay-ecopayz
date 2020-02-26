<?php

namespace ProductBundle\Manager;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Manager\AbstractManager;
use AppBundle\Exceptions\FormValidationException;
use DbBundle\Entity\Product;

class ProductManager extends AbstractManager
{
    public function handleForm(Form $form, Request $request)
    {
        $productData = $request->get('Product');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $productRequest = $form->getData();

            $product = $productRequest->getProduct() ?? new Product();
            $product->setCode($productRequest->getCode());
            $product->setName($productRequest->getName());
            $product->setUrl($productRequest->getUrl());
            $product->setIsActive($productRequest->getIsActive());
            $this->getRepository()->save($product);
            if ($productRequest->isCommissionIsUpdated()) {
                $commission = $productRequest->getProductCommission();
                $commission->preserveOriginal();
                if ($commission->getResourceId() === null) {
                    $commission->setProduct($product);
                }
                $commission->setCommission($productRequest->getCommission());
                $this->getEventDispatcher()->dispatch(\DbBundle\Listener\VersionableListener::VERSIONABLE_SAVE, new \AppBundle\Event\GenericEntityEvent($commission));
            } else {
                $commission = $productRequest->getProductCommission();
                $commission->setCommission($productRequest->getCommission());
                $commission->setProduct($product);
                $commission->preserveOriginal();

                $this->getEventDispatcher()->dispatch(\DbBundle\Listener\VersionableListener::VERSIONABLE_SAVE, new \AppBundle\Event\GenericEntityEvent($commission));
            }

            return $product;
        }

        throw new FormValidationException($form);
    }

    public function getProductList($filters = null)
    {
        $results = [];
        try {
            if (array_get($filters, 'datatable', 0)) {
                $filters['excludeAcWallet'] = true;
                if (false !== array_get($filters, 'search.value', false)) {
                    $filters['search'] = $filters['search']['value'];
                }

                $orders = (!array_has($filters, 'order')) ? [['column' => 'p.createdAt', 'dir' => 'desc']] : $filters['order'];
                $results['data'] = $this->getProductCommissions($this->getRepository()->getProductList($filters, $orders));

                if (array_get($filters, 'route', 0)) {
                    $results['data'] = array_map(function ($data) {
                        $data['product'] = $data;
                        $data['routes'] = [
                            'update' => $this->getRouter()->generate('product.update_page', ['id' => $data['product']['id']]),
                            'view' => $this->getRouter()->generate('product.view_page', ['id' => $data['product']['id']]),
                            'save' => $this->getRouter()->generate('product.save', ['id' => $data['product']['id']]),
                        ];

                        return $data;
                    }, $results['data']);
                }
                $results['draw'] = $filters['draw'];
                $results['recordsFiltered'] = $this->getRepository()->getProductListFilterCount($filters);
                $results['recordsTotal'] = $this->getRepository()->getProductListAllCount();
            } elseif (array_get($filters, 'select2', 0)) {
                $filters['isActive'] = true;
                $results['items'] = array_map(function ($product) use ($filters) {
                    return [
                        'id' => $product[array_get($filters, 'idColumn', 'id')],
                        'text' => $product['name'],
                        'details'=> $product['details']
                    ];
                }, $this->getRepository()->getProductList($filters));
                $results['recordsFiltered'] = $this->getRepository()->getProductListFilterCount($filters);
            } else {
                $results = $this->getProductCommissions($this->getRepository()->getProductList($filters));
                $results = array_map(function ($result) {
                    $result['text'] = $result['name'] . ' (' . $result['code'] . ')';
                    $result['toSync'] =  false;
                    
                    return $result;
                }, $results);
            }
        } catch (\Exception $e) {
            $errorMessage = 'Line error: ' . $e->getCode() . ' Message: ' . $e->getMessage();
            $results = ['error' => $errorMessage];
        }

        return $results;
    }

    public function getProductCommissions(array $products): array
    {
        $productIds = [];
        foreach ($products as $product) {
            $productIds[] = $product['id'];
        }

        $result = $this->getProductCommissionRepository()->getProductCommissionOfProducts($productIds);
        $commissions = [];
        foreach ($result as $record) {
            $commissions[$record['product']['id']] = $record;
        }

        $products = array_map(function ($product) use ($commissions) {
            if (isset($commissions[$product['id']])) {
                $product['commission'] = $commissions[$product['id']]['commission'];
            } else {
                $product['commission'] = 0;
            }

            return $product;
        }, $products);

        return $products;
    }

    protected function getRepository(): \DbBundle\Repository\ProductRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:Product');
    }

    private function getProductCommissionRepository(): \DbBundle\Repository\ProductCommissionRepository
    {
        return $this->getDoctrine()->getRepository(\DbBundle\Entity\ProductCommission::class);
    }

    private function getEventDispatcher(): \Symfony\Component\EventDispatcher\EventDispatcherInterface
    {
        return $this->getContainer()->get('event_dispatcher');
    }
}
