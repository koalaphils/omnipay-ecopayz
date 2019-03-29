<?php

namespace TransactionBundle\Manager;

use AppBundle\Exceptions\FormValidationException;
use AppBundle\ValueObject\Number;
use DbBundle\Entity\CommissionPeriod;
use DbBundle\Entity\CustomerGroup;
use DbBundle\Entity\CustomerGroupGateway;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\TransactionLog;
use DbBundle\Entity\Notification;
use DbBundle\Entity\User;
use DbBundle\Repository\CommissionPeriodRepository;
use DbBundle\Repository\MemberRunningCommissionRepository;
use Doctrine\ORM\Query;
use PDO;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use AppBundle\Manager\AbstractManager;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use TransactionBundle\Event\TransactionProcessEvent;
use TransactionBundle\Exceptions\TransactionNotExistsException;
use TransactionBundle\Form\TransactionType;
use AppBundle\Helper\WampHelper;

class TransactionManager extends TransactionOldManager
{
    private $memberRunningCommissionRepository;
    private $commissionPeriodRepository;

    public function findTransactions(Request $request)
    {
        $filters = $request->get('filters', []);
        $orders = $request->get('orders', [['column' => 'transaction.date', 'dir' => 'desc']]);
        $limit = (int) $request->get('length', 10);
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $limit;

        $isDataTableTransaction = (bool) $request->get('isDataTableTransaction', false);
        if ($isDataTableTransaction) {
            $filters['isDataTableTransaction'] = true;
        }

        $customerId = $request->query->get('customerId');
        if (!is_null($customerId)) {
            $filters['customerId'] = $customerId;
        }


        if (array_has($filters, 'search') && trim($filters['search']) !== '') {
            if (!$this->isTransactionNumber($filters['search'])) {
                $customerIds = [];
                $filters['searchCustomerIds'] = $this->getCustomerRepository()->getCustomerIds(['search' => $filters['search']]);
                if (!empty(array_get($filters['searchCustomerIds'], 0))) {
                    $customerIds = $filters['searchCustomerIds'];
                }

                $filters['searchTransactionIds'] = $this->getSubtransactionRepository()->getTransactionIds(['search' => $filters['search']], $customerIds);
            }
        }


        $transactions = $this->getRepository()->findTransactions($filters, $orders, $limit, $offset, [], \Doctrine\ORM\Query::HYDRATE_OBJECT);
        $recordsFiltered = $this->getRepository()->getTotal($filters);
        $recordsTotal = $this->getRepository()->getTotal();

        $result = [
            'records' => $transactions,
            'recordsFiltered' => $recordsFiltered,
            'recordsTotal' => $recordsTotal,
            'limit' => $limit,
            'page' => $page,
        ];

        return $result;
    }

    /**
     * prevents large sql results from being loaded in the memory and consuming all the server's memory
     */
    private function doNotLoadSqlResultsInMemoryAllAtOnce(): void
    {
        $em = $this->getDoctrine()->getManager();
        $pdo = $em->getConnection()->getWrappedConnection();
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    }

