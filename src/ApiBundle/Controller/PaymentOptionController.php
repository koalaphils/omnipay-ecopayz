<?php

namespace ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use DbBundle\Collection\Collection;

class PaymentOptionController extends AbstractController
{
    /**
     * @ApiDoc(
     *  description="Payment Option List",
     *  filters={
     *      {"name"="is_active", "dataType"="boolean"},
     *      {"name"="search", "dataType"="string"},
     *      {"name"="limit", "dataType"="integer"},
     *      {"name"="page", "dataType"="integer"},
     *      {"name"="has_custom_ordering", "dataType"="boolean"},
     *  }
     * )
     */
    public function paymentOptionListAction(Request $request)
    {
        $filters = [];
        if ($request->get('search', null) !== null) {
            $filters['search'] = $request->get('search', '');
        }

        if ($request->get('is_active', null) !== null) {
            $filters['is_active'] = $request->get('is_active');
        }

        $orders = [];
        if ($request->get('has_custom_ordering', null) !== null) {
            $orders = [['column' => 'sort', 'dir' => 'ASC']];
        }
        
        $paymentOptions = $this->getPaymentOptionRepository()->filter($filters, $orders, $request->get('limit', 20), (((int) $request->get('page', 1))-1) * $request->get('limit', 20), \Doctrine\ORM\Query::HYDRATE_OBJECT);
        $total = $this->getPaymentOptionRepository()->total([]);
        $totalFiltered = $this->getPaymentOptionRepository()->total($filters);
        $collection = new Collection($paymentOptions, $total, $totalFiltered, $request->get('limit', 20), $request->get('page', 1));
        $view = $this->view($collection);
        $view->getContext()->setGroups(['Default', 'API', 'items' => ['Default', 'API']]);

        return $view;
    }
    
    protected function getPaymentOptionRepository(): \DbBundle\Repository\PaymentOptionRepository
    {
        return $this->getRepository(\DbBundle\Entity\PaymentOption::class);
    }
}
