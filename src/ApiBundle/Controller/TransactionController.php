<?php

namespace ApiBundle\Controller;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use DbBundle\Collection\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Response;
use DbBundle\Entity\PaymentOption;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\Customer;
use DbBundle\Entity\Product;
use Payum\Core\Reply\HttpRedirect;
use ApiBundle\Model\Bitcoin\BitcoinPayment;
use ApiBundle\Model\Bitcoin\BitcoinRateDetail;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Http\Client\Common\HttpMethodsClient;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use DbBundle\Repository\CustomerPaymentOptionRepository;

class TransactionController extends AbstractController
{
    public const USER_BALANCE_PINNACLE_API_URL = 'http://47.254.197.223:9000/api/pinnacle/users/balance';
    private $requestMessageFactory;
     
    public function __construct()
    {
        $this->requestMessageFactory = new GuzzleMessageFactory();
        $this->client = new HttpMethodsClient(new GuzzleAdapter(new GuzzleClient(['curl'=>[CURLOPT_SSL_VERIFYPEER => 0]])), $this->requestMessageFactory);    
    }

    // 2ec7e87b
    // $transactions  DbBundle\Entity\Transaction
    private function filterTrans($transactions){
        $trans = [];
        foreach($transactions as $t){
            $tran = [];
            $tran['number'] = $t->getNumber();
            $tran['type'] = $t->getTypeText();
            $tran['status'] = $t->getStatusText();

            // DbBundle\Entity\PaymentOption     
            $tran['paymentOptionType'] = $t->getPaymentOptionType()->getCode();            
            $tran['isVoided'] = $t->isVoided();
            $tran['amount'] = number_format((float)$t->getAmount(), 2, '.', '');

            // DateTime
            $tran['date'] = $t->getDate()->format('m/d/Y');
            $tran['currency'] = $t->getCurrency()->getCode();

            $trans[] = $tran;
            // array_push($trans, $tran);
        }

        return $trans;
    }
    
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
        $post = $request->request->all();
        
        $page = 1;         
        $customerRepository = $this->getDoctrine()->getManager()->getRepository(Customer::class);                
        
        // zimi - check $customer is null
        if ($customer === null) {
            $customer_id = $post['cid'];
            $customer = $customerRepository->findOneBy(['id' => $customer_id]);            
            $customer->setIsCustomer(true);        
        }

        /**filter**/
        $filters = ['customer' => $customer_id];
        $filters['limit'] = $request->get('limit', 1000);       
        $filters['offset'] = (((int) $page)-1) * $filters['limit'];        
        $orders = [["column" => "date", "dir" => "desc"]];
        
        if ($post['search'] != null) {
            $filters['search'] = $post['search'];
        }            
        if ($post['filter']['fromDate'] != null) {
            $filters['from'] = $post['filter']['fromDate'];
        }

        if ($post['filter']['toDate'] != null) {
            $filters['to'] = $post['filter']['toDate'];
        }

        if ($post['filter']['type'] != null && $post['filter']['type'] != -1) {
            $filters['types'] = $post['filter']['type'];
        }

        if ($post['filter']['status'] != null && $post['filter']['status'] != -1) {
            $filters['status'] = $post['filter']['status'];
        }
           
        if ($post['filter']['paymentOption'] != null && $post['filter']['paymentOption'] != -1) {
            $filters['paymentOption'] = $post['filter']['paymentOption'];
        }

        $transactions = $this->getTransactionRepository()->filters($filters, $orders);

        // zimi - data filter
        $transactions = $this->filterTrans($transactions);

        $total = $this->getTransactionRepository()->getTotal(['customer' => $customer_id]);
        $totalFiltered = $this->getTransactionRepository()->getTotal($filters);
        $collection = new Collection($transactions, $total, $totalFiltered, $filters['limit'], $page);

        // FOS\RestBundle\View\View
        $view = $this->view($collection);                
        // $view->getContext()->setGroups(['Default', 'API', 'subtransactions_group', 'items' => ['Default', 'API', 'subtransactions_group']]);