    /**
     *
     * print data as csv as they are received from the database
     * this will be streamed to the client to prevent loading all the data into php's memory
     *
     * @param Request $request
     */
    public function printCsvReport(Request $request): void
    {
        $this->doNotLoadSqlResultsInMemoryAllAtOnce();

        $separator = ',';
        $statusLabels = $this->getStatusOptions();
        $transaction = new Transaction();
        $transactionTypeLabels = $transaction->getTypesText();

        $filters = $request->get('filters', []);
        $products = [];
        if (isset($filters['product']) && !empty($filters['product'])) {
            $products = $this->getDoctrine()->getRepository(\DbBundle\Entity\Product::class)->getProductByIds($filters['product']);

        }

        $query = $this->getTransactionsForExportQuery($request);
        $iterableResult = $query->iterate($parameters = null, $hydrationMode = Query::HYDRATE_ARRAY);

        $csvReport = '';
        $csvReport .= $this->getFilterDetailsForCsv($filters, $products);
        echo $csvReport;

        $csvReport = '';
        $csvReport .= 'Number, Date, Member,  Product (Username), Payment Option (Email), Currency, Member Fee, Company Fee, Amount, Status, Type';
        $csvReport .= "\n";
        echo $csvReport;

        foreach ($iterableResult as $row) {
            $csvReport = '';
            $transaction = $row;
            $transaction = array_pop($transaction);
            $csvReport .= $transaction['number'] . ($transaction['wasCreatedFromAms'] ? ' (AMS)' : '') . $separator;
            $csvReport .= $transaction['date']->format('Y-m-d H:i:s') . $separator;
            $csvReport .= '"' . $transaction['customerFullName'] . '"'. $separator;
            $csvReport .= '"' . ltrim(ltrim(trim(trim(trim(str_replace('()', '' ,$transaction['productsAndUsernames'])), ',')),',')) . '"' . $separator;
            $csvReport .= '"' . $transaction['immutablePaymentOptionDataOnTransaction'] . '"' . $separator;
            $csvReport .= $transaction['currencyCode'] . $separator;
            $csvReport .= $transaction['memberFee'] . $separator;
            $csvReport .= $transaction['companyFee'] . $separator;
            $csvReport .= $transaction['amount'] . $separator;
            if ($transaction['isVoided']) {
                $csvReport .= 'Voided' . $separator;
            } else {
                $csvReport .= ucwords($statusLabels[$transaction['statusId']]) . $separator;
            }
            $csvReport .= ucwords($transactionTypeLabels[$transaction['typeId']]);
            $csvReport .= "\n";

            echo $csvReport;
        }
    }

    public function printGatewayTransactionsCsvReport(Request $request): void
    {
        $this->doNotLoadSqlResultsInMemoryAllAtOnce();

        $separator = ',';
        $transaction = new Transaction();
        $transactionTypeLabels = $transaction->getTypesText();

        $filters = $request->get('filters', []);
        $query = $this->getTransactionsForExportQuery($request);
        $iterableResult = $query->iterate($parameters = null, $hydrationMode = Query::HYDRATE_ARRAY);

        $csvReport = '';
        $csvReport .= $this->getFilterDetailsForCsv($filters);
        echo $csvReport;

        $csvReport = '';
        $csvReport .= 'Number, Date, Member, Currency, Amount, Type';
        $csvReport .= "\n";
        echo $csvReport;

        foreach ($iterableResult as $row) {
            $csvReport = '';
            $transaction = $row;
            $transaction = array_pop($transaction);
            $csvReport .= $transaction['number'] . ($transaction['wasCreatedFromAms'] ? ' (AMS)' : '') . $separator;
            $csvReport .= $transaction['date']->format('Y-m-d H:i:s') . $separator;
            $csvReport .= $transaction['customerFullName'] . $separator;
            $csvReport .= $transaction['currencyCode'] . $separator;
            $csvReport .= $transaction['amount'] . $separator;
            $csvReport .= ucwords($transactionTypeLabels[$transaction['typeId']]);
            $csvReport .= "\n";

            echo $csvReport;
        }
    }

