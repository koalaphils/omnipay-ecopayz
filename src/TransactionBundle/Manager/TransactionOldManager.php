<?php

namespace TransactionBundle\Manager;

use AppBundle\ValueObject\Number;
use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\Gateway;
use DbBundle\Entity\Interfaces\GatewayInterface;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\CustomerProduct;

class TransactionOldManager extends AbstractManager
{
    public function getType($type, $inverse = false)
    {
        if (!$inverse) {
            $types = [
                'deposit' => Transaction::TRANSACTION_TYPE_DEPOSIT,
                'withdraw' => Transaction::TRANSACTION_TYPE_WITHDRAW,
                'transfer' => Transaction::TRANSACTION_TYPE_TRANSFER,
                'bonus' => Transaction::TRANSACTION_TYPE_BONUS,
                'p2p_transfer' => Transaction::TRANSACTION_TYPE_P2P_TRANSFER,
                'commission' => Transaction::TRANSACTION_TYPE_COMMISSION,
                'revenue_share' => Transaction::TRANSACTION_TYPE_REVENUE_SHARE,
                'adjustment' => Transaction::TRANSACTION_TYPE_ADJUSTMENT,
                'debit_adjustment' => Transaction::TRANSACTION_TYPE_DEBIT_ADJUSTMENT,
                'credit_adjustment' => Transaction::TRANSACTION_TYPE_CREDIT_ADJUSTMENT,
            ];
        } else {
            $types = [
                Transaction::TRANSACTION_TYPE_DEPOSIT => 'deposit',
                Transaction::TRANSACTION_TYPE_WITHDRAW => 'withdraw',
                Transaction::TRANSACTION_TYPE_TRANSFER => 'transfer',
                Transaction::TRANSACTION_TYPE_BONUS => 'bonus',
                Transaction::TRANSACTION_TYPE_P2P_TRANSFER => 'p2p_transfer',
                Transaction::TRANSACTION_TYPE_COMMISSION => 'commission',
                Transaction::TRANSACTION_TYPE_REVENUE_SHARE => 'revenue_share',
                Transaction::TRANSACTION_TYPE_ADJUSTMENT => 'adjustment',
                Transaction::TRANSACTION_TYPE_DEBIT_ADJUSTMENT => 'debit_adjustment',
                Transaction::TRANSACTION_TYPE_CREDIT_ADJUSTMENT => 'credit_adjustment',
            ];
        }

        return array_get($types, $type);
    }
    
    public function getAdjustmentType(string $type): string
    {
        return $this->getType($type . '_adjustment');
    }

    public function getList($filters = null)
    {
        $status = true;
        $results = [];

        if (array_get($filters, 'datatable', 0)) {
            if (false !== array_get($filters, 'search.value', false)) {
                $filters['search'] = $filters['search']['value'];
            }

            $results['data'] = $this->getRepository()->getList($filters);
            if (array_get($filters, 'route', 0)) {
                $results['data'] = array_map(function ($data) {
                    $data = [
                        'transaction' => $data,
                    ];
                    $data['routes'] = [
                        'update' => $this->getRouter()->generate('transaction.update_page', ['type' => $this->getType($data['transaction']['type'], true), 'id' => $data['transaction']['id']]),
                    ];

                    return $data;
                }, $results['data']);
            }
            $results['draw'] = $filters['draw'];
            $results['recordsFiltered'] = $this->getRepository()->getListFilterCount($filters);
            $results['recordsTotal'] = $this->getRepository()->getListAllCount();
        } else {
            $results = $this->getRepository()->getList($filters);
        }

        return $results;
    }

    public function updateCounter($to, $from = null)
    {
        $counter = $this->_getSettingManager()->getSetting('counter');
        if ($from !== null && $to != Transaction::TRANSACTION_STATUS_START) {
            $this->_getSettingManager()->updateSetting('counter.status.' . $from, array_get($counter, 'status.' . $from, 0) - 1);
        }

        $this->_getSettingManager()->updateSetting('counter.status.' . $to, array_get($counter, 'status.' . $to, 0) + 1);
    }