        return $view;
    }

    /**
     * @ApiDoc(
     *  description="Get specific transaction"
     * )     
     */
    public function customerTransactionAction($id, $cid=null)
    {        
        $query = 'select * from transaction where transaction_id = '. $id .' and transaction_customer_id = ' . $cid;
        $em = $this->getDoctrine()->getManager();
        $qu = $em->getConnection()->prepare($query);
        $qu->execute();
        $res = $qu->fetchAll();
        if (count($res) > 0) {
            $res = $res[0];            
        } else {            
            return new JsonResponse(['error' => true, 'error_message' => 'Transaction not found']);
        }

        return new JsonResponse(['error' => false, 'error_message' => '', 'data' => $res]);        
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
        $customerRepository = $this->getDoctrine()->getManager()->getRepository(Customer::class);
        $productRepository = $this->getDoctrine()->getManager()->getRepository(Product::class);

        $transactionTemp = $transaction = $request->request->get('transaction', []);        
        $amount = $transactionTemp['amount'];

        $paymentOptionType = $transaction['paymentOptionType'];
        $productCode = $transaction['product'];        
        $product = $productRepository->findByCode($productCode);
        $productName = $product[0]->getName();            

        $bitcoinRate = '';
        if ($paymentOptionType == 'bitcoin') {   
            $bitcoinRate = $transaction['currentRate'];            
        }        

        // zimi - check $customer is null
        if ($customer === null) {
            $customer_id = $transactionTemp['customer'];
            $customer = $customerRepository->findOneBy(['id' => $customer_id]);            
            $customer->setIsCustomer(true);        
        }
                
        $defaultGroup = ['Default', 'deposit', ''];
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

        // zimi-removed: && $form->isValid()
        if ($form->isSubmitted()) {
            if (is_null($memberPaymentOptionId)) {
                $memberPaymentOption = $this->getTransactionManager()->addCustomerPaymentOption($customer, $paymentOptionType, $transactionTemp);
            } else {
                $memberPaymentOption = $this->getCustomerPaymentOptionRepository()->find($memberPaymentOptionId);
            }

            $transactionModel = $form->getData();
            $transactionModel->setPaymentOption($memberPaymentOption);
            $transactionModel->setCustomer($customer);
            // zimi
            $transactionModel->setAmount($amount);
            $transactionModel->setBitcoinRate($bitcoinRate);
            $transactionModel->setProduct($product[0]);

            try {                
                $transaction = $this->getTransactionManager()->handleDeposit($transactionModel);
                $transactionNumber = $transaction->getNumber();                
                $response = [
                    'title' => 'Deposit Transaction',
                    'message' => $transactionNumber .' - Deposit is requested.',
                    'otherDetails' => ['type' => $transaction->getTypeText(), 'id' => $transaction->getId()]
                ];

                if ($paymentOption->isPaymentBitcoin()) {                    
                    $response['otherDetails']['address'] = $transaction->getBitcoinAddress();
                    if (is_null($memberPaymentOptionId)) {
                        $updatedMemberPaymentOption = $this->getTransactionManager()->updateMemberPaymentOptionAccountId($memberPaymentOption, $transaction->getBitcoinAddress());
                        $transaction = $this->getTransactionManager()->updateImmutablePaymentOptionOnBitcoinTransaction($transaction, $updatedMemberPaymentOption);
                    } else {
                        $memberPaymentOptionOnRecord = $this->getCustomerPaymentOptionRepository()->findByMemberPaymentOptionAccountId($memberPaymentOption->getCustomer()->getId(), $transaction->getBitcoinAddress());
                        if (!is_null($memberPaymentOptionOnRecord)) {
                            $memberPaymentOption = $memberPaymentOptionOnRecord;
                        }
                        
                        $updatedMemberPaymentOption = $this->getTransactionManager()->updateMemberPaymentOptionAccountId($memberPaymentOption, $transaction->getBitcoinAddress());
                        $transaction = $this->getTransactionManager()->updateImmutablePaymentOptionOnBitcoinTransaction($transaction, $updatedMemberPaymentOption);
                        $this->getTransactionManager()
                            ->enableCustomerPaymentOption($memberPaymentOption->getId())
                        ;

                        $this->getCustomerPaymentOptionRepository()
                            ->disableOldPaymentOption($memberPaymentOption->getId(), $transaction->getCustomer()->getId(), $paymentOptionType, Transaction::TRANSACTION_TYPE_DEPOSIT)
                        ;
                    }
                }

                if (!$paymentOption->isPaymentEcopayz() && !$paymentOption->isPaymentBitcoin()) {
                    $this->getTransactionManager()
                        ->enableCustomerPaymentOption($transaction->getPaymentOptionOnTransaction()->getId())
                    ;

                    $this->getCustomerPaymentOptionRepository()
                        ->disableOldPaymentOption($transaction->getPaymentOptionOnTransaction()->getId(), $transaction->getCustomer()->getId(), $paymentOptionType, Transaction::TRANSACTION_TYPE_DEPOSIT)
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

    private function verifyBalance($amount, $balance){        
        if ($amount > $balance) {
            return false;
        }

        return true;
    }

    private function verifySmsCode($data){        
        $full_phone = $data['phoneCode'] . substr($data['phoneNumber'], 1);

        if ($data['signupType'] == 0) {
            $query = 'SELECT sms_code_value FROM piwi_system_log_sms_code WHERE sms_code_customer_phone_number = \''.$full_phone.'\' order by sms_code_created_at desc limit 1';
        } else {
            $query = 'SELECT sms_code_value FROM piwi_system_log_sms_code WHERE sms_code_customer_email = \'' . $data['email'] . '\' order by sms_code_created_at desc limit 1';
        }

        $em = $this->getDoctrine()->getManager();
        $qu = $em->getConnection()->prepare($query);
        $qu->execute();
        $res = $qu->fetchAll();
        if (count($res) > 0) {
            $res = $res[0];            
        } else {
            return false;
        }
        
        if ($data['smsCode'] == $res['sms_code_value']) {
            return true;
        }

        return false;        
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

        
        // zimi - check $customer is null
        $amount = $transaction['amount'];
        $smsCode = $transaction['smsCode'];
        
        $customerRepository = $this->getDoctrine()->getManager()->getRepository(Customer::class);
        if ($customer === null) {
            $customer_id = $transaction['customer'];
            $customer = $customerRepository->findOneBy(['id' => $customer_id]);
            $availableBalance = $customer->getBalance();

            $availableBalance = number_format((float)$availableBalance, 2, '.', ''); 
            $amount = number_format((float)$amount, 2, '.', '');            
            
            // zimi-bypass
            if ($this->verifySmsCode($transaction) == false){
                return new JsonResponse(['error' => true, 'error_message' => 'your verification code is invalid']);
            }

            // if ($this->verifyBalance($amount, $availableBalance) == false){
            //     return new JsonResponse(['error' => true, 'error_message' => 'your balance is not enought']);
            // }   
            
            $customer->setIsCustomer(true);        
        }

        
        $memberPaymentOption = $this->getCustomerPaymentOptionRepository()->find($transaction['paymentOption']);
        $paymentOptionType = $transaction['paymentOptionType'];
        $memberPaymentOptionId = $transaction['paymentOption'] ?? 0;        
        $paymentOption = $this->getPaymentOptionRepository()->find($paymentOptionType);        
        $defaultGroup = ['Default', 'withdraw'];
        if ($paymentOption->isPaymentBitcoin() && $paymentOption->hasRequiredField('account_id')) {
            $hasAccountId = true;
            $defaultGroup = array_merge($defaultGroup, ['withAccountId']);
        }

        $tempTransactionModel = new \ApiBundle\Model\Transaction();
        $tempTransactionModel->setCustomer($customer);
        $tempTransactionModel->setPayment($memberPaymentOption);

        if (array_key_exists('bankDetails', $transaction)) {
            $tempTransactionModel->setBankDetails($transaction['bankDetails']);
        }

        // namdopin
        $customerBitcoinAddress = '';
        if (array_key_exists('bitcoinAddress', $transaction)) {            
            $customerBitcoinAddress = $transaction['bitcoinAddress'];            
        }

        unset($transaction['paymentOption']);
        $form = $this->createForm(\ApiBundle\Form\Transaction\TransactionType::class, $tempTransactionModel, [
            'validation_groups' => ['Default', 'withdraw'],
            'hasFee' => false,
            'hasTransactionPassword' => false,
        ]);
        $form->submit($transaction);
        // && $form->isValid()
        if ($form->isSubmitted()) {
            $transactionModel = $form->getData();
            if ($paymentOption->isPaymentBitcoin()) {                                
                $accountId = $customerBitcoinAddress;
                $memberPaymentOptionRequestedByAccountId = $this->getCustomerPaymentOptionRepository()->findByMemberPaymentOptionAccountId($customer->getId(), $accountId, Transaction::TRANSACTION_TYPE_WITHDRAW);
                if ($memberPaymentOptionId === 0 && !is_null($memberPaymentOptionRequestedByAccountId)) {
                    $memberPaymentOption = $memberPaymentOptionRequestedByAccountId;                    
                } elseif ($memberPaymentOptionId !== 0) {
                    $memberPaymentOption = $this->getCustomerPaymentOptionRepository()->find($memberPaymentOptionId);
                } else {
                    $memberPaymentOption = $this->getTransactionManager()->addCustomerPaymentOption($customer, $paymentOptionType, $transactionTemp, Transaction::TRANSACTION_TYPE_WITHDRAW);
                }
                $memberPaymentOption = $this->getTransactionManager()->updateMemberPaymentOptionAccountId($memberPaymentOption, $accountId);
            } else {
                $memberPaymentOption = $this->getCustomerPaymentOptionRepository()->find($memberPaymentOptionId);
            }
            $transactionModel->setCustomer($customer);
            $transactionModel->setPaymentOption($memberPaymentOption);

            // zimi
            $transactionModel->setAmount($amount);
            $transactionModel->setCustomerBitcoinAddress($customerBitcoinAddress);
            $transactionModel->setAccountId($customerBitcoinAddress);

            $transaction = $this->getTransactionManager()->handleWithdraw($transactionModel);
            
            if ($paymentOption->isPaymentBitcoin()) {
                $this->getTransactionManager()
                    ->enableCustomerPaymentOption($transaction->getPaymentOptionOnTransaction()->getId())
                ;

                $this->getCustomerPaymentOptionRepository()
                    ->disableOldPaymentOption($transaction->getPaymentOptionOnTransaction()->getId(), $customer->getId(), $paymentOptionType, Transaction::TRANSACTION_TYPE_WITHDRAW)
                ;
            }

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

    /**
     * @ApiDoc(
     *  description="Check wether a user has active bitcoin transaction",
     * )
     */
    public function checkForActiveBitcoinTransactionAction(Request $request)
    {
        $customer = $this->getUser()->getCustomer();
        $bitcoinManger = $this->get('payment.bitcoin_manager');
        $transaction = $bitcoinManger->findUserUnacknowledgedDepositBitcoinTransaction($customer);

        if ($transaction === null) {
            return new JsonResponse([]);
        }

        $view = $this->view($transaction);
        $groups = array_merge($view->getContext()->getGroups(), ['subtransactions_group', 'details', 'bitcoin_transaction']);
        $view->getContext()->setGroups($groups);

        return $view;
    }

    /**
     * @ApiDoc(
     *  description="Acknowledge a bitcoin transaction.",
     * )
     */
    public function acknowledgeBitcoinTransactionAction(Request $request)
    {
        $customer = $this->getUser()->getCustomer();
        $transactionRepository = $this->getTransactionRepository();
        $transaction = $transactionRepository->findUserUnacknowledgedDepositBitcoinTransaction($customer);

        if ($transaction === null) {
            return new Response('No active transaction to acknowledge.', Response::HTTP_BAD_REQUEST);
        }

        $this->getTransactionManager()->acknowledgeBitcoinTransaction($transaction);

        return new Response();
    }

    /**
     * @ApiDoc(
     *  description="Decline a bitcoin transaction.",
     * )
     */
    public function declineBitcoinTransactionAction(Request $request)
    {
        $customer = $this->getUser()->getCustomer();
        $transactionRepository = $this->getTransactionRepository();
        $transaction = $transactionRepository->findUserUnacknowledgedDepositBitcoinTransaction($customer);

        if ($transaction === null) {
            return new Response('No active transaction to decline.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $bitcoinManger = $this->get('payment.bitcoin_manager');
            $bitcoinManger->decline($transaction);

            return new Response();
        } catch(\DomainException $ex) {
            return new Response($ex->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function lockRateBitcoinTransactionAction(Request $request)
    {        
        $customer = $this->getUser()->getCustomer();
        $post = $request->request->all();        
        $customerRepository = $this->getDoctrine()->getManager()->getRepository(Customer::class);                        
        if ($customer === null) {
            $customer_id = $post['cid'];
            $customer = $customerRepository->findOneBy(['id' => $customer_id]);                        
        }
        
        $transactionRepository = $this->getTransactionRepository();
        $transaction = $transactionRepository->findUserUnacknowledgedDepositBitcoinTransaction($customer);

        if ($transaction === null) {            
            return new Response('No active transaction to lock.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $service = $this->getTransactionBitcoinLockRateService();
            $service->lockBitcoinTransaction($transaction);

            $tran_id = $transaction->getId(); 
            $tran_status = $transaction->getStatus(); 

            // [trans_id, trans_status]           
            return new JsonResponse([$tran_id, 'status' => $tran_status]);
            // return new Response();
        } catch(\DomainException $ex) {
            return new Response($ex->getMessage(), Response::HTTP_BAD_REQUEST);
        }  
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

    private function getTransactionBitcoinLockRateService(): \TransactionBundle\Service\TransactionBitcoinLockRateService
    {   
        return $this->get('transaction.lock.service');
    }

    private function buildPaymentForm(\Symfony\Component\Form\FormBuilder $formBuilder, string $payment): void
    {
        $class = 'ApiBundle\\Form\\Transaction\\Extension\\' . ucwords($payment) . 'Type';
        if (class_exists($class)) {
            $formBuilder->add('paymentDetails', $class, ['required' => true]);
            $paymentType = $this->getFormRegistry()->getType($class);
            if ($paymentType instanceof \Symfony\Component\Form\ResolvedFormTypeInterface) {
                $paymentType = $paymentType->getInnerType();
            }

            if ($paymentType instanceof \ApiBundle\Form\Transaction\Extension\TransactionFormExtensionInterface) {
                $paymentType->extendTransactionForm($formBuilder);
            }
        }
    }

    // zimi    
    public function getBitcoinRateAdjustmentAction()
    {                
        try {
            $service = $this->getTransactionBitcoinLockRateService();
            $service->lockBitcoinTransaction($transaction);

            return new Response();
        } catch(\DomainException $ex) {
            return new Response($ex->getMessage(), Response::HTTP_BAD_REQUEST);
        }  
    }

    // zimi
    public function getAvailableBalance($userCode = '')
    {            
        try {
            $url = self::USER_BALANCE_PINNACLE_API_URL;
            $res = $this->post($url, ["userCode" => $userCode], ["Content-Type" => "application/json"]);

            $res = $res->getBody()->getContents();
            $res = json_decode($res);
            $res = json_decode($res);

            if (gettype($res) === 'object') {
                return $res->availableBalance;
            }

            return null;

        } catch(\DomainException $ex) {
            return new Response($ex->getMessage(), Response::HTTP_BAD_REQUEST);
        }  
    }

    // zimi
    private function post(string $url, array $postData = [], array $headers = []): ResponseInterface
    {
        $request = $this->requestMessageFactory->createRequest('POST', $url, $headers, json_encode($postData));       
        $res = $this->client->sendRequest($request);
        
        return $res;
    }
}