    private function getFilterDetailsForCsv(Array $filters = [], Array $products = []): string
    {
        $csvReport = '';
        $statusLabels = $this->getStatusOptions();

        if (array_has($filters, 'from')) {
            $csvReport .= "From : " . $filters['from'] . " \n";
        }
        if (array_has($filters, 'to')) {
            $csvReport .= "To : " . $filters['to'] . " \n";
        }
        if (isset( $filters['product']) && !empty($filters['product'])) {
            $tmp = "Product : \"";
            foreach ($products as $productId) {
                $tmp .= $productId['name'] .', ';
            }
            $tmp = trim(trim(trim($tmp),','));
            $csvReport .= $tmp ."\"\n";
        }
        if (isset( $filters['paymentOption']) && !empty($filters['paymentOption'])) {
            $csvReport .= "Payment Option : \"" . implode(',', $filters['paymentOption']) . "\" \n";
        }
        if (isset( $filters['types']) && !empty($filters['types'])) {
            $tmp = "Types : \"";
            foreach ($filters['types'] as $transactionType) {
                $tmp .= ucwords($transactionType) . ' ,';
            }
            $tmp = trim(trim(trim($tmp),','));
            $csvReport .= $tmp . "\" \n";
        }
        if (isset( $filters['source']) && !empty($filters['source'])) {
            $tmp = "Source : \"";
            foreach ($filters['source'] as $transactionSource) {
                if ($transactionSource == 'member') {
                    $tmp .= 'Member Site, ';
                } elseif ($transactionSource == 'admin') {
                    $tmp .= 'Backoffice, ';
                }
            }
            $tmp = trim(trim(trim($tmp),','));
            $csvReport .= $tmp . "\" \n";
        }
        if (isset( $filters['status']) && !empty($filters['status'])) {
            $tmp = "Status : \"";
            $statuses = '';
            foreach (array_unique($filters['status']) as $transactionStatusId) {
                if (!isset($statusLabels[$transactionStatusId])) {
                    $statuses .= ucwords($transactionStatusId) .', ';
                    continue;
                }
                $statuses .= ucwords($statusLabels[$transactionStatusId]) .', ';
            }
            $tmp = $tmp . trim(trim(trim($statuses),','));
            $csvReport .= $tmp . "\"\n";
        }
        $csvReport .= "\n";

        return $csvReport;
    }



    /**
     * this has a limit of 1,000,000 rows for now
     *
     * @param Request $request
     * @return Query
     */
    private function getTransactionsForExportQuery(Request $request): Query
    {
        $filters = $request->get('filters', []);
        $orders = $request->get('orders', [['column' => 'transaction.date', 'dir' => 'desc']]);
        $limit = (int) $request->get('length', 1600000);
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $limit;
        $filters['isUsingExport'] = true;
        
        if (array_has($filters, 'search') && trim($filters['search']) !== '') {
            if (!$this->isTransactionNumber($filters['search'])) {
                $customerIds = [];
                $filters['searchCustomerIds'] = $this->getCustomerRepository()->getCustomerIds(['search' => $filters['search']]);
                if (!empty(array_get($filters['searchCustomerIds'], 0))) {
                    $customerIds = $filters['searchCustomerIds'];
                }

                $filters['searchTransactionIds'] = $this->getSubtransactionRepository()->getTransactionIds(['search' => $filters['search']], $customerIds);
            }
        }
        $query = $this->getRepository()->getTransactionsForExportQuery($filters, $orders, $limit, $offset);

        return $query;
    }

    public function isTransactionNumber($transactionNumber) : bool
    {
        // deposit // withdrawals // transfer //p2p-transfer
        if (preg_match('/^\d{8}-\d{6}-\d{1,3}$/', $transactionNumber)) {
            return true;
        }
        // bet transaction number
        if (preg_match('/^\d{8}-\d{6}-\d{1,3}-\d{1,99}$/', $transactionNumber)) {
            return true;
        }
        // dwl transaction number
        if (preg_match('/^\d{8}-\d{6}-\d{1,3}-\d{1,99}-\d{1,99}$/', $transactionNumber)) {
            return true;
        }

        return false;
    }

    public function handleFormTransaction(Form $form, Request $request)
    {
        $form->handleRequest($request);

        // zimi-comment:  && $form->isValid()
        if ($form->isSubmitted()) {
            $transaction = $form->getData();
            $transaction->retainImmutableData();
            $transaction->autoSetPaymentOptionType();
            $btn = $form->getClickedButton();

            $action = array_get($btn->getConfig()->getOption('attr', []), 'value', 'process');
            
            if ($request->request->has('toCustomer')) {
                $transaction->setDetail('toCustomer', $request->request->get('toCustomer'));
            }

            $this->processTransaction($transaction, $action);

            return $transaction;
        }

        throw new FormValidationException($form);
    }
    
    public function insertTransactionLog($transaction, $old_status, $created_by){
        $log = new TransactionLog();
        $log->setTransactionId($transaction->getId());
        $log->setOldStatus($old_status);
        $log->setNewStatus($transaction->getStatus());
        $log->setIsVoided($transaction->getIsVoided());
        $log->setCreatedBy($created_by);
        $log->setCreatedAt(new \Datetime());
        
        $this->getTransactionLogRepository()->save($log);
    }
    
