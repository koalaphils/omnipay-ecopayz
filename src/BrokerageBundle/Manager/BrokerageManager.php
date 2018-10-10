<?php

namespace BrokerageBundle\Manager;

use AppBundle\ValueObject\Number;
use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\Customer;
use DbBundle\Entity\Currency;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Psr\Log\LoggerInterface;
use CustomerBundle\Events;
use CustomerBundle\Event\CustomerProductSaveEvent;

class BrokerageManager extends AbstractManager
{
    const SYNC_STAUS_PENDING = 3;
    const SYNC_STAUS_PROCESSED = 0;

    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function syncFirstTransaction(Transaction $transaction)
    {
        $this->beginTransaction();
        if ($transaction instanceOf Transaction) {
            foreach ($transaction->getSubTransactions() as $subTransaction) {
                $customerProduct = $subTransaction->getCustomerProduct();
                $betadmin = $customerProduct->getProduct()->getDetail('betadmin');
                if (!$betadmin) {
                    continue;
                } elseif ($customerProduct->getDetail('brokerage')['sync_id']) {
                    continue;
                }

                $betadmintoSync = $betadmin['tosync'];
                $brokerageSyncStatus = $customerProduct->getDetail('brokerage.sync_status', BrokerageManager::SYNC_STAUS_PENDING);
                if ($betadmintoSync && ($brokerageSyncStatus !== BrokerageManager::SYNC_STAUS_PROCESSED)) {
                    $customer = $customerProduct->getCustomer();
                    $currency = $customer->getCurrency();
                    $socials = $customer->getSocials();

                    $fullName = explode(' ', $customer->getFullName());
                    $firstName = $fullName[0];
                    $lastName = implode(' ', array_slice($fullName, 1));

                    $params = [
                        'sync_id' => '',
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'skype' => $customerProduct->getUserName(),
                        'balance' => $subTransaction->getAmount(),
                        'currency' => $currency->getCode(),
                    ];
                    $apiUri = $this->syncCustomerUrl();
                    $response = json_decode($this->postData($apiUri, $params), true);

                    if ($response['sync_id']) {
                        $syncId = (int) $response['sync_id'];
                        $brokerageParams = [
                            "brokerage" => [
                                "sync_id" => $syncId,
                                "sync_status" => BrokerageManager::SYNC_STAUS_PROCESSED,
                                "details" => [
                                    "first_name" => $firstName,
                                    "last_name" => $lastName,
                                ],
                            ]
                        ];
                    } else {
                        $brokerageParams = [
                            "brokerage" => [
                                "sync_id" => null,
                                "sync_status" => BrokerageManager::SYNC_STAUS_PENDING,
                                "details" => null,
                            ]
                        ];
                    }

                    $customerProduct->setDetail("brokerage", $brokerageParams["brokerage"]);

                    $eventDispatcher = $this->get('event_dispatcher');
                    $eventDispatcher->dispatch(Events::EVENT_CUSTOMER_PRODUCT_SAVE, new CustomerProductSaveEvent($customerProduct));

                    $this->getCustomerProductRepository()->save($customerProduct);
                    $this->commit();
                }
            }
        }
    }

    public function searchName($search)
    {
        try {
            $apiUri = $this->getSearchBrokerageNameUri();

            $results = json_decode($this->getData($apiUri, ['search' => $search]), true);

            if ($results['status'] === "error" || empty($results['data'])) {
                return [
                    'items' => []
                ];
            }

            $results['items'] =  array_map(function($item) {
                $item['text'] = $item['firstname'] . ' ' .$item['lastname'];

                return $item;
            }, $results['data']);

            return $results;
        } catch (\Exception $e) {
            $errorMessage = 'Message: ' . $e->getMessage();

            throw new \Exception($errorMessage);
        }
    }