    public function processTransactionSummary(Transaction &$transaction): void
    {
        $sumProduct = new Number(0);
        $sumWithdrawProduct = new Number(0);
        $sumDepositProduct = new Number(0);
        $totalCustomerFee = new Number(0);
        $totalCompanyFee = new Number(0);
        $currencies = [];

        // zimi-check !== null
        if ($transaction->getCustomer()->getCurrency() !== null) {
            $currencies[$transaction->getCustomer()->getCurrency()->getId()] = $transaction->getCustomer()->getCurrency();
        }

        foreach ($transaction->getSubTransactions() as $subTransaction) {
            /* @var $subTransaction SubTransaction */
            $sumProduct = $sumProduct->plus($subTransaction->getAmount());
            if ($subTransaction->getType() == Transaction::TRANSACTION_TYPE_WITHDRAW) {
                $sumWithdrawProduct = $sumWithdrawProduct->plus($subTransaction->getAmount());
            } elseif ($subTransaction->getType() == Transaction::TRANSACTION_TYPE_DEPOSIT
                || $subTransaction->getType() == Transaction::TRANSACTION_TYPE_DWL
            ) {
                $sumDepositProduct = $sumDepositProduct->plus($subTransaction->getAmount());
            }

            // $totalCustomerFee = $totalCustomerFee->plus(array_get($subTransaction->getFees(), 'customer_fee', 0));
            $totalCompanyFee = $totalCompanyFee->plus(array_get($subTransaction->getFees(), 'company_fee', 0));
            if ($subTransaction->getCustomerProduct() instanceof CustomerProduct) {
                $currencies[$subTransaction->getCustomerProduct()->getCustomer()->getCurrency()->getId()] = $subTransaction->getCustomerProduct()->getCustomer()->getCurrency();
            }
        }

        $baseCurrency = $this->getCurrencyRepository()->find($this->_getSettingManager()->getSetting('currency.base'));
        foreach ($transaction->getSubTransactions() as &$subTransaction) {
            $currency = $currencies[$transaction->getCustomer()->getCurrency()->getId()];
            if ($subTransaction->getCustomerProduct() instanceof CustomerProduct) {
                $toCurrency = $currencies[$subTransaction->getCustomerProduct()->getCustomer()->getCurrency()->getId()];
            } else {
                $toCurrency = $currency;
            }
            $subTransaction->setDetail('baseCurrency', $baseCurrency->getCode());
            $subTransaction->setDetail('currency', $currency->getCode());
            $subTransaction->setDetail('rate', $currency->getRate());
            $subTransaction->setDetail('toCurrency', $toCurrency->getCode());
            $subTransaction->setDetail('toRate', $toCurrency->getRate());
            $converted = currency_exchangerate($subTransaction->getAmount(), $currency->getRate(), $toCurrency->getRate());
            $subTransaction->setDetail('convertedAmount', $converted);
        }

        $customerFeeFromTransaction = array_get($transaction->getFees(), 'customer_fee', 0);
        if (!is_null($customerFeeFromTransaction)) {
            $totalCustomerFee = $totalCustomerFee->plus($customerFeeFromTransaction);
        }
        $companyFeeFromTransaction = array_get($transaction->getFees(), 'company_fee', 0);
        if (!is_null($companyFeeFromTransaction)) {
            $totalCompanyFee = $totalCompanyFee->plus($companyFeeFromTransaction);
        }
        $expression = $this->_getSettingManager()->getSetting('transaction.equations.' . $this->getType($transaction->getType(), true));
        $values = [
            'sum_products' => $sumProduct . '',
            'sum_withdraw_products' => $sumWithdrawProduct->toString(),
            'sum_deposit_products' => $sumDepositProduct->toString(),
            'total_customer_fee' => $totalCustomerFee->toString(),
            'total_company_fee' => $totalCompanyFee->toString(),
            'company_fee' => $transaction->getFee('company_fee', 0),
            'customer_fee' => $transaction->getFee('customer_fee', 0),
        ];

        $totalAmount = $this->processEquation(array_get($expression, 'totalAmount.equation'), array_get($expression, 'totalAmount.variables'), $values);
        $customerAmount = $this->processEquation(array_get($expression, 'customerAmount.equation'), array_get($expression, 'customerAmount.variables'), $values);

        $transaction->setDetail('summary', $values + [
                'total_amount' => $totalAmount->toString(),
                'customer_amount' => $customerAmount->toString(),
            ]);

        $transaction->setAmount($totalAmount->toString());
        $transaction->setFee('total_customer_fee', $totalCustomerFee->toString());
        $transaction->setFee('total_company_fee', $totalCompanyFee->toString());

        if ($transaction->getGateway()) {
            $transaction->setImmutablePaymentGatewayFormula();
        }

        $customerGroups = [];

        foreach ($transaction->getCustomer()->getGroups() as $customerGroup) {
            $customerGroups[] = [
                'id' => $customerGroup->getId(),
                'name' => $customerGroup->getName(),
            ];
        }

        if (empty($customerGroups)) {
            $defaultCustomerGroup = $this->getCustomerGroupRepository()->getDefaultGroup();
            $customerGroups[] = [
                'id' => $defaultCustomerGroup->getId(),
                'name' => $defaultCustomerGroup->getName(),
            ];
        }

        $transaction->setDetail('customer.groups', $customerGroups);
    }