    public function insertNotificationByTransaction($transaction){
        $type_text = $transaction->getTypeText();
        $status_text = $transaction->getIsVoided() ? "Voided" : ucfirst($transaction->getStatusText());
        $style = $this->getNotificationRepository()->getStyleText($transaction->getStatus());
        $number = $transaction->getNumber();
        if($transaction->getIsVoided()){
            $status_text = "Voided";
            $style = "red";
        }
        $message = "<b>Transaction $number</b> $type_text has been <b>$status_text</b>";
        
        $user = $this->getUserRepository()->findByCustomerID($transaction->getCustomerId());
        if($user){
            $item = new Notification();
            $item->setUserID($user->getId());
            $item->setMessage($message);
            $item->setStyle($style);
            $item->setCreatedAt(new \Datetime());
            $this->save($item);  
        }
        
        $url = "notification_" . $transaction->getCustomerId();
        WampHelper::publish($url, ['msg' => $message]);
    }

    public function getCountPerStatus(): array
    {
        $exp = new ExpressionLanguage();
        $exp->addFunction(\Symfony\Component\ExpressionLanguage\ExpressionFunction::fromPhp('date'));
        $exp->addFunction(\Symfony\Component\ExpressionLanguage\ExpressionFunction::fromPhp('strtotime'));

        $statuses = $this->getSettingManager()->getSetting('transaction.status');
        $statuses['voided'] = [];
        $statusesSetting = $this->getSettingManager()->getSetting('transaction.statusCounter');

        $statusesConditions = [];
        foreach ($statuses as $statusId => $statusInfo) {
            $statusCondition = array_get($statusesSetting, $statusId, ['conditions' => [], 'params' => []]);
            $params = $statusCondition['params'];
            foreach ($params as $param => $value) {
                $statusCondition['params'][$param] = $exp->evaluate($value);
            }
            $statusesConditions[$statusId] = $statusCondition;
        }

        return $this->getRepository()->getTotalTransactionPerStatuses($statusesConditions);
    }

    public function updateBitcoinPaymentOption(Transaction $transaction): void {
        if ($transaction->isPaymentBitcoin()) {
            $paymentOption = $transaction->getPaymentOption();
            if(!is_null($paymentOption)  && strcasecmp($paymentOption->getBitcoinField(), $transaction->getBitcoinAddress()) !== 0){

                $paymentOption->setAccountId($transaction->getBitcoinAddress());

                $this->getEntityManager()->persist($paymentOption);
                $this->getEntityManager()->flush($paymentOption);
            }
        }
    }

    public function processTransaction(Transaction &$transaction, $action, bool $fromCustomer = false)
    {        
        // zimi-check $transaction !== null        
        if ($transaction !== null && $transaction->getId()) {
            if ($action === 'void') {
                $action = ['label' => 'Void', 'status' => 'void'];
            } elseif ($action === 'decline') {
                $action = ['label' => 'Decline', 'status' => Transaction::TRANSACTION_STATUS_DECLINE];
            } elseif ($action === 'confirm') {
                $action = ['label' => 'Confirm', 'status' => Transaction::TRANSACTION_STATUS_ACKNOWLEDGE];
                $transaction->setBitcoinConfirmation(3);
                $transaction->setBitcoinAcknowledgedByUser(true);
            } else {
                $action = $this->getAction($transaction->getStatus(), $action, $transaction->getTypeText());
            }
        } else {
            $action = ['label' => 'Save', 'status' => Transaction::TRANSACTION_STATUS_START];
        }

        /* @var $subtransaction \DbBundle\Entity\SubTransaction */
        foreach ($transaction->getSubTransactions() as $subtransaction) {
            if ($subtransaction->getDetail('hasFee', false)) {
                $subtransaction->setFee('customer_fee', $transaction->getFee('customer_fee'));
            } else {
                $subtransaction->removeFee('customer_fee');
            }
        }
        
        // zimi        
        $amount = $transaction->getAmount();

        $event = new TransactionProcessEvent($transaction, $action, $fromCustomer);
        try {
            $this->getRepository()->beginTransaction();
            
            $this->getEventDispatcher()->dispatch('transaction.saving', $event);
            if ($event->isPropagationStopped()) {
                return;
            }

            $this->getEventDispatcher()->dispatch('transaction.pre_save', $event);
            $this->getRepository()->reconnectToDatabase();
            $this->updateBitcoinPaymentOption($transaction);
            // zimi- DbBundle\Entity\Transaction            
            $eventTransaction = $event->getTransaction();            
            $eventTransaction->setAmount($amount);   

            $this->getRepository()->save($eventTransaction);
            $this->getRepository()->commit();
            $this->getEventDispatcher()->dispatch('transaction.saved', $event);

        } catch (\Exception $e) {
            $this->getRepository()->rollback();

            throw $e;
        }
    }