    public function unlinkWithSkypeBetting(CustomerProduct $customerProduct)
    {
        $response = ['message' => 'Unlink Skype betting successful.', 'success' => true];
        $this->beginTransaction();
        try {
            $customerProduct->unsetBrokerage();
            $this->getCustomerProductRepository()->save($customerProduct);

            $this->commit();

            $eventDispatcher = $this->get('event_dispatcher');
            $eventDispatcher->dispatch(Events::EVENT_CUSTOMER_PRODUCT_SAVE, new CustomerProductSaveEvent($customerProduct));
        } catch (\Exception $e) {
            $this->rollback();
            $errorMessage = 'Message: ' . $e->getMessage();
            $response = ['message' => $errorMessage, 'success' => false];
        }

        return $response;
    }

    public function syncPostBets(CustomerProduct $customerProduct, $bets, $isBetSettled = false)
    {
        $this->beginTransaction();
        try {
            $customer = $customerProduct->getCustomer();
            $currency = $customer->getCurrency();
            $currentBalance = $customerProduct->getBalance();
            $subTotalStake = 0;

            $expression = $this->getSettingManager()->getSetting('transaction.equations.bet');
            foreach ($bets as $bet) {
                if (!$isBetSettled) {
                    $transaction = $this->getTransactionRepository()->findOneByBetId($bet['bet_id']);

                    if ($transaction instanceOf Transaction) {
                        continue;
                    }
                }

                $this->createBetTransaction($customer, $currency, $customerProduct, $bet, $expression, $isBetSettled);

                $subTotalStake -= $bet['stake'];
            }

            $currentBalance = $this->updateCustomerProductBalance($currentBalance, $subTotalStake);
            $customerProduct->setBalance($currentBalance);

            $this->getCustomerProductRepository()->save($customerProduct);
            $this->commit();

            return [
                'success' => true,
                'message' => 'bet transactions successfully saved',
            ];
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function updateBetTransaction($params)
    {
        try {
            $transaction = $this->getTransactionRepository()->findOneByBetId($params['bet_id']);
            if ($transaction instanceOf Transaction) {
                $this->beginTransaction();
                $previousAmount = new Number($transaction->getAmount());
                $stake = (new Number($params['stake']))->__toString();
                $transaction->setAmount($stake);
                $transaction->setDetail('bet_details', $params['details']);

                $this->getTransactionRepository()->save($transaction);

                $subTransaction = $transaction->getSubTransactions()[0];
                $subTransaction->setAmount($stake);

                $this->getSubTransactionRepository()->save($subTransaction);

                $customerProduct = $subTransaction->getCustomerProduct();
                $currentBalance = $customerProduct->getBalance();

                $excessAmount = $previousAmount->minus($transaction->getAmount());
                $currentBalance = $this->updateCustomerProductBalance($currentBalance, $excessAmount);
                $customerProduct->setBalance($currentBalance);

                $this->getCustomerProductRepository()->save($customerProduct);
                $this->commit();

                $result = [
                    'success' => true,
                    'message' => 'bet transaction successfully updated',
                ];
            } else {
                $result = [
                    "success" => false,
                    "message" => "bet transaction not found",
                ];
            }

            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function updateBetTransactionDetailsByEvent($params)
    {
        try {
            $transactions = $this->getTransactionRepository()->findByBetEventId($params['event_id']);
            $this->beginTransaction();
             if (count($transactions) > 0) {
                foreach ($transactions as $transaction) {
                    if (!$transaction->getIsVoided()) {
                        $transaction->setDetail('bet_details', $params[$transaction->getBetId()]);

                        $this->getTransactionRepository()->save($transaction);
                    }
                }
                $result = [
                    'success' => true,
                    'message' => 'bet details transactions successfully updated',
                ];
            } else {
                $result = [
                    "success" => false,
                    "message" => "bet transactions not found",
                ];
            }
            $this->commit();

            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function voidBetTransactionsByEvent($params)
    {
        try {
            $transactions = $this->getTransactionRepository()->findByBetEventId($params['event_id']);
            $this->beginTransaction();
            if (count($transactions) > 0) {
                foreach ($transactions as $transaction) {
                    if (!$transaction->getIsVoided()) {
                        $transaction->setIsVoided(true);
                        $transaction->setDetail('reason', $params['reason'] . '');
                        $this->getTransactionRepository()->save($transaction);

                        $subTransaction = $transaction->getSubTransactions()[0];

                        $customerProduct = $subTransaction->getCustomerProduct();
                        $currentBalance = $customerProduct->getBalance();

                        $currentBalance = $this->updateCustomerProductBalance($currentBalance, $transaction->getAmount());
                        $customerProduct->setBalance($currentBalance);

                        $this->getCustomerProductRepository()->save($customerProduct);
                    }
                }

                $result = [
                    'success' => true,
                    'message' => 'bet transactions by event successfully voided',
                ];
            } else {
                $result = [
                    "success" => false,
                    "message" => "bet transactions not found",
                ];
            }
            $this->commit();

            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function voidBetTransaction($params)
    {
        try {
            $transaction = $this->getTransactionRepository()->findOneByBetId($params['bet_id']);
            $this->beginTransaction();
            if ($transaction instanceOf Transaction) {
                $transaction->setIsVoided(true);
                $transaction->setDetail('reason', $params['reason'] . '');
                $this->getTransactionRepository()->save($transaction);

                $subTransaction = $transaction->getSubTransactions()[0];

                $customerProduct = $subTransaction->getCustomerProduct();
                $currentBalance = $customerProduct->getBalance();

                $currentBalance = $this->updateCustomerProductBalance($currentBalance, $transaction->getAmount());
                $customerProduct->setBalance($currentBalance);

                $this->getCustomerProductRepository()->save($customerProduct);

                $result = [
                    'success' => true,
                    'message' => 'bet transaction successfully voided',
                ];
            } else {
                $result = [
                    'success' => false,
                    'message' => 'bet transaction not found',
                ];
            }
            $this->commit();

            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function getBrokerage() {
        return $this->getContainer()->getParameter('brokerage');
    }

    protected function getRepository()
    {
    }

    protected function getTransactionRepository(): \DbBundle\Repository\TransactionRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:Transaction');
    }

    protected function getSubTransactionRepository(): \DbBundle\Repository\SubTransactionRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:SubTransaction');
    }

    protected function getCustomerProductRepository(): \DbBundle\Repository\CustomerProductRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:CustomerProduct');
    }

    protected function getCurrencyRepository(): \DbBundle\Repository\CurrencyRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:Currency');
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

    protected function processEquation($equation, $variables = [], $predefineValues = [])
    {
        $variables = array_map(function ($value) use ($predefineValues) {
            return array_get($predefineValues, $value, $value);
        }, $variables);

        $value = Number::parseEquation($equation, $variables, true);

        return $value;
    }

    private function createBetTransaction(Customer $customer, Currency $currency, CustomerProduct $customerProduct, $bet, $expression, $isBetSettled) {
        $type = Transaction::TRANSACTION_TYPE_BET;
        $status = Transaction::TRANSACTION_STATUS_END;
        $transaction = new Transaction;
        $subTransaction = new SubTransaction;
        $stake = (new Number($bet['stake']))->__toString();
        $transaction->setCustomer($customer);
        $transaction->setCurrency($currency);

        $subTransaction->setAmount($stake);
        $subTransaction->setCustomerProduct($customerProduct);
        $subTransaction->setType($type);
        $baseCurrency = $this->getCurrencyRepository()->find($this->getSettingManager()->getSetting('currency.base'));
        $subTransaction->setDetail('baseCurrency', $baseCurrency->getCode());
        $subTransaction->setDetail('currency', $currency->getCode());
        $subTransaction->setDetail('rate', $currency->getRate());
        $subTransaction->setDetail('toCurrency', $currency->getCode());
        $subTransaction->setDetail('toRate', $currency->getRate());
        $subTransaction->setDetail('convertedAmount', $subTransaction->getAmount());
        $subTransaction->setDetail('betSettled', $isBetSettled);
        $subTransaction->copyImmutableCustomerProductData();

        $transaction->setNumber(date('Ymd-His-') . $type . '-' . $bet['bet_id']);

        $transaction->setType($type);
        $transaction->setAmount($stake);
        $transaction->setStatus($status);
        $transaction->setDate(new \DateTime($bet['date']));
        $transaction->setDetail('bet_details', $bet['details']);
        $transaction->setDetail('bet_id', $bet['bet_id']);
        $transaction->setDetail('event_id', $bet['event_id']);

        $sumProduct = new Number($transaction->getAmount());
        $sumWithdrawProduct = new Number(0);
        $sumDepositProduct = new Number(0);
        $totalCustomerFee = new Number(0);
        $totalCompanyFee = new Number(0);
        $companyFee = new Number(0);
        $customerFee = new Number(0);
        $values = [
            'sum_products' => $sumProduct->__toString(),
            'sum_withdraw_products' => $sumWithdrawProduct->__toString(),
            'sum_deposit_products' => $sumDepositProduct->__toString(),
            'total_customer_fee' => $totalCustomerFee->__toString(),
            'total_company_fee' => $totalCompanyFee->__toString(),
            'company_fee' => $companyFee->__toString(),
            'customer_fee' => $customerFee->__toString(),
        ];

        $totalAmount = $this->processEquation(array_get($expression, 'totalAmount.equation'), array_get($expression, 'totalAmount.variables'), $values);
        $customerAmount = $this->processEquation(array_get($expression, 'customerAmount.equation'), array_get($expression, 'customerAmount.variables'), $values);
        $transaction->setFee('total_customer_fee', $totalCustomerFee->__toString());
        $transaction->setFee('total_company_fee', $totalCompanyFee->__toString());

        $transaction->setDetail('summary', $values + [
            'total_amount' => $totalAmount->__toString(),
            'customer_amount' => $customerAmount->__toString(),
        ]);

        $transaction->addSubTransaction($subTransaction);

        $this->getTransactionRepository()->save($transaction);
    }

    public function getCustomerBalance(string $syncId)
    {
        $result = $this->getData('customer/' . $syncId . '/balance');

        if (!$result) {
            return 'BA Balance not available right now.';
        }

        return json_decode($result, true)['balance'];
    }

    public function getBrokerageBalance(string $syncId)
    {
        $result = $this->getData('customer/' . $syncId . '/brokerage-balance');

        return json_decode($result, true)['balance'];
    }

    private function postData($apiUri, $params = [])
    {
        $client = new Client();
        $brokerage = $this->getBrokerage();
        $baseUrl = $brokerage['url'];
        $url = $baseUrl . $apiUri;
        $headers = $this->getHeaders($brokerage);

        $response = $client->post(
            $url,
            ['headers' => $headers,
            'json' => $params]
        )->getBody()->getContents();

        return $response;
    }

    private function getData($apiUri, $params = [])
    {
        $client = new Client();
        $brokerage = $this->getBrokerage();
        $baseUrl = $brokerage['url'];
        $url = $baseUrl . $apiUri;
        $headers = $this->getHeaders($brokerage);


        try {
            $response = $client->get(
                $url,
                ['headers' => $headers,
                'query' => $params]
            )->getBody()->getContents();
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
            return null;
        }

        return $response;
    }

    private function getHeaders($brokerage)
    {
        return [
            'Authorization' => $brokerage['token_type'] . ' ' . $brokerage['access_token'],
            'Accept'        => 'application/json',
        ];
    }

    private function syncCustomerUrl()
    {
        $brokerage = $this->getBrokerage();

        return $brokerage['paths']['sync_customer_product'];
    }

    private function getSearchBrokerageNameUri()
    {
        $brokerage = $this->getBrokerage();

        return $brokerage['paths']['search_brokers_name'];
    }

    private function updateCustomerProductBalance($currentBalance, $subTotalStake)
    {
        $currentBalance = new Number($currentBalance);

        return $currentBalance->plus($subTotalStake)->__toString();
    }

    private function getBrokerageUrl()
    {
        return $this->brokerage['url'];
    }

    private function getTransactionManager(): \TransactionBundle\Manager\TransactionManager
    {
        return $this->getContainer()->get('transaction.manager');
    }
}