    /**
     * Process end transaction.
     *
     * @param Transaction $transaction
     */
    public function endTransaction(&$transaction)
    {
        $subTransactions = $transaction->getSubTransactions();
        $customerProducts = [];
        $customers = [];
                
        // zimi - call API pinnacle        
        $transactionStatus = $transaction->getStatus();        
        $id = $transaction->getId();
        $query = 'select * from transaction where transaction_id = ' . $id;        
        $em = $this->getDoctrine()->getManager();
        $qu = $em->getConnection()->prepare($query);
        $qu->execute();
        $res = $qu->fetchAll()[0];
        $amount = $res['transaction_amount'];        
        $customer_id = $res['transaction_customer_id'];
        $query = 'select c.customer_pin_user_code as userCode from user u join customer c on c.customer_user_id = u.user_id where c.customer_id = ' . $customer_id;
        $qu = $em->getConnection()->prepare($query);
        $qu->execute();
        $res = $qu->fetchAll()[0];
        $userCode = $res['userCode'];
        
        // Amount value should be two decimal places
        $amount = number_format((float)$amount, 2, '.', '');                
                
        // zimi - withdraw transaction
        $pin_url = '';        
        if ($transaction->isDeposit()){
            $pin_url = 'http://47.254.197.223:9000/api/pinnacle/deposit';              
        }

        if ($transaction->isWithdrawal()){
            $pin_url = 'http://47.254.197.223:9000/api/pinnacle/withdraw';                   
        }

        if ($transactionStatus == Transaction::TRANSACTION_STATUS_END) {
            $headers = [
                'Content-type: application/json'
            ];            
            
            $pdata = json_encode(array('userCode' => $userCode, 'amount'=> $amount));
            $curl = curl_init();            
            curl_setopt_array($curl, array(
            CURLOPT_HTTPHEADER => $headers,
               CURLOPT_RETURNTRANSFER => 1,
               CURLOPT_SSL_VERIFYPEER => 0,
               CURLOPT_URL => $pin_url,
               CURLOPT_POST => 1,
               CURLOPT_POSTFIELDS => $pdata 
            ));
                    
            $response = curl_exec($curl);                        
            $res = json_decode($response);            
            $res = json_decode($res);            
            
            // pin response null
            if (is_null($res)) {
                return json_encode(['error' => true, 'error_code' => 500, 'message' => 'PIN response null']);
            }

            if (array_key_exists('code', $res)) {
                $res_code = $res->code;
                $res_message = $res->message;   
                $transaction->setStatus(Transaction::TRANSACTION_STATUS_ACKNOWLEDGE);
                                
                return json_encode($res);
                
            }            
        }

        if ($transaction->isDeposit()) {            
            $transaction->getCustomer()->setEnabled();            
            // DbBundle\Entity\Customer
            $cus = $transaction->getCustomer();
            $cus->setBalance($amount);            
            
            // zimi#006b4669            
            $this->getRepository()->save($cus);
        }
        
        if ($transaction->isDeposit() || $transaction->isWithdrawal() || $transaction->isBonus()) {
            if ($transaction->getGateway()) {
                $this->processPaymentGatewayBalance($transaction);
                $this->save($transaction->getGateway());
            }
        }
    }