    public function getGatewaysByTransaction(Transaction $transaction): \Doctrine\Common\Collections\ArrayCollection
    {
        $gateways = new \Doctrine\Common\Collections\ArrayCollection();
        if ($transaction->getCustomer() === null) {
            return $gateways;
        }

        foreach ($transaction->getSubTransactions() as $subtransaction) {
            if (!is_numeric($subtransaction->getAmount())) {
                $subtransaction->setAmount(0);
            }
        }

        $this->processTransactionSummary($transaction);
        if ($transaction->getCustomer() !== null) {
            $transaction->setCurrency($transaction->getCustomer()->getCurrency());
        }
        //get groups of customer
        $customerGroups = $transaction->getCustomer()->getGroups();
        if ($customerGroups->isEmpty()) {
            $defaultGroup = $this->getDoctrine()->getRepository(CustomerGroup::class)->getDefaultGroup();
            $transaction->getCustomer()->getGroups()->add($defaultGroup);
            $customerGroups = $transaction->getCustomer()->getGroups();
        }
        $this->getDoctrine()->getManager()->initializeObject($customerGroups);
        $groupIds = [];
        foreach ($customerGroups as $customerGroup) {
            $groupIds[] = $customerGroup->getId();
        }

        //get gateways within groups
        $customerGroupGateways = $this->getDoctrine()->getRepository(CustomerGroupGateway::class)->findByGroup($groupIds);
        $expressionLang = new ExpressionLanguage();

        $serializer = $this->getContainer()->get('jms_serializer');
        $serializerContext = new \JMS\Serializer\SerializationContext();
        $serializerContext->setGroups(['transaction_exp']);
        $serializerContext->setSerializeNull(true);
        $serializedTransaction = json_decode($serializer->serialize($transaction, 'json', $serializerContext));
        foreach ($customerGroupGateways as $customerGroupGateway) {
            $exp = "true === (" . $customerGroupGateway->getConditions() . ")";
            $evaluation = $expressionLang->evaluate($exp, ['transaction' => $serializedTransaction, 'customer' => $serializedTransaction->customer]);
            if ($evaluation) {
                $gateways->add($customerGroupGateway->getGateway());
            }
        }

        return $gateways;
    }

    public function createForm(Transaction $transaction, $forSave = true, $args = [])
    {
        if ($transaction->getId()) {
            return $this->createUpdateForm($transaction, $forSave, $args);
        }

        return $this->createNewForm($transaction, $forSave, $args);
    }

    public function getAction($status, $action, $type = null): array
    {
        if ($type !== null) {
            $path = 'transaction.type.workflow.' . $type . '.' . $status . '.actions.' . $action;
            $typeAction = $this->getSettingManager()->getSetting($path, null);
            if ($typeAction !== null) {
                return $typeAction;
            }
        }
        $action = $this->getSettingManager()->getSetting("transaction.status.$status.actions.$action");

        return $action;
    }

    public function addVoidedStatus($statuses = []): array
    {
        $voided = Transaction::TRANSACTION_STATUS_VOIDED;

        return $statuses + [$voided => ['label' => ucfirst($voided)]];
    }

