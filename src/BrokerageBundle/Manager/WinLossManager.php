<?php

namespace BrokerageBundle\Manager;

use AppBundle\Manager\AbstractManager;
use AppBundle\ValueObject\Number;
use BrokerageBundle\Exceptions\UnableToSaveJobException;
use CommissionBundle\Manager\CommissionManager;
use DbBundle\Entity\CommissionPeriod;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\DWL;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\Currency;
use DbBundle\Repository\CommissionPeriodRepository;
use JMS\JobQueueBundle\Entity\Job;
use DWLBundle\Command\DwlGenerateFileCommand;
use Symfony\Component\Process\Process;

class WinLossManager extends AbstractManager
{
    const NEGATIVE_INTEGER = -1;
    const DEFAULT_VERSION = 1;
    const FALSE_INTEGER = 0;

    /**
     *
     * @var \BrokerageBundle\Service\Brokerage
     */
    private $brokerage;

    public function setBrokerage(\BrokerageBundle\Service\Brokerage $brokerage): void
    {
        $this->brokerage = $brokerage;
    }

    public function syncWinLoss($data)
    {
        $response = ['message' => 'Process win/loss successful.', 'success' => true];
        $this->beginTransaction();
        try {
            $data['expression'] = $this->getSettingManager()->getSetting('transaction.equations.dwl');

            $responseData = $this->processBetWinLossItems($data);

            $this->commit();

            $this->computeAndPayoutCommissions($responseData['dwl'], $responseData['referrerIds']);
            $this->regenerateDwlFile($responseData['dwl']);
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

    public function resyncWinLoss(array $data): array
    {
        $this->beginTransaction();
        $response = ['sync_ids_affected' => []];
        try {
            $data['expression'] = $this->getSettingManager()->getSetting('transaction.equations.dwl');
            $dwlDate = \DateTimeImmutable::createFromFormat('Y-m-d', $data['date']);
            $dwls = [];
            $referrerIds = [];

            foreach ($data['members'] as $bet) {
                $memberProduct = $this->getCustomerProductRepository()->findOneByBetSyncId($bet['sync_id']);
                if ($memberProduct instanceOf MemberProduct) {
                    $dwl = $this->getDWLRepository()->findDWLByDateProductAndCurrency($memberProduct->getProductId(), $memberProduct->getCurrencyId(), $dwlDate);
                    if ($dwl instanceOf DWL) {
                        $subTransaction = $this->getSubTransactionRepository()->getSubTransactionByDwlAndMemberProduct($dwl->getId(), $memberProduct->getId());

                        if (!$subTransaction instanceof SubTransaction) {
                            $this->addTransactionAndSubtransactions($dwl, $bet, $memberProduct, $memberProduct->getCustomer(), $memberProduct->getCurrency(), $dwlDate, $data['expression']);

                            $this->updateCustomerProductBalance($bet, $memberProduct);

                            $response['sync_ids_affected'][] = $bet['sync_id'];

                            $member = $memberProduct->getCustomer();

                            if ($member->hasReferrer()) {
                                $referrer = $member->getReferrer();
                                $referrerIds[$dwl->getId()][$referrer->getId()] = $member->getId();
                            }

                            $dwls[$dwl->getId()] = $dwl;
                        }
                    }
                }
            }

            if (count($dwls) > 0) {
                foreach ($dwls as $dwl) {
                    if (count($response['sync_ids_affected']) > 0) {
                        $this->recountDwlItemsAndSave($dwl);
                    }

                    $this->regenerateDwlFile($dwl);
                }
            }

            $this->commit();

            if (count($referrerIds) > 0) {
                foreach ($referrerIds as $key => $value) {
                    $this->computeAndPayoutCommissions($dwls[$key], $value);
                }
            }
        } catch (UnableToSaveJobException $e) {

        } catch (\Exception $e) {
            //$this->rollback();

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
            $memberProduct = $this->getCustomerProductRepository()->findOneByBetSyncId($bet['sync_id']);

            if ($memberProduct instanceOf MemberProduct) {
                $dwl = $this->getDWLRepository()->findDWLByDateProductAndCurrency($memberProduct->getProductId(), $memberProduct->getCurrencyId(), $date);
                $dwlItem = null;
                if ($dwl instanceOf DWL) {
                    $file = file_get_contents($this->getDWLItemFile($dwl));
                    $dwlItemDetails = json_decode($file, true);
                    $countDwlItems = count($dwlItemDetails['items']);

                    if (isset($dwlItemDetails['items'])) {
                        foreach ($dwlItemDetails['items'] as $key => $item) {
                            if ($item['id'] == $memberProduct->getId()) {
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
                            $this->updateCustomerProductBalance($bet, $memberProduct, $data['update'], $data['voided']);
                            $dwlItemDetails = $this->updateDwlItem($memberProduct, $bet, $dwlItem, $dwlItemDetails, $data['update'], $data['voided']);
                            $subTransaction = $this->updateTransactionAndSubtransactions($dwl, $bet, $memberProduct, $dwlItem, $data['expression'], $data['update'], $data['voided']);
                            $dwlItemDetails = $this->updateDwlItemTotal($dwl, $bet, $dwlItemDetails, $data['update']);
                            $dwl = $this->updateDwl($bet, $dwl, count($dwlItemDetails['items']), $data['update'], $data['voided']);
                        } elseif ($data['update']) {
                            if (isset($bet['stake'])) {
                                $this->updateCustomerProductBalance($bet, $memberProduct);
                            } else {
                                $this->updateCustomerProductBalance($bet, $memberProduct, $data['update']);
                            }
                            if (count($dwlItem) > 0) {
                                if (isset($bet['stake'])) {
                                    $dwlItemDetails = $this->updateDwlItem($memberProduct, $bet, $dwlItem, $dwlItemDetails);
                                    $subTransaction = $this->updateTransactionAndSubtransactions($dwl, $bet, $memberProduct, $dwlItem, $data['expression']);
                                    $dwl = $this->updateDwl($bet, $dwl, count($dwlItemDetails['items']));
                                } else {
                                    $dwlItemDetails = $this->updateDwlItem($memberProduct, $bet, $dwlItem, $dwlItemDetails, $data['update']);
                                    $subTransaction = $this->updateTransactionAndSubtransactions($dwl, $bet, $memberProduct, $dwlItem, $data['expression'], $data['update']);
                                    $dwl = $this->updateDwl($bet, $dwl, count($dwlItemDetails['items']), $data['update']);
                                }
                                $dwlItemDetails = $this->updateDwlItemTotal($dwl, $bet, $dwlItemDetails, $data['update']);
                            } elseif (isset($bet['stake'])) {
                                $subTransaction = $this->addTransactionAndSubtransactions($dwl, $bet, $memberProduct, $memberProduct->getCustomer(), $memberProduct->getCurrency(), $date, $data['expression']);
                                $dwlItemDetails = $this->addDwlItem($dwl, $memberProduct, $bet, $dwlItemDetails, $subTransaction->getParent(), $subTransaction->getId());
                                $dwlItemDetails = $this->updateDwlItemTotal($dwl, $bet, $dwlItemDetails, $data['update']);
                                $dwl = $this->updateDwl($bet, $dwl, count($dwlItemDetails['items']));
                            }
                        } else {
                            if (count($dwlItem) > 0) {
                                $subTransaction = $this->updateTransactionAndSubtransactions($dwl, $bet, $memberProduct, $dwlItem, $data['expression']);
                                $dwlItemDetails = $this->updateDwlItem($memberProduct, $bet, $dwlItem, $dwlItemDetails);
                            } else {
                                $subTransaction = $this->addTransactionAndSubtransactions($dwl, $bet, $memberProduct, $memberProduct->getCustomer(), $memberProduct->getCurrency(), $date, $data['expression']);
                                $dwlItemDetails = $this->addDwlItem($dwl, $memberProduct, $bet, $dwlItemDetails, $subTransaction->getParent(), $subTransaction->getId());
                            }
                            $dwlItemDetails = $this->updateDwlItemTotal($dwl, $bet, $dwlItemDetails);
                            $dwl = $this->updateDwl($bet, $dwl, count($dwlItemDetails['items']));
                            $this->updateCustomerProductBalance($bet, $memberProduct);
                        }

                        $this->generateDWLItemFile($dwl, $dwlItemDetails);
                    }
                } else {
                    $dwl = $this->addDwl($date, $memberProduct, $bet);
                    $subTransaction = $this->addTransactionAndSubtransactions($dwl, $bet, $memberProduct, $memberProduct->getCustomer(), $memberProduct->getCurrency(), $date, $data['expression']);

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
                    $dwlItemDetails = $this->addDwlItem($dwl, $memberProduct, $bet, $dwlItemDetails, $subTransaction->getParent(), $subTransaction->getId());
                    $dwlItemDetails = $this->updateDwlItemTotal($dwl, $bet, $dwlItemDetails);

                    $this->generateDWLItemFile($dwl, $dwlItemDetails);
                    $this->updateCustomerProductBalance($bet, $memberProduct);
                }

                $member = $memberProduct->getCustomer();

                if ($member->hasReferrer()) {
                    $referrer = $member->getReferrer();
                    $referrerIds[$referrer->getId()] = $referrer->getId();
                }
            }
        }

        return ['dwl' => $dwl, 'referrerIds' => $referrerIds];
    }

    private function addTransactionAndSubtransactions(
        DWL $dwl,
        array $bet,
        MemberProduct $memberProduct,
        Member $member,
        Currency $currency,
        \DateTimeInterface $date,
        array $expression): SubTransaction
    {
        $type = Transaction::TRANSACTION_TYPE_DWL;
        $status = Transaction::TRANSACTION_STATUS_END;
        $transaction = new Transaction;
        $subTransaction = new SubTransaction;
        $transaction->setCustomer($member);
        $transaction->setCurrency($currency);
        $turnover = (new Number($bet['turnover']))->toString();
        $winLoss = (new Number($bet['winLoss']))->toString();
        $stake = (new Number($bet['stake']))->toString();

        $subTransaction->setCustomerProduct($memberProduct);
        $subTransaction->setType($type);
        $subTransaction->setAmount($winLoss);
        $subTransaction->setDwlBrokerageStake($stake);
        $subTransaction->setDwlBrokerageWinLoss($winLoss);
        $subTransaction->setDetail('baseCurrency', $currency->getCode());
        $subTransaction->setDetail('currency', $currency->getCode());
        $subTransaction->setDetail('rate', $currency->getRate());
        $subTransaction->setDetail('toCurrency', $currency->getCode());
        $subTransaction->setDetail('toRate', $currency->getRate());
        $subTransaction->setDetail('convertedAmount', $subTransaction->getAmount());
        $subTransaction->setDwlId($dwl->getId());
        $subTransaction->setDwlTurnover($turnover);
        $subTransaction->setDwlWinLoss($winLoss);
        $subTransaction->setDwlGrossCommission(0);
        $subTransaction->setDwlCommission(0);
        if (isset($bet['current_balance'])) {
            $subTransaction->setDwlCustomerBalance($bet['current_balance']);
        } else {
            $subTransaction->setDwlCustomerBalance(
                $this->getBrokerageBalanceForDate($dwl->getDate(), $memberProduct->getBrokerageSyncId())
            );
        }

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

    private function updateTransactionAndSubtransactions(
        DWL $dwl,
        array $bet,
        MemberProduct $memberProduct,
        array $dwlItem,
        array $expression,
        bool $update = false,
        bool $void = false): SubTransaction
    {
        if ($void) {
            $winLoss = (new Number($bet['winLoss']))->times(WinLossManager::NEGATIVE_INTEGER);
            $turnover = (new Number($bet['turnover']))->times(WinLossManager::NEGATIVE_INTEGER);
            $stake = (new Number($bet['stake']))->times(WinLossManager::NEGATIVE_INTEGER);
        } else {
            $turnover = (new Number($bet['turnover']))->minus($bet['prevTurnover']);
            $winLoss = (new Number($bet['winLoss']))->minus($bet['prevWinLoss']);
            $stake = (new Number($bet['stake']));
        }

        $subTransaction = $this->getSubTransactionRepository()->findOneById($dwlItem['transaction']['subId']);
        $subTransaction->setAmount((new Number($subTransaction->getAmount()))->plus($winLoss)->toString());
        $subTransaction->setDwlTurnover((new Number($subTransaction->getDetail('dwl.turnover')))->plus($turnover)->toString());
        $subTransaction->setDwlWinLoss(Number::add($subTransaction->getDwlWinLoss(), $winLoss)->toString());

        if ($subTransaction->hasDwlBrokerageWinLoss()) {
            $subTransaction->setDwlBrokerageWinLoss((new Number($subTransaction->getDwlBrokerageWinLoss()))->plus($winLoss)->toString());
            $subTransaction->setDwlBrokerageStake((new Number($subTransaction->getDwlBrokerageStake()))->plus($stake)->toString());
        } else {
            $subTransaction->setDwlBrokerageWinLoss($winLoss->toString());
            $subTransaction->setDwlBrokerageStake($stake->toString());
        }
        if (isset($bet['current_balance'])) {
            $subTransaction->setDwlCustomerBalance($bet['current_balance']);
        } else {
            $subTransaction->setDwlCustomerBalance(
                $this->getBrokerageBalanceForDate($dwl->getDate(), $memberProduct->getBrokerageSyncId())
            );
        }

        $transaction = $subTransaction->getParent();
        $transaction->setAmount((new Number($transaction->getAmount()))->plus($winLoss)->toString());
        $this->setTransactionSummary($transaction, $expression);

        $transaction->addSubTransaction($subTransaction);

        $this->getCommissionManager()->setCommissionInformationForTransaction($transaction, $dwl);

        $this->getTransactionRepository()->save($transaction);

        return $subTransaction;
    }

    private function addDwl(\DateTimeInterface $date, MemberProduct $memberProduct, array $bet): DWL
    {
        $dwl = new DWL();
        $dwl->setDate($date);
        $dwl->setDetail('versions.1', DWL::DWL_STATUS_COMPLETED);
        $dwl->setDetail('total.turnover', (new Number($bet['turnover']))->toString());
        $dwl->setDetail('total.memberWinLoss', (new Number($bet['winLoss']))->toString());
        $dwl->setDetail('total.memberAmount', (new Number($bet['winLoss']))->toString());
        $dwl->setDetail('total.record', WinLossManager::DEFAULT_VERSION);
        $dwl->setDetail('total.grossCommission', 0);
        $dwl->setStatusCompleted();
        $dwl->setCurrency($memberProduct->getCurrency());
        $dwl->setProduct($memberProduct->getProduct());
        $this->getDWLRepository()->save($dwl);

        $this->generateDWLFile($dwl);

        return $dwl;
    }

    private function updateDwl(
        array $bet,
        DWL $dwl,
        int $record,
        bool $update = false,
        bool $void = false): DWL
    {
        if ($void) {
            $turnover = (new Number($bet['turnover']))->times(WinLossManager::NEGATIVE_INTEGER);
            $winLoss = (new Number($bet['winLoss']))->times(WinLossManager::NEGATIVE_INTEGER);
        } else {
            $turnover = (new Number($bet['turnover']))->minus($bet['prevTurnover']);
            $winLoss = (new Number($bet['winLoss']))->minus($bet['prevWinLoss']);
        }

        $dwl->setDetail('total.turnover', (new Number($dwl->getDetail('total.turnover')))->plus($turnover)->plus($bet['stake'])->toString());
        $dwl->setDetail('total.memberWinLoss', (new Number($dwl->getDetail('total.memberWinLoss')))->plus($winLoss)->plus($bet['stake'])->toString());
        $dwl->setDetail('total.memberAmount', (new Number($dwl->getDetail('total.memberAmount')))->plus($winLoss)->plus($bet['stake'])->toString());
        $dwl->setDetail('total.record', $record);
        $this->getDWLRepository()->save($dwl);

        $this->generateDWLFile($dwl);

        return $dwl;
    }

    private function addDwlItem(
        DWL $dwl,
        MemberProduct $memberProduct,
        array $bet,
        array $dwlItemDetails,
        Transaction $transaction,
        int $subTransactionId): array
    {
        $dwlItemDetails['items'][] = [
            'id' => $memberProduct->getId(),
            'username' => $memberProduct->getUserName(),
            'turnover' => (new Number($bet['turnover']))->toString(),
            'gross' => (new Number(0))->toString(),
            'winLoss' => Number::add($bet['winLoss'], $bet['stake'])->toString(),
            'commission' => (new Number(0))->toString(),
            'amount' => Number::add($bet['winLoss'], $bet['stake'])->toString(),
            'calculatedAmount' => (new Number($bet['winLoss']))->toString(),
            'brokerage' => [
                'winLoss' => (new Number($bet['winLoss']))->toString(),
                'stake' => (new Number($bet['stake']))->toString(),
            ],
            'transaction' => [
                'id' => $transaction->getId(),
                'subId' => $subTransactionId,
            ],
            'errors' => [],
            'customer' => [
                'balance' => (new Number($memberProduct->getBalance()))->toString(),
            ],
        ];

        return $dwlItemDetails;
    }

    private function updateDwlItem(
        MemberProduct $memberProduct,
        array $bet,
        array $dwlItem,
        array $dwlItemDetails,
        bool $update = false,
        bool $void = false): array
    {
        if ($void) {
            $dwlItem['turnover'] = (new Number($dwlItem['turnover']))->minus($bet['turnover'])->toString();
            $dwlItem['winLoss'] = (new Number($dwlItem['winLoss']))->minus($bet['winLoss'])->minus($bet['stake'])->toString();
            $dwlItem['amount'] = (new Number($dwlItem['amount']))->minus($bet['winLoss'])->minus($bet['stake'])->toString();
            $dwlItem['calculatedAmount'] = (new Number($dwlItem['winLoss']))->minus($bet['winLoss'])->minus($bet['stake'])->toString();

            if (isset($dwlItem['brokerage'])) {
                $dwlItem['brokerage']['winLoss'] = (new Number($dwlItem['brokerage']['winLoss']))->minus($bet['winLoss'])->toString();
                $dwlItem['brokerage']['stake'] = (new Number($dwlItem['brokerage']['stake']))->minus($bet['stake'])->toString();
            }
        } else {
            $dwlItem['turnover'] = ((new Number($dwlItem['turnover']))->plus($bet['turnover']))->minus($bet['prevTurnover'])->toString();
            $winLoss = (new Number($bet['winLoss']))->minus($bet['prevWinLoss']);
            $dwlItem['winLoss'] = (new Number($dwlItem['winLoss']))->plus($winLoss)->plus($bet['stake'])->toString();
            $dwlItem['amount'] = (new Number($dwlItem['amount']))->plus($bet['stake'])->plus($winLoss)->toString();
            $dwlItem['calculatedAmount'] = (new Number($dwlItem['winLoss']))->plus($winLoss)->plus($bet['stake'])->toString();

            if (isset($dwlItem['brokerage'])) {
                $dwlItem['brokerage']['winLoss'] = (new Number($dwlItem['brokerage']['winLoss']))->plus($winLoss)->toString();
                $dwlItem['brokerage']['stake'] = (new Number($dwlItem['brokerage']['stake']))->plus($bet['stake'])->toString();
            }
        }

        //removing array keys
        $dwlItems = $dwlItemDetails['items'];
        unset($dwlItemDetails['items']);
        foreach ($dwlItems as $item) {
            $dwlItemDetails['items'][] = $item;
        }

        $dwlItemDetails['items'][] = $dwlItem;

        return $dwlItemDetails;
    }

    private function updateDwlItemTotal(
        DWL $dwl,
        array $bet,
        array $dwlItemDetails,
        bool $update = false,
        bool $void = false): array
    {
        if ($void) {
            $dwlItemDetails['total']['turnover'] = (new Number($dwl->getDetail('total.turnover')))->minus($bet['turnover'])->toString();
            $dwlItemDetails['total']['memberWinLoss'] = (new Number($dwl->getDetail('total.memberWinLoss')))->minus($bet['winLoss'])->minus($bet['stake'])->toString();
            $dwlItemDetails['total']['memberAmount'] = (new Number($dwl->getDetail('total.memberAmount')))->minus($bet['winLoss'])->minus($bet['stake'])->toString();
            $dwlItemDetails['total']['calculatedAmount'] = (new Number($dwl->getDetail('total.memberAmount')))->minus($bet['winLoss'])->minus($bet['stake'])->toString();
        } else {
            $dwlItemDetails['total']['turnover'] = ((new Number($dwl->getDetail('total.turnover')))->plus($bet['turnover']))->minus($bet['prevTurnover'])->toString();
            $dwlItemDetails['total']['memberWinLoss'] = (new Number($dwl->getDetail('total.memberWinLoss')))->plus($bet['winLoss'])->plus($bet['stake'])->toString();
            $dwlItemDetails['total']['memberAmount'] = (new Number($dwl->getDetail('total.memberAmount')))->plus($bet['winLoss'])->plus($bet['stake'])->toString();
            $dwlItemDetails['total']['calculatedAmount'] = (new Number($dwl->getDetail('total.memberAmount')))->plus($bet['winLoss'])->plus($bet['stake'])->toString();
        }
        $dwlItemDetails['total']['record'] = $dwl->getDetail('total.record');

        return $dwlItemDetails;
    }

    private function generateDWLFile(DWL $dwl): void
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

    private function generateDWLItemFile(DWL $dwl, array $data): void
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
    private function updateCustomerProductBalance(array $bet, MemberProduct $memberProduct, bool $update = false, bool $void = false): void
    {
        if ($void) {
            $stake = (new Number($bet['stake']))->times(WinLossManager::NEGATIVE_INTEGER);
            $winLoss = (new Number($bet['winLoss']))->times(WinLossManager::NEGATIVE_INTEGER);
        } else {
            $stake = new Number($bet['stake']);
            $winLoss = (new Number($bet['winLoss']))->minus($bet['prevWinLoss']);
        }

        $subTotal = $stake->plus($winLoss);
        $newBalance = (new Number($memberProduct->getBalance()))->plus($subTotal)->toString();
        $memberProduct->setBalance($newBalance);
        $this->getCustomerProductRepository()->save($memberProduct);
    }

    private function setTransactionSummary($transaction, $expression): void
    {
        $sumProduct = new Number($transaction->getAmount());
        $sumWithdrawProduct = new Number(0);
        $sumDepositProduct = new Number(0);
        $totalCustomerFee = new Number(0);
        $totalCompanyFee = new Number(0);
        $companyFee = new Number(0);
        $memberFee = new Number(0);

        $values = [
            'sum_products' => $sumProduct->toString(),
            'sum_withdraw_products' => $sumWithdrawProduct->toString(),
            'sum_deposit_products' => $sumDepositProduct->toString(),
            'total_customer_fee' => $totalCustomerFee->toString(),
            'total_company_fee' => $totalCompanyFee->toString(),
            'company_fee' => $companyFee->toString(),
            'customer_fee' => $memberFee->toString(),
        ];

        $totalAmount = $this->processEquation(array_get($expression, 'totalAmount.equation'), array_get($expression, 'totalAmount.variables'), $values);
        $memberAmount = $this->processEquation(array_get($expression, 'customerAmount.equation'), array_get($expression, 'customerAmount.variables'), $values);
        $transaction->setFee('total_customer_fee', $totalCustomerFee->toString());
        $transaction->setFee('total_company_fee', $totalCompanyFee->toString());

        $transaction->setDetail('summary', $values + [
            'total_amount' => $totalAmount->toString(),
            'customer_amount' => $memberAmount->toString(),
        ]);
    }

    private function getDWLItemFile(DWL $dwl): string
    {
        return $this->getContainer()->getParameter('upload_folder') . sprintf('dwl/%s_v_%s.json', $dwl->getId(), $dwl->getVersion());
    }

    private function checkPostBets(array $data): void
    {
        foreach ($data as $postBetDetails) {
            $memberProduct = $this->getCustomerProductRepository()->findOneByBetSyncId($postBetDetails['sync_id']);

            if ($memberProduct instanceOf MemberProduct) {
                foreach ($postBetDetails['bets'] as $key => $bet) {
                    $transaction = $this->getTransactionRepository()->findOneByBetId($bet['bet_id']);

                    if ($transaction instanceOf Transaction) {
                        $this->settlePastTransactionsToPreventReportDiscrepancies($transaction);
                        unset($postBetDetails['bets'][$key]);
                    }
                }

                if (count($postBetDetails['bets']) > 0) {
                    $this->getBrokerageManager()->syncPostBets($memberProduct, $postBetDetails['bets'], true);
                }
            } else {
                continue;
            }
        }
    }

    private function settlePastTransactionsToPreventReportDiscrepancies(Transaction $transaction): void
    {
        $subTransaction = $transaction->getFirstSubTransaction();
        if (!$subTransaction->isBetSettled()) {
            $subTransaction->setDetail('betSettled', true);
            $this->getTransactionRepository()->save($transaction);
        }
    }

    private function computeAndPayoutCommissions(DWL $dwl, array $referrerIds = []): void
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

    private function recountDwlItemsAndSave(DWL $dwl): void
    {
        $subTransactions = $this->getSubTransactionRepository()->findByDwlId($dwl->getId());

        $dwl->setDetail('total.record', count($subTransactions));

        $this->getEntityManager()->persist($dwl);
        $this->getEntityManager()->flush($dwl);
    }

    private function regenerateDwlFile(DWL $dwl): void
    {
        try {
            $regenerateFile = new Job(DwlGenerateFileCommand::COMMAND_NAME,
                [
                    $dwl->getId(),
                    $this->getUser()->getUsername(),
                    '--env',
                    $this->getContainer()->get('kernel')->getEnvironment(),
                ],
                true,
                'dwlFile'
            );
            $this->getEntityManager()->persist($regenerateFile);
            $this->getEntityManager()->flush($regenerateFile);
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

    private function getBrokerageBalanceForDate(\DateTimeInterface $date, int $syncId): string
    {
        $brokerage = $this->brokerage->getMembersComponent()->getMember($syncId, $date);

        return (string) ($brokerage['end_of_day_balance'] ?? '0');
    }
}