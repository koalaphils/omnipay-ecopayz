<?php

namespace ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use DbBundle\Collection\Collection;
use Symfony\Component\HttpFoundation\Response;
use DbBundle\Entity\PaymentOption;

class TransactionController extends AbstractController
{
    /**
     * @ApiDoc(
     *  description="Get customer transactions",
     *  filters={
     *      {"name"="search", "dataType"="string"},
     *      {"name"="limit", "dataType"="integer"},
     *      {"name"="orders[0][column]", "dataType"="array"},
     *      {"name"="orders[0][dir]", "dataType"="array"},
     *      {"name"="page", "dataType"="integer"},
     *      {"name"="from", "dataType"="date"},
     *      {"name"="to", "dataType"="date"},
     *      {"name"="interval", "dataType"="string"},
     *      {"name"="types", "dataType"="string"},
     *      {"name"="status"},
     *      {"name"="paymentOption", "dataType"="string"}
     *  }
     * )
     */
    public function customerTransactionsAction(Request $request)
    {
        $customer = $this->getUser()->getCustomer();

        $filters = ['customer' => $customer->getId()];

        $filters['limit'] = $request->get('limit', 10);
        $filters['offset'] = (((int) $request->get('page', 1))-1) * $filters['limit'];
        $orders = [];

        if ($request->query->has('orders')) {
            $orders = $request->query->get('orders');
        }

        if ($request->query->has('sort')) {
            $filters['sort'] = $request->query->get('sort');
        }

        if ($request->query->has('from')) {
            $filters['from'] = $request->query->get('from');
        }

        if ($request->query->has('to')) {
            $filters['to'] = $request->query->get('to');
        }

        if ($request->query->has('interval')) {
            $filters['interval'] = $request->query->get('interval');
        }

        if ($request->query->has('search')) {
            $filters['search'] = $request->query->get('search');
        }

        if ($request->query->has('types')) {
            $filters['types'] = explode(',', $request->query->get('types'));
        }

        if ($request->query->has('status')) {
            $filters['status'] = $request->query->get('status');
            if (!is_array($filters['status'])) {
                $filters['status'] = [$filters['status']];
            }
        }

        if ($request->query->has('paymentOption')) {
            $filters['paymentOption'] = $request->query->get('paymentOption');
        }

        $transactions = $this->getTransactionRepository()->filters($filters, $orders);
        $total = $this->getTransactionRepository()->getTotal(['customer' => $customer->getId()]);
        $totalFiltered = $this->getTransactionRepository()->getTotal($filters);
        $collection = new Collection($transactions, $total, $totalFiltered, $filters['limit'], $request->get('page', 1));

        $view = $this->view($collection);
        $view->getContext()->setGroups(['Default', 'API', 'subtransactions_group', 'items' => ['Default', 'API', 'subtransactions_group']]);

        return $view;
    }

    /**
     * @ApiDoc(
     *  description="Get specific transaction"
     * )
     */
    public function customerTransactionAction($id)
    {
        $customer = $this->getUser()->getCustomer();

        $transaction = $this->getTransactionRepository()->findByIdAndCustomer($id, $customer->getId());

        if ($transaction === null) {
            throw $this->createNotFoundException('Transaction not found');
        }

        $view = $this->view($transaction);
        $view->getContext()->setGroups(['Default', 'API', 'subtransactions_group']);

        return $view;
    }