    public function revertCustomerProductBalance(\DbBundle\Entity\CustomerProduct $customerProduct, SubTransaction $subTransaction): void
    {
        $customerProductBalance = new Number($customerProduct->getBalance());
        $subTransactionAmount = $subTransaction->getDetail('convertedAmount', $subTransaction->getAmount());

        if ($subTransaction->isDeposit() || $subTransaction->isDWL()) {
            $customerProductBalance = $customerProductBalance->minus($subTransactionAmount);
            $customerProduct->setBalance($customerProductBalance->__toString());
        } elseif ($subTransaction->isWithdrawal()) {
            $customerProductBalance = $customerProductBalance->plus($subTransactionAmount);
            $customerProduct->setBalance($customerProductBalance->__toString());
        }
    }

    /**
     * @return array id => label key value pairs
     */
    public function getStatusOptions()
    {
        $statuses = $this->getSettingManager()->getSetting('transaction.status');
        $statusIdAndLabels = [];
        foreach ($statuses as $statusId => $statusData) {
            $statusIdAndLabels[$statusId] = strtolower($statusData['label']);
        }
        return $statusIdAndLabels;
    }

    public function getCommissionPeriodForTransaction(int $transactionId): CommissionPeriod
    {
        $memberRunningCommission = $this->getMemberRunningCommissionRepository()->findOneByCommissionTransaction($transactionId);
        $commissionPeriod = $this->getCommissionPeriodRepository()->findOneById($memberRunningCommission->getCommissionPeriodId());

        return $commissionPeriod;
    }

    public function setMemberRunningCommissionRepository(MemberRunningCommissionRepository $memberRunningCommissionRepository): void
    {
        $this->memberRunningCommissionRepository = $memberRunningCommissionRepository;
    }

    public function setCommissionPeriodRepository(CommissionPeriodRepository $commissionPeriodRepository): void
    {
        $this->commissionPeriodRepository = $commissionPeriodRepository;
    }

    protected function getEventDispatcher()
    {
        return $this->get('event_dispatcher');
    }

    private function getMemberRunningCommissionRepository(): MemberRunningCommissionRepository
    {
        return $this->memberRunningCommissionRepository;
    }

    private function getCommissionPeriodRepository(): CommissionPeriodRepository
    {
        return $this->commissionPeriodRepository;
    }

    private function createNewForm(Transaction $transaction, $forSave = true, $args = [])
    {
        $statusStart = Transaction::TRANSACTION_STATUS_START;
        $form = $this->getContainer()->get('form.factory')->create(TransactionType::class, $transaction, [
            'action' => $this->getRouter()->generate('transaction.save', ['type' => $this->getType($transaction->getType(), true)]),
            'actions' => [['label' => 'Save', 'status' => $statusStart, 'class' => 'btn-action btn-success']],
            'unmap' => $forSave ? $this->getFormUnmap($transaction, true) : [],
            'views' => $forSave ? [] : $this->getFormView($transaction, true),
            'validation_groups' => function () use ($transaction, $args) {
                if (array_has($args, 'validation_groups')) {
                    return $args['validation_groups'];
                }
                $groups = ['default'];
                if (in_array($transaction->getType(), [Transaction::TRANSACTION_TYPE_WITHDRAW, Transaction::TRANSACTION_TYPE_DEPOSIT])
                    && $this->getSettingManager()->getSetting('transaction.paymentGateway', 'customer-level') !== 'customer-level'
                ) {
                    $groups[] = 'withGateway';
                    $groups[] = 'withPaymentOption';
                }

                return $groups;
            },
            'hasAdjustment' => $transaction->hasAdjustment(),
        ]);

        if (($transaction->isBonus())
            ? array_get($this->getStatus($transaction->getStatus()), 'editBonusAmount', false)
            : false
        ) {
            foreach ($form->get('subTransactions')->all() as &$sub) {
                $sub->remove('type')->remove('customerProduct');
            }
        }

        return $form;
    }

