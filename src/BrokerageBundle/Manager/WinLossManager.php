<?php

namespace BrokerageBundle\Manager;

use AppBundle\Manager\AbstractManager;
use AppBundle\ValueObject\Number;
use BrokerageBundle\Exceptions\UnableToSaveJobException;
use CommissionBundle\Manager\CommissionManager;
use DbBundle\Entity\CommissionPeriod;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\DWL;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\CommissionPeriodRepository;
use JMS\JobQueueBundle\Entity\Job;

class WinLossManager extends AbstractManager
{
    const NEGATIVE_INTEGER = -1;
    const DEFAULT_VERSION = 1;
    const FALSE_INTEGER = 0;

    public function syncWinLoss($data)
    {
        $response = ['message' => 'Process win/loss successful.', 'success' => true];
        $this->beginTransaction();
        try {
            $data['expression'] = $this->getSettingManager()->getSetting('transaction.equations.dwl');

            $responseData = $this->processBetWinLossItems($data);

            $this->commit();

            $this->computeAndPayoutCommissions($responseData['dwl'], $responseData['referrerIds']);
        } catch (UnableToSaveJobException $e) {
            /*
             * do nothing, since this exception is only to catch if unable to save the job,
             * wethere the job was saved or not it must still proceed as success
             */
        } catch (\Exception $e) {
            $this->rollback();

            throw $e;
        }

        return $response;
    }

    /**
     * Get media manager.
     *
     * @return \MediaBundle\Manager\MediaManager
     */
    protected function getMediaManager()
    {
        return $this->getContainer()->get('media.manager');
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

    protected function getTransactionRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Transaction');
    }