    /**
     * @param Transaction $transaction
     */
    public function processPaymentGatewayBalance(&$transaction)
    {
        $gateway = $transaction->getGateway();
        $details = $gateway->getDetails();
        $methods = array_get($details, 'methods.' . $this->getType($transaction->getType(), true));
        if ($transaction->isBonus()) {
            $vars = [['var' => 'x', 'value' => 'total_amount']];
            $equation = $gateway->getBalance() . '-x';
        } else {
            $vars = array_get($methods, 'variables', []);
            $equation = $gateway->getBalance() . array_get($methods, 'equation');
        }

        $predefineValues = $transaction->getDetail('summary', []);
        $variables = [];
        foreach ($vars as $var) {
            $variables[$var['var']] = $var['value'];
        }
        $oldBalance = $gateway->getBalance();
        $newBalance = $this->processEquation($equation, $variables, $predefineValues) . '';
        $gatewayTotal = (new Number($newBalance))->minus($oldBalance);
        $transaction->setGatewayComputedAmount($gatewayTotal->__toString());
        $transaction->getGateway()->setBalance($newBalance);
        $this->auditGateway($transaction, $gateway, $oldBalance, $newBalance);
    }

    /**
     * Process summary.
     *
     * @param Transaction $transaction
     */
    public function voidTransaction(&$transaction)
    {
        if (!$transaction->isVoided()) {
            $transaction->setIsVoided(true);
            // Sub Transaction
            $subTransactions = $transaction->getSubTransactions();
            foreach ($subTransactions as $subTransaction) {
                /* @var $subTransaction SubTransaction */
                $customerProduct = $subTransaction->getCustomerProduct();
                $customerProductBalance = new Number($customerProduct->getBalance());
                $subTransactionAmount = $subTransaction->getDetail('convertedAmount', $subTransaction->getAmount());

                if ($subTransaction->getType() == Transaction::TRANSACTION_TYPE_DEPOSIT) {
                    $customerProductBalance = $customerProductBalance->minus($subTransactionAmount);
                    $customerProduct->setBalance($customerProductBalance . '');
                } elseif ($subTransaction->getType() == Transaction::TRANSACTION_TYPE_WITHDRAW) {
                    $customerProductBalance = $customerProductBalance->plus($subTransactionAmount);
                    $customerProduct->setBalance($customerProductBalance . '');
                }

                // $this->getRepository()->save($customerProduct);
            }

            // Payment Gateway
            $gateway = $transaction->getGateway();
            if ($gateway) {
                if ($transaction->isBonus()) {
                    $method = ['equation' => '-x', 'variables' => [['var' => 'x', 'value' => 'total_amount']]];
                } else {
                    $method = $gateway->getDetail('methods.' . $this->getType($transaction->getType(), true));
                }
                $equation = array_get($method, 'equation');

                switch ($equation[0]) {
                    case '+':
                        $equation[0] = '-';
                        break;
                    case '-':
                        $equation[0] = '+';
                        break;
                    case '*':
                        $equation[0] = '/';
                        break;
                    case '/':
                        $equation[0] = '*';
                        break;
                }

                $equation = $gateway->getBalance() . $equation;

                $variables = [];
                foreach (array_get($method, 'variables') as $var) {
                    $variables[$var['var']] = $var['value'];
                }
                $predefineValues = $transaction->getDetail('summary', []);
                $newBalance = $this->processEquation($equation, $variables, $predefineValues) . '';
                $this->auditGateway($transaction, $gateway, $gateway->getBalance(), $newBalance);
                $transaction->getGateway()->setBalance($newBalance);
                $this->getRepository()->save($transaction->getGateway());
            }
        }
    }

    protected function getCustomerGroupRepository(): \DbBundle\Repository\CustomerGroupRepository
    {
        return $this->getDoctrine()->getRepository(\DbBundle\Entity\CustomerGroup::class);
    }

    /**
     * @return \DbBundle\Repository\TransactionRepository
     */
    public function getRepository(): \DbBundle\Repository\TransactionRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:Transaction');
    }

    /**
     * @return \DbBundle\Repository\GatewayRepository
     */
    public function getGatewayRepository(): \DbBundle\Repository\GatewayRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:Gateway');
    }

    /**
     * @return \DbBundle\Repository\CustomerRepository
     */
    public function getCustomerRepository(): \DbBundle\Repository\CustomerRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:Customer');
    }

    /**
     * @return \DbBundle\Repository\TransactionLogRepository
     */
    public function getTransactionLogRepository(): \DbBundle\Repository\TransactionLogRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:TransactionLog');
    }

    /**
     * @return \DbBundle\Repository\CurrencyRepository
     */
    public function getCurrencyRepository(): \DbBundle\Repository\CurrencyRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:Currency');
    }

    public function getStatus($status)
    {
        $setting = $this->getSettingManager()->getSetting('transaction.status.' . $status);

        return $setting;
    }

    public function getAction($status, $action)
    {
        $action = $this->getSettingManager()->getSetting("transaction.status.$status.actions.$action");

        return $action;
    }

    protected function auditGateway(Transaction $transaction, Gateway $gateway, $oldBalance, $newBalance)
    {
        $gatewayLogManager = $this->getGatewayLogManager();

        $oldBalance = new Number($oldBalance);
        $finalAmount = $oldBalance->minus($newBalance)->toFloat();

        $gatewayLog = $gatewayLogManager->createLog(
            $finalAmount < 0 ? GatewayInterface::OPERATION_ADD : GatewayInterface::OPERATION_SUB,
            abs($finalAmount),
            $oldBalance,
            $transaction->getNumber(),
            $transaction->getCurrency(),
            $gateway,
            $gateway->getPaymentOptionEntity(),
            [
                'identifier' => $transaction->getId(),
                'reference_class' => get_class($transaction),
                'type' => $transaction->getType(),
            ]
        );

        $gatewayLogManager->save($gatewayLog);
    }

    protected function processEquation($equation, $variables = [], $predefineValues = []): Number
    {
        $variables = array_map(function ($value) use ($predefineValues) {
            return array_get($predefineValues, $value, $value);
        }, $variables);

        $value = Number::parseEquation($equation, $variables, true);

        return $value;
    }

    /**
     * Get event dispatcher.
     *
     * @return \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected function getEventDispatcher()
    {
        return $this->get('event_dispatcher');
    }

    /**
     * Get Setting Manager.
     *
     * @return \AppBundle\Manager\SettingManager
     */
    protected function getSettingManager()
    {
        return $this->get('app.setting_manager');
    }

    /**
     * Get Setting Manager.
     *
     * @return \AppBundle\Manager\SettingManager
     */
    protected function _getSettingManager()
    {
        return $this->get('app.setting_manager');
    }

    protected function getGatewayLogManager()
    {
        return $this->get('gateway_log.manager');
    }
}