    private function createUpdateForm(Transaction $transaction, $forSave = true, $args = [])
    {
        $actions = [];
        $isForVoidingOrDecline = array_get($args, 'isForVoidingOrDecline', false);

        if (!$transaction->isDwl() && $transaction->hasEnded() && !$transaction->isVoided()) {
            $actions['void'] =
                [
                    'label' => 'Void',
                    'class' => 'btn-danger',
                    'status' => 'void',
                ];
        } else if (!$transaction->isDwl() && $transaction->isTransactionPaymentBitcoin() && !$transaction->isVoided() && $transaction->getStatus() == Transaction::TRANSACTION_STATUS_START && $transaction->isDeposit()) {
            $actions['decline'] = ['label' => 'Decline', 'class' => 'btn-danger', 'status' => Transaction::TRANSACTION_STATUS_DECLINE];
            $actions['confirm'] = ['label' => 'Confirm', 'class' => 'btn-danger', 'status' => Transaction::TRANSACTION_STATUS_ACKNOWLEDGE];
            $isForVoidingOrDecline = true;
        } else {
            $actions = array_get($this->getStatus($transaction->getStatus()), 'actions', []);
        }

        $actions = array_map(function ($action) {
            $action['class'] = $action['class'] . ' btn-action';

            return $action;
        }, $actions);

        $unmap = $forSave ? $this->getFormUnmap($transaction, false) : [];
        $editGateway = $this->getSettingManager()->getSetting('transaction.status.' . $transaction->getStatus() . '.editGateway');
        if ($forSave && $editGateway) {
            $unmap['gateway'] = true;
        }

        $form = $this->getContainer()->get('form.factory')->create(TransactionType::class, $transaction, [
            'action' => $this->getRouter()->generate('transaction.save', ['type' => $this->getType($transaction->getType(), true), 'id' => $transaction->getId()]),
            'actions' => $actions,
            'unmap' => $unmap,
            'views' => $forSave ? [] : $this->getFormView($transaction, false),
            'addSubtransaction' => false,
            'isForVoidingOrDecline' => $isForVoidingOrDecline,
            'validation_groups' => function () use ($transaction, $args, $isForVoidingOrDecline) {
                if (array_has($args, 'validation_groups')) {
                    return $args['validation_groups'];
                }
                $groups = ['default', 'withDecimalPlacesValidation'];
                if (($transaction->isWithdrawal() || $transaction->isDeposit())
                    && $this->getSettingManager()->getSetting('transaction.paymentGateway', 'customer-level') !== 'customer-level'
                ) {
                    if ($transaction->isWithdrawal()) {
                        $groups[] = 'withCustomerFee';
                    }

                    if ($transaction->isInProgress() && !$transaction->isVoided() && !$isForVoidingOrDecline) {
                        $groups[] = 'withGateway';
                    }
                    $groups[] = 'withPaymentOption';

                }

                if ($transaction->isWithdrawal() || $transaction->isDeposit() || $transaction->isTransfer() || $transaction->isP2pTransfer() || $transaction->isBonus()) {
                    if ($isForVoidingOrDecline) {
                        $groups[] = 'isForVoidingOrDecline';
                    }
                }

                if ($transaction->hasDepositUsingBitcoin()) {
                    if (($key = array_search('withDecimalPlacesValidation', $groups)) !== false) {
                        unset($groups[$key]);
                    }
                    $groups[] = 'withBitcoin';
                }

                return $groups;
            },
            'isCommission' => $transaction->isCommission(),
            'hasAdjustment' => $transaction->hasAdjustment(),
        ]);

        if (($transaction->isBonus())
            ? array_get($this->getStatus($transaction->getStatus()), 'editBonusAmount', false)
            : false
        ) {
            foreach ($form->get('subTransactions')->all() as &$sub) {
                $sub->remove('type')->remove('customerProduct');
            }
        }

        return $form;
    }