    /**
     * @ApiDoc(
     *  description="Request deposit transaction",
     *  input={
     *      "class"="ApiBundle\Form\Transaction\TransactionType",
     *      "options"={"hasEmail"=true}
     *  }
     * )
     */
    public function depositTransactionAction(Request $request)
    {
        $customer = $this->getUser()->getCustomer();
        $transactionTemp = $transaction = $request->request->get('transaction', []);
        
        $paymentOptionType = $transaction['paymentOptionType'];
        $defaultGroup = ['Default', 'deposit'];
        $memberPaymentOptionId = !empty($transaction['paymentOption']) ? $transaction['paymentOption'] : null;
        unset($transaction['paymentOptionType']);
        unset($transaction['paymentOption']);
        
        $paymentOption = $this->getPaymentOptionRepository()->find($paymentOptionType);
        if ($paymentOption->hasRequiredField('email')) {
            $defaultGroup = array_merge($defaultGroup, ['withEmail']);
        }

        if ($paymentOption->hasUniqueField('email')) {
            $defaultGroup = array_merge($defaultGroup, ['withUniqueEmail']);
        }
        
        $tempTransactionModel = new \ApiBundle\Model\Transaction();
        $tempTransactionModel->setCustomer($customer);
        $tempTransactionModel->setPayment($paymentOption);
        $form = $this->createForm(\ApiBundle\Form\Transaction\TransactionType::class, $tempTransactionModel, [
            'validation_groups' => $defaultGroup,
            'hasEmail' => true,
        ]);
        $form->submit($transaction);

        if ($form->isSubmitted() && $form->isValid()) {
            if (is_null($memberPaymentOptionId)) {
                $memberPaymentOption = $this->getTransactionManager()->addCustomerPaymentOption($customer, $paymentOptionType, $transactionTemp);
            } else {
                $memberPaymentOption = $this->getCustomerPaymentOptionRepository()->find($memberPaymentOptionId);
            }
            $transactionModel = $form->getData();
            $transactionModel->setPaymentOption($memberPaymentOption);
            $transactionModel->setCustomer($customer);

            try {
                $transaction = $this->getTransactionManager()->handleDeposit($transactionModel);
                $transactionNumber = $transaction->getNumber();
                $response = [
                    'title' => 'Deposit Transaction',
                    'message' => $transactionNumber .' - Deposit is requested.',
                    'otherDetails' => ['type' => $transaction->getTypeText(), 'id' => $transaction->getId()]
                ];

                if (!$paymentOption->isPaymentEcopayz()) {
                    $this->getTransactionManager()
                        ->enableCustomerPaymentOption($transaction->getPaymentOptionOnTransaction()->getId())
                    ;

                    $this->getCustomerPaymentOptionRepository()
                        ->disableOldPaymentOption($transaction->getPaymentOptionOnTransaction()->getId(), $transaction->getCustomer()->getId(), $paymentOptionType)
                    ;
                }
                
                return new JsonResponse($response);
            } catch (\Payum\Core\Reply\HttpRedirect $e) {
                return $this->view(['url' => $e->getUrl()], Response::HTTP_OK, [
                    'X_IS_REDIRECT' => true,
                    'X_REDIRECT_URL' => $e->getUrl(),
                ]);
            }
        }

        return $this->view($form, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @ApiDoc(
     *  description="Request withdraw transaction",
     *  input={
     *      "class"="ApiBundle\Form\Transaction\TransactionType",
     *      "options"={"hasFee"=true, "hasTransactionPassword"=true}
     *  }
     * )
     */
    public function withdrawTransactionAction(Request $request)
    {
        $customer = $this->getUser()->getCustomer();
        $transaction = $request->request->get('transaction', []);
        $memberPaymentOption = $this->getCustomerPaymentOptionRepository()->find($transaction['paymentOption']);

        $tempTransactionModel = new \ApiBundle\Model\Transaction();
        $tempTransactionModel->setCustomer($customer);
        $tempTransactionModel->setPayment($memberPaymentOption);
        unset($transaction['paymentOption']);
        $form = $this->createForm(\ApiBundle\Form\Transaction\TransactionType::class, $tempTransactionModel, [
            'validation_groups' => ['Default', 'withdraw'],
            'hasFee' => true,
            'hasTransactionPassword' => true,
        ]);
        $form->submit($transaction);
        if ($form->isSubmitted() && $form->isValid()) {
            $transactionModel = $form->getData();
            $transactionModel->setCustomer($customer);
            $transactionModel->setPaymentOption($memberPaymentOption);
            $transaction = $this->getTransactionManager()->handleWithdraw($transactionModel);
            $transactionNumber = $transaction->getNumber();
            $response = [
                'title' => 'Withdrawal Transaction',
                'message' => $transactionNumber .' - Withdrawal is requested.',
                'otherDetails' => ['type' => $transaction->getTypeText(), 'id' => $transaction->getId()]
            ];

            return new JsonResponse($response);
        }

        return $this->view($form, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @ApiDoc(
     *  description="Request transfer",
     *  input={
     *      "class"="ApiBundle\Form\Transfer\TransferType",
     *      "options"={
     *          "isP2P"=false
     *      }
     *  }
     * )
     */
    public function transferTransactionAction(Request $request)
    {
        $customer = $this->getUser()->getCustomer();
        $transaction = $request->request->get('transfer');
        $tempTransactionModel = new \ApiBundle\Model\Transfer();
        $tempTransactionModel->setCustomer($customer);
        $form = $this->createForm(\ApiBundle\Form\Transfer\TransferType::class, $tempTransactionModel, [
            'validation_groups' => ['Default', 'transfer'],
        ]);
        $form->submit($transaction);

        if ($form->isSubmitted() && $form->isValid()) {
            $transactionModel = $form->getData();
            $transactionModel->setCustomer($customer);
            $transaction = $this->getTransactionManager()->handleTransfer($transactionModel);
            $transactionNumber = $transaction->getNumber();
            $response = [
                'title' => 'Product Wallet Transaction',
                'message' => $transactionNumber .' - Product wallet is requested.',
                'otherDetails' => ['type' => $transaction->getTypeText(), 'id' => $transaction->getId()]
            ];

            return new JsonResponse($response);
        }

        return $this->view($form, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @ApiDoc(
     *  description="Request p2p transfer",
     *  input={
     *      "class"="ApiBundle\Form\Transfer\TransferType",
     *      "options"={
     *          "isP2P"=true
     *      }
     *  }
     * )
     */
    public function p2pTransferTransactionAction(Request $request)
    {
        $customer = $this->getUser()->getCustomer();
        $transaction = $request->request->get('transfer');
        $tempTransactionModel = new \ApiBundle\Model\Transfer();
        $tempTransactionModel->setCustomer($customer);
        $form = $this->createForm(\ApiBundle\Form\Transfer\TransferType::class, $tempTransactionModel, [
            'isP2P' => true,
            'validation_groups' => ['Default', 'p2p'],
        ]);
        $form->submit($transaction);
        if ($form->isSubmitted() && $form->isValid()) {
            $transactionModel = $form->getData();
            $transactionModel->setCustomer($customer);
            $transaction = $this->getTransactionManager()->handleP2PTransfer($transactionModel);
            $transactionNumber = $transaction->getNumber();
            $response = [
                'title' => 'P2P Transfer Transaction',
                'message' => $transactionNumber .' - P2P transfer is requested.',
                'otherDetails' => ['type' => $transaction->getTypeText(), 'id' => $transaction->getId()]
            ];

            return new JsonResponse($response);
        }

        return $this->view($form, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function getTransactionRepository(): \ApiBundle\Repository\TransactionRepository
    {
        return $this->get('api.transaction_repository');
    }

    private function getTransactionManager(): \ApiBundle\Manager\TransactionManager
    {
        return $this->get('api.transaction_manager');
    }

    private function getCustomerPaymentOptionRepository(): \DbBundle\Repository\CustomerPaymentOptionRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:CustomerPaymentOption');
    }

    private function getPaymentOptionRepository(): \DbBundle\Repository\PaymentOptionRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:PaymentOption');
    }
}
