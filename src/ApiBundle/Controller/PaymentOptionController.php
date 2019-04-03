<?php

namespace ApiBundle\Controller;

use DbBundle\Entity\Transaction;
use DbBundle\Repository\PaymentOptionRepository;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use DbBundle\Collection\Collection;
use PaymentBundle\Component\Blockchain\Rate;

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

     /**
     * @ApiDoc(
     *  description="Get Bitcoin Adjustment from Cache (Deposit)",
     * )
     */
    public function getCachedBitcoinAdjustmentAction()
    {
        $bitcoinManager = $this->get('payment.bitcoin_manager');
        $bitcoinAdjustment = $bitcoinManager->createBitcoinAdjustment(Rate::RATE_EUR);

        return new JsonResponse($bitcoinAdjustment->toArray(Transaction::TRANSACTION_TYPE_DEPOSIT));
    }

    /**
     * @ApiDoc(
     *  description="Get Bitcoin Adjustment from Cache (Withdrawal)",
     * )
     */
    public function getCachedBitcoinWithdrawalAdjustmentAction()
    {
        $bitcoinManager = $this->get('payment.bitcoin_manager');
        $bitcoinWithdrawalAdjustment = $bitcoinManager->createBitcoinAdjustment(Rate::RATE_EUR, Transaction::TRANSACTION_TYPE_WITHDRAW);

        return new JsonResponse($bitcoinWithdrawalAdjustment->toArray(Transaction::TRANSACTION_TYPE_WITHDRAW));
    }

    /**
     * @ApiDoc(
     *     description="Get member process payment option types",
     *     section="Member",
     *     views={"piwi"},
     *     headers={
     *         { "name"="Authorization", "description"="Bearer <access_token>" }
     *     }
     * )
     */
    public function getMemberProcessPaymentOptionsAction(PaymentOptionRepository $paymentOptionRepository): View
    {
        $customer = $this->getUser()->getCustomer();
        $paymentOptionTypes = $paymentOptionRepository->getMemberProcessedPaymentOption($customer->getId());
        $processPaymentOptionTypes = [];
        foreach ($paymentOptionTypes as $paymentOption) {
            $processPaymentOptionTypes[$paymentOption->getCode()] = true;
        }

        return $this->view($processPaymentOptionTypes);
    }
    
    protected function getPaymentOptionRepository(): \DbBundle\Repository\PaymentOptionRepository
    {
        return $this->getRepository(\DbBundle\Entity\PaymentOption::class);
    }
}