    private function getFormUnmap(Transaction $transaction, $new = true)
    {
        if ($new) {
            return [];
        }



        $isDateMapped = $this->isDateFieldEditable($transaction);
        $isMapped = true;

        if ($transaction->isBonus()) {
            $isSubtransactionMapped = array_get($this->getStatus($transaction->getStatus()), 'editBonusAmount', false);
        } else {
            $isSubtransactionMapped = $this->isAmountFieldEditable($transaction);
        }

        return  [
            'number' => false,
            'customer' => false,
            'date' => $isDateMapped,
            'gateway' => false,
            'customerFee' => array_get($this->getStatus($transaction->getStatus()), 'editFees', false),
            'companyFee' => array_get($this->getStatus($transaction->getStatus()), 'editFees', false),
            'subTransactions' => [
                '__this' => $isSubtransactionMapped,
                'type' => $isMapped,
                'customerProduct' => $isMapped,
                'amount' => $isSubtransactionMapped,
            ],
            'bonus' => [
                '__this' => false,
                'id' => false,
                'subject' => false,
            ],
            'messages' => [
                'customer' => false,
            ],
        ];
    }

    private function getFormView(Transaction $transaction, $new = true)
    {
        if ($new) {
            return [];
        }

        $isDateReadOnly = !$this->isDateFieldEditable($transaction);

        if ($transaction->isBonus()) {
            $isSubtransactionReadOnly = !array_get($this->getStatus($transaction->getStatus()), 'editBonusAmount', false);
        } else {
            $isSubtransactionReadOnly = !$this->isAmountFieldEditable($transaction);
        }

        $isReadOnly = true;
        $formView = [
            'number' => $isReadOnly,
            'customer' => $isReadOnly,
            'date' => $isDateReadOnly,
            'customerFee' => !array_get($this->getStatus($transaction->getStatus()), 'editFees', false),
            'companyFee' => !array_get($this->getStatus($transaction->getStatus()), 'editFees', false),
            'subTransactions' => [
                'type' => $isSubtransactionReadOnly,
                'customerProduct' => $isSubtransactionReadOnly,
                'amount' => $isSubtransactionReadOnly,
                'hasFee' => $isReadOnly,
            ],
            'messages' => [
                'support' => $transaction->isEnd()
                    || $transaction->isDeclined(),
            ],
        ];

        if (!$transaction->isCommission()) {
            $formView = array_merge($formView, ['gateway' => !array_get($this->getStatus($transaction->getStatus()), 'editGateway', false)]);
        }

        return  $formView;
    }

    public function getTransactionStatus(): array
    {
        $status = $this->getSettingManager()->getSetting('transaction.status');
        $otherStatus = Transaction::getOtherStatus();

        foreach ($otherStatus as $data) {
            $status[$data] = [
                'label' => $this->getTranslator()->trans($data, [], 'TransactionBundle'),
            ];
        }

        return $status;
    }

    public function getNonPendingTransactionStatus(array $statuses): array
    {
        $statusToRemove = Transaction::getPendingStatus();
        foreach ($statusToRemove as $index => $key) {
            if (array_key_exists($key, $statuses)) {
                unset($statuses[$key]);
            }
        }

        return $statuses;
    }
    
    public function getTransactionById(int $id): Transaction
    {
        $transaction = $this->getRepository()->findById($id);

        return $transaction;
    }

    private function getSubtransactionRepository(): \DbBundle\Repository\SubTransactionRepository
    {
        return $this->getDoctrine()->getRepository(SubTransaction::class);
    }

    private function getNotificationRepository(): \DbBundle\Repository\NotificationRepository
    {
        return $this->getDoctrine()->getRepository(Notification::class);
    }

    private function getUserRepository(): \DbBundle\Repository\UserRepository
    {
        return $this->getDoctrine()->getRepository(User::class);
    }

    private function isAmountFieldEditable(Transaction $transaction) : bool
    {
        return array_get($this->getStatus($transaction->getStatus()), 'editAmount', false);
    }

    private function isDateFieldEditable(Transaction $transaction) : bool
    {
        return array_get($this->getStatus($transaction->getStatus()), 'editDate', false);
    }

    public function findLastTransactionDateByMemberId(int $memberId): ?\DateTimeInterface
    {
        return $this->getRepository()->findLastTransactionDateByMemberId($memberId);
    }

    public function generateTransactionNumber(string $type, string $suffix = ''): string
    {
        $date = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'));

        return $date->format('Ymd-His-') . generate_code(6, false, 'd') . '-' . $this->getType($type) . $suffix;
    }
}