    protected function getSubTransactionRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:SubTransaction');
    }

    protected function getCustomerProductRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:CustomerProduct');
    }

    protected function getDWLRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:DWL');
    }

    protected function processEquation($equation, $variables = [], $predefineValues = [])
    {
        $variables = array_map(function ($value) use ($predefineValues) {
            return array_get($predefineValues, $value, $value);
        }, $variables);

        $value = Number::parseEquation($equation, $variables, true);

        return $value;
    }

    protected function getRepository()
    {
    }

    private function processBetWinLossItems($data)
    {
        $date = new \DateTime($data['date']);
        $hasDWLItem = false;
        $referrers = [];
        $referrerIds = [];
        foreach ($data['bets'] as $bet) {
            $customerProduct = $this->getCustomerProductRepository()->findOneByBetSyncId($bet['sync_id']);

            if ($customerProduct instanceOf CustomerProduct) {
                $dwl = $this->getDWLRepository()->findDWLByDateProductAndCurrency($customerProduct->getProductId(), $customerProduct->getCurrencyId(), $date);
                $dwlItem = null;
                if ($dwl instanceOf DWL) {
                    $file = file_get_contents($this->getDWLItemFile($dwl));
                    $dwlItemDetails = json_decode($file, true);
                    $countDwlItems = count($dwlItemDetails['items']);

                    if (isset($dwlItemDetails['items'])) {
                        foreach ($dwlItemDetails['items'] as $key => $item) {
                            if ($item['id'] == $customerProduct->getId()) {
                                $dwlItem = $item;
                                unset($dwlItemDetails['items'][$key]);
                                break;
                            }
                        }

                        if (count($dwlItem) > 0 && $data['checkWinlossItem']) {
                            $hasDWLItem = true;

                            continue;
                        } else {
                            if (isset($data['postBetDetails'])) {
                                $this->checkPostBets($data['postBetDetails']);
                                unset($data['postBetDetails']);
                            }
                        }

                        if ($data['voided'] && count($dwlItem) > 0) {
                            $this->updateCustomerProductBalance($bet, $customerProduct, $data['update'], $data['voided']);
                            $dwlItemDetails = $this->updateDwlItem($customerProduct, $bet, $dwlItem, $dwlItemDetails, $data['update'], $data['voided']);
                            $subTransaction = $this->updateTransactionAndSubtransactions($dwl, $bet, $customerProduct, $dwlItem, $data['expression'], $data['update'], $data['voided']);
                            $dwlItemDetails = $this->updateDwlItemTotal($dwl, $bet, $dwlItemDetails, $data['update']);
                            $dwl = $this->updateDwl($bet, $dwl, count($dwlItemDetails['items']), $data['update'], $data['voided']);
                        } elseif ($data['update']) {
                            if (isset($bet['stake'])) {
                                $this->updateCustomerProductBalance($bet, $customerProduct);
                            } else {
                                $this->updateCustomerProductBalance($bet, $customerProduct, $data['update']);
                            }
                            if (count($dwlItem) > 0) {
                                if (isset($bet['stake'])) {
                                    $dwlItemDetails = $this->updateDwlItem($customerProduct, $bet, $dwlItem, $dwlItemDetails);
                                    $subTransaction = $this->updateTransactionAndSubtransactions($dwl, $bet, $customerProduct, $dwlItem, $data['expression']);
                                    $dwl = $this->updateDwl($bet, $dwl, count($dwlItemDetails['items']));
                                } else {
                                    $dwlItemDetails = $this->updateDwlItem($customerProduct, $bet, $dwlItem, $dwlItemDetails, $data['update']);
                                    $subTransaction = $this->updateTransactionAndSubtransactions($dwl, $bet, $customerProduct, $dwlItem, $data['expression'], $data['update']);
                                    $dwl = $this->updateDwl($bet, $dwl, count($dwlItemDetails['items']), $data['update']);
                                }
                                $dwlItemDetails = $this->updateDwlItemTotal($dwl, $bet, $dwlItemDetails, $data['update']);
                            } elseif (isset($bet['stake'])) {
                                $subTransaction = $this->addTransactionAndSubtransactions($dwl, $bet, $customerProduct, $customerProduct->getCustomer(), $customerProduct->getCurrency(), $date, $data['expression']);
                                $dwlItemDetails = $this->addDwlItem($dwl, $customerProduct, $bet, $dwlItemDetails, $subTransaction->getParent(), $subTransaction->getId());
                                $dwlItemDetails = $this->updateDwlItemTotal($dwl, $bet, $dwlItemDetails, $data['update']);
                                $dwl = $this->updateDwl($bet, $dwl, count($dwlItemDetails['items']));
                            }
                        } else {
                            $this->updateCustomerProductBalance($bet, $customerProduct);
                            if (count($dwlItem) > 0) {
                                $subTransaction = $this->updateTransactionAndSubtransactions($dwl, $bet, $customerProduct, $dwlItem, $data['expression']);
                                $dwlItemDetails = $this->updateDwlItem($customerProduct, $bet, $dwlItem, $dwlItemDetails);
                            } else {
                                $subTransaction = $this->addTransactionAndSubtransactions($dwl, $bet, $customerProduct, $customerProduct->getCustomer(), $customerProduct->getCurrency(), $date, $data['expression']);
                                $dwlItemDetails = $this->addDwlItem($dwl, $customerProduct, $bet, $dwlItemDetails, $subTransaction->getParent(), $subTransaction->getId());
                            }
                            $dwlItemDetails = $this->updateDwlItemTotal($dwl, $bet, $dwlItemDetails);
                            $dwl = $this->updateDwl($bet, $dwl, count($dwlItemDetails['items']));
                        }

                        $this->generateDWLItemFile($dwl, $dwlItemDetails);
                    }
                } else {
                    $this->updateCustomerProductBalance($bet, $customerProduct);
                    $dwl = $this->addDwl($date, $customerProduct, $bet);
                    $subTransaction = $this->addTransactionAndSubtransactions($dwl, $bet, $customerProduct, $customerProduct->getCustomer(), $customerProduct->getCurrency(), $date, $data['expression']);

                    $dwlItemDetails = [
                        'items' => [],
                        'total' => [
                            'turnover' => 0,
                            'grossCommission' => 0,
                            'memberWinLoss' => 0,
                            'memberCommission' => 0,
                            'memberAmount' => 0,
                        ],
                    ];
                    $dwlItemDetails = $this->addDwlItem($dwl, $customerProduct, $bet, $dwlItemDetails, $subTransaction->getParent(), $subTransaction->getId());
                    $dwlItemDetails = $this->updateDwlItemTotal($dwl, $bet, $dwlItemDetails);

                    $this->generateDWLItemFile($dwl, $dwlItemDetails);
                }

                $customer = $customerProduct->getCustomer();

                if ($customer->hasReferrer()) {
                    $referrer = $customer->getReferrer();
                    $referrerIds[$referrer->getId()] = $referrer->getId();
                }
            }
        }

        return ['dwl' => $dwl, 'referrerIds' => $referrerIds];
    }

    private function addTransactionAndSubtransactions($dwl, $bet, $customerProduct, $customer, $currency, $date, $expression)
    {
        $type = Transaction::TRANSACTION_TYPE_DWL;
        $status = Transaction::TRANSACTION_STATUS_END;
        $transaction = new Transaction;
        $subTransaction = new SubTransaction;
        $transaction->setCustomer($customer);
        $transaction->setCurrency($currency);
        $turnover = (new Number($bet['turnover']))->__toString();
        $winLoss = (new Number($bet['winLoss']))->__toString();

        $subTransaction->setCustomerProduct($customerProduct);
        $subTransaction->setType($type);
        $subTransaction->setAmount($winLoss);
        $subTransaction->setDetail('baseCurrency', $currency->getCode());
        $subTransaction->setDetail('currency', $currency->getCode());
        $subTransaction->setDetail('rate', $currency->getRate());
        $subTransaction->setDetail('toCurrency', $currency->getCode());
        $subTransaction->setDetail('toRate', $currency->getRate());
        $subTransaction->setDetail('convertedAmount', $subTransaction->getAmount());
        $subTransaction->setDetail('dwl.id', $dwl->getId());
        $subTransaction->setDetail('dwl.customer.balance', $customerProduct->getBalance());
        $subTransaction->setDetail('dwl.turnover', $turnover);
        $subTransaction->setDetail('dwl.winLoss', $winLoss);
        $subTransaction->setDetail('dwl.gross', 0);
        $subTransaction->setDetail('dwl.commission', 0);

        $subTransaction->copyImmutableCustomerProductData();

        $transaction->setNumber(sprintf(
            '%s-%s-%s-%s',
            date('Ymd-His'),
            Transaction::TRANSACTION_TYPE_DWL,
            $dwl->getId(),
            $bet['sync_id']
        ));

        $transaction->setType($type);
        $transaction->setAmount($winLoss);
        $transaction->setDetail('dwl.id', $dwl->getId());
        $transaction->setStatus($status);
        $transaction->setDate(new \DateTimeImmutable('now'));
        $this->setTransactionSummary($transaction, $expression);

        $transaction->addSubTransaction($subTransaction);

        $this->getCommissionManager()->setCommissionInformationForTransaction($transaction, $dwl);

        $this->getTransactionRepository()->save($transaction);

        return $subTransaction;
    }

    private function updateTransactionAndSubtransactions(DWL $dwl, $bet, $customerProduct, $dwlItem, $expression, $update = false, $void = false)
    {
        if ($void) {
            $winLoss = (new Number($bet['winLoss']))->times(WinLossManager::NEGATIVE_INTEGER);
            $turnover = (new Number($bet['turnover']))->times(WinLossManager::NEGATIVE_INTEGER);
        } else {
            $turnover = (new Number($bet['turnover']))->minus($bet['prevTurnover']);
            $winLoss = (new Number($bet['winLoss']))->minus($bet['prevWinLoss']);
        }
        $subTransaction = $this->getSubTransactionRepository()->findOneById($dwlItem['transaction']['subId']);

        $subTransaction->setAmount((new Number($subTransaction->getAmount()))->plus($winLoss)->__toString());
        $subTransaction->setDetail('dwl.customer.balance', $customerProduct->getBalance());
        $subTransaction->setDetail('dwl.turnover', (new Number($subTransaction->getDetail('dwl.turnover')))->plus($turnover)->__toString());
        $subTransaction->setDetail('dwl.winLoss', (new Number($subTransaction->getDetail('dwl.winLoss')))->plus($winLoss)->__toString());

        $transaction = $subTransaction->getParent();
        $transaction->setAmount((new Number($transaction->getAmount()))->plus($winLoss)->__toString());
        $this->setTransactionSummary($transaction, $expression);

        $transaction->addSubTransaction($subTransaction);

        $this->getCommissionManager()->setCommissionInformationForTransaction($transaction, $dwl);

        $this->getTransactionRepository()->save($transaction);

        return $subTransaction;
    }

    private function addDwl($date, $customerProduct, $bet)
    {
        $dwl = new DWL();
        $dwl->setDate($date);
        $dwl->setDetail('versions.1', DWL::DWL_STATUS_COMPLETED);
        $dwl->setDetail('total.turnover', (new Number($bet['turnover']))->__toString());
        $dwl->setDetail('total.memberWinLoss', (new Number($bet['winLoss']))->__toString());
        $dwl->setDetail('total.memberAmount', (new Number($bet['winLoss']))->__toString());
        $dwl->setDetail('total.record', WinLossManager::DEFAULT_VERSION);
        $dwl->setDetail('total.grossCommission', 0);
        $dwl->setStatusCompleted();
        $dwl->setCurrency($customerProduct->getCurrency());
        $dwl->setProduct($customerProduct->getProduct());
        $this->getDWLRepository()->save($dwl);

        $this->generateDWLFile($dwl);

        return $dwl;
    }

    private function updateDwl($bet, $dwl, $record, $update = false, $void = false)
    {
        if ($void) {
            $turnover = (new Number($bet['turnover']))->times(WinLossManager::NEGATIVE_INTEGER);
            $winLoss = (new Number($bet['winLoss']))->times(WinLossManager::NEGATIVE_INTEGER);
        } else {
            $turnover = (new Number($bet['turnover']))->minus($bet['prevTurnover']);
            $winLoss = (new Number($bet['winLoss']))->minus($bet['prevWinLoss']);
        }

        $dwl->setDetail('total.turnover', (new Number($dwl->getDetail('total.turnover')))->plus($turnover)->__toString());
        $dwl->setDetail('total.memberWinLoss', (new Number($dwl->getDetail('total.memberWinLoss')))->plus($winLoss)->__toString());
        $dwl->setDetail('total.memberAmount', (new Number($dwl->getDetail('total.memberAmount')))->plus($winLoss)->__toString());
        $dwl->setDetail('total.record', $record);
        $this->getDWLRepository()->save($dwl);

        $this->generateDWLFile($dwl);

        return $dwl;
    }

    private function addDwlItem($dwl, $customerProduct, $bet, $dwlItemDetails, $transaction, $subTransactionId)
    {
        $dwlItemDetails['items'][] = [
            'id' => $customerProduct->getId(),
            'username' => $customerProduct->getUserName(),
            'turnover' => (new Number($bet['turnover']))->__toString(),
            'gross' => (new Number(0))->__toString(),
            'winLoss' => (new Number($bet['winLoss']))->__toString(),
            'commission' => (new Number(0))->__toString(),
            'amount' => (new Number($bet['winLoss']))->__toString(),
            'calculatedAmount' => (new Number($bet['winLoss']))->__toString(),
            'amount' => (new Number($bet['winLoss']))->__toString(),
            'calculatedAmount' => (new Number($bet['winLoss']))->__toString(),
            'transaction' => [
                'id' => $transaction->getId(),
                'subId' => $subTransactionId,
            ],
            'errors' => [],
            'customer' => [
                'balance' => (new Number($customerProduct->getBalance()))->__toString(),
            ],
        ];

        return $dwlItemDetails;
    }

    private function updateDwlItem($customerProduct, $bet, $dwlItem, $dwlItemDetails, $update = false, $void = false)
    {
        if ($void) {
            $dwlItem['turnover'] = (new Number($dwlItem['turnover']))->minus($bet['turnover'])->__toString();
            $dwlItem['winLoss'] = (new Number($dwlItem['winLoss']))->minus($bet['winLoss'])->__toString();
            $dwlItem['amount'] = (new Number($dwlItem['amount']))->minus($bet['winLoss'])->__toString();
            $dwlItem['calculatedAmount'] = (new Number($dwlItem['winLoss']))->minus($bet['winLoss'])->__toString();
        } else {
            $dwlItem['turnover'] = ((new Number($dwlItem['turnover']))->plus($bet['turnover']))->minus($bet['prevTurnover'])->__toString();
            $winLoss = (new Number($bet['winLoss']))->minus($bet['prevWinLoss']);
            $dwlItem['winLoss'] = (new Number($dwlItem['winLoss']))->plus($winLoss)->__toString();
            $dwlItem['amount'] = (new Number($dwlItem['amount']))->plus($winLoss)->__toString();
            $dwlItem['calculatedAmount'] = (new Number($dwlItem['winLoss']))->plus($winLoss)->__toString();
        }
        $dwlItem['customer']['balance'] = (new Number($customerProduct->getBalance()))->__toString();

        //removing array keys
        $dwlItems = $dwlItemDetails['items'];
        unset($dwlItemDetails['items']);
        foreach ($dwlItems as $item) {
            $dwlItemDetails['items'][] = $item;
        }

        $dwlItemDetails['items'][] = $dwlItem;

        return $dwlItemDetails;
    }

    private function updateDwlItemTotal($dwl, $bet, $dwlItemDetails, $update = false, $void = false)
    {
        if ($void) {
            $dwlItemDetails['total']['turnover'] = (new Number($dwl->getDetail('total.turnover')))->minus($bet['turnover'])->__toString();
            $dwlItemDetails['total']['memberWinLoss'] = (new Number($dwl->getDetail('total.memberWinLoss')))->minus($bet['winLoss'])->__toString();
            $dwlItemDetails['total']['memberAmount'] = (new Number($dwl->getDetail('total.memberAmount')))->minus($bet['winLoss'])->__toString();
            $dwlItemDetails['total']['calculatedAmount'] = (new Number($dwl->getDetail('total.memberAmount')))->minus($bet['winLoss'])->__toString();
        } else {
            $dwlItemDetails['total']['turnover'] = ((new Number($dwl->getDetail('total.turnover')))->plus($bet['turnover']))->minus($bet['prevTurnover'])->__toString();
            $dwlItemDetails['total']['memberWinLoss'] = (new Number($dwl->getDetail('total.memberWinLoss')))->plus($bet['winLoss'])->__toString();
            $dwlItemDetails['total']['memberAmount'] = (new Number($dwl->getDetail('total.memberAmount')))->plus($bet['winLoss'])->__toString();
            $dwlItemDetails['total']['calculatedAmount'] = (new Number($dwl->getDetail('total.memberAmount')))->plus($bet['winLoss'])->__toString();
        }
        $dwlItemDetails['total']['record'] = $dwl->getDetail('total.record');

        return $dwlItemDetails;
    }

    private function generateDWLFile($dwl)
    {
        $fileName = sprintf('dwl/progress_%s_v_%s.json', $dwl->getId(), $dwl->getVersion());
        $this->getMediaManager()->deleteFile($fileName);
        $this->getMediaManager()->createFile($fileName);
        $progressData = [
            '_v' => base64_encode($dwl->getUpdatedAt()->format('Y-m-d H:i:s')),
            'status' => $dwl->getStatus(),
            'process' => $dwl->getDetail('total.record'),
            'total' => $dwl->getDetail('total.record'),
        ];
        file_put_contents($this->getMediaManager()->getFilePath($fileName), \GuzzleHttp\json_encode($progressData));
    }

    private function generateDWLItemFile($dwl, $data)
    {
        $fileName = sprintf('%s%s_v_%s.json', $this->getMediaManager()->getPath('dwl'), $dwl->getId(), $dwl->getVersion());
        $this->getMediaManager()->deleteFile($fileName);
        $this->getMediaManager()->createFile($fileName);
        file_put_contents($fileName, json_encode($data));
    }

    /**
     * @param array $params
     *
     * @return array
     */
    private function updateCustomerProductBalance($bet, CustomerProduct $customerProduct, $update = false, $void = false)
    {
        if ($void) {
            $stake = (new Number($bet['stake']))->times(WinLossManager::NEGATIVE_INTEGER);
            $winLoss = (new Number($bet['winLoss']))->times(WinLossManager::NEGATIVE_INTEGER);
        } else {
            $stake = new Number($bet['stake']);
            $winLoss = (new Number($bet['winLoss']))->minus($bet['prevWinLoss']);
        }

        $subTotal = $stake->plus($winLoss);
        $newBalance = (new Number($customerProduct->getBalance()))->plus($subTotal)->__toString();
        $customerProduct->setBalance($newBalance);
        $this->getCustomerProductRepository()->save($customerProduct);
    }

    private function setTransactionSummary($transaction, $expression)
    {
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
    }

    private function getDWLItemFile($dwl)
    {
        return $this->getContainer()->getParameter('upload_folder') . sprintf('dwl/%s_v_%s.json', $dwl->getId(), $dwl->getVersion());
    }

    private function checkPostBets($data)
    {
        foreach ($data as $postBetDetails) {
            $customerProduct = $this->getCustomerProductRepository()->findOneByBetSyncId($postBetDetails['sync_id']);

            if ($customerProduct instanceOf CustomerProduct) {
                foreach ($postBetDetails['bets'] as $key => $bet) {
                    $transaction = $this->getTransactionRepository()->findOneByBetId($bet['bet_id']);

                    if ($transaction instanceOf Transaction) {
                        $this->settlePastTransactionsToPreventReportDiscrepancies($transaction);
                        unset($postBetDetails['bets'][$key]);
                    }
                }

                if (count($postBetDetails['bets']) > 0) {
                    $this->getBrokerageManager()->syncPostBets($customerProduct, $postBetDetails['bets'], true);
                }
            } else {
                continue;
            }
        }
    }

    private function settlePastTransactionsToPreventReportDiscrepancies(Transaction $transaction)
    {
        $subTransaction = $transaction->getFirstSubTransaction();
        if (!$subTransaction->isBetSettled()) {
            $subTransaction->setDetail('betSettled', true);
            $this->getTransactionRepository()->save($transaction);
        }
    }

    private function computeAndPayoutCommissions(DWL $dwl, $referrerIds = [])
    {
        try {
            if (count($referrerIds) > 0) {
                $period = $this->getCommissionPeriodRepository()->getCommissionForDWL($dwl);
                if ($period instanceof CommissionPeriod) {
                    $computeJob = new Job('commission:period:compute',
                        [
                            $this->getUser()->getUsername(),
                            '--period',
                            $period->getId(),
                            '--members',
                            json_encode($referrerIds),
                            '--env',
                            $this->getContainer()->get('kernel')->getEnvironment(),
                        ],
                        true,
                        'payout'
                    );

                    $payJob = new Job('commission:period:pay',
                        [
                            $this->getUser()->getUsername(),
                            '--period',
                            $period->getId(),
                            '--members',
                            json_encode($referrerIds),
                            '--env',
                            $this->getContainer()->get('kernel')->getEnvironment(),
                        ],
                        true,
                        'payout'
                    );
                    $payJob->addDependency($computeJob);
                    
                    $this->getEntityManager()->persist($computeJob);
                    $this->getEntityManager()->persist($payJob);
                    $this->getEntityManager()->flush($computeJob);
                    $this->getEntityManager()->flush($payJob);
                }
            }
        } catch (\Exception $e) {
            throw new UnableToSaveJobException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function getBrokerageManager(): BrokerageManager
    {
        return $this->container->get('brokerage.brokerage_manager');
    }

    private function getCommissionManager(): CommissionManager
    {
        return $this->container->get('commission.manager');
    } 

    private function getCommissionPeriodRepository(): CommissionPeriodRepository
    {
        return $this->getDoctrine()->getRepository(CommissionPeriod::class);
    }
}