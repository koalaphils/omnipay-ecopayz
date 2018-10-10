<?php

namespace DWLBundle\Service;

use AppBundle\ValueObject\Number;
use CommissionBundle\Manager\CommissionManager;
use CommissionBundle\Service\CommissionDWLTransactionService;
use CurrencyBundle\Manager\CurrencyManager;
use DateTime;
use DateTimeImmutable;
use DbBundle\Entity\CommissionSchedule;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\DWL;
use DbBundle\Entity\MemberRunningCommission;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\CommissionScheduleRepository;
use DbBundle\Repository\CustomerProductRepository as MemberProductRepository;
use DbBundle\Repository\MemberRunningCommissionRepository;
use DbBundle\Repository\TransactionRepository;
use LogicException;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class DWLSubmitService extends AbstractDWLService
{
    const RESUBMIT_DEFAULT_ACTION = 0;

    private $dwlNeedsToGenerateFile = [];

    public function processDWl(DWl $dwl, bool $forceSubmition = false): array
    {
        try {
            $this->guardProcessDWL($dwl);
        } catch (\Exception $e) {
            $this->log($e->getMessage(), 'error', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
        $data = $this->getData($dwl);
        if ($forceSubmition === false && $data['totalErrors'] > 0) {
            $errorMessage = sprintf('DWL has %s errors', $data['totalErrors']);
            $this->log($errorMessage, 'error');

            return ['success' => false, 'error' => $errorMessage];
        }

        $this->log('Update DWL status to Transacting');
        $this->updateDWL($dwl, DWL::DWL_STATUS_TRANSACTING);
        $this->updateProcess($dwl, 0, $data['totalItems']);

        $this->log('Start processing data');
        $processedData = $this->processData($dwl, $data['data']);
        $this->log('Process Done');

        $this->log('Start deleting old transactions that are not included in new version');
        $deleted = $this->deleteItems($dwl, $processedData['transactionsIds']);
        $this->log('Deletion completed', 'info', ['deletedItems' => $deleted]);

        $this->updateDWL($dwl, DWL::DWL_STATUS_COMPLETED);
        $this->updateProcess($dwl, $data['totalItems'], $data['totalItems']);

        return ['success' => true];
    }

    private function deleteItems(DWL $dwl, array $transactionIds)
    {
        $transactions = $this->getTransactionRepository()->findByNotInIds($transactionIds, $dwl->getId());
        $this->getEntityManager()->beginTransaction();
        foreach ($transactions as $transaction) {
            foreach ($transaction->getSubTransactions() as $subTransaction) {
                $customerProduct = $subTransaction->getCustomerProduct();
                $this->getTransactionManager()->revertCustomerProductBalance($customerProduct, $subTransaction);
                $this->getEntityManager()->persist($customerProduct);
                $this->getEntityManager()->flush($customerProduct);
                $this->getEntityManager()->remove($subTransaction);
            }
            $this->getEntityManager()->remove($transaction);
        }
        $this->getEntityManager()->commit();
    }

    private function processData(DWL $dwl, array $data): array
    {
        $submitedItems = [];
        $submitedTotals = [
            'turnover' => 0,
            'grossCommission' => 0,
            'memberWinLoss' => 0,
            'memberCommission' => 0,
            'memberAmount' => 0,
            'record' => 0,
        ];

        $transactionIds = [];
        foreach ($data['items'] as $item) {
            if (count($item['errors']) > 0) {
                continue;
            }
            $submitedItems[] = $item;
            $this->computeTotals($submitedTotals, $item);
            $transaction = $this->processItem($dwl, $item);
            $transactionIds[] = $transaction->getId();
            array_set($item, 'transaction.id', $transaction->getId());
            array_set($item, 'transaction.subId', $transaction->getFirstSubTransaction()->getId());
            $this->updateProcess($dwl, count($transactionIds), $data['total']['record']);
        }
        $dwl->setDetail('total.record', $submitedTotals['record']);
        return ['items' => $submitedItems, 'total' => $submitedTotals, 'transactionsIds' => $transactionIds];
    }

    private function processItem(DWL $dwl, array $item): Transaction
    {
        $differenceBalance = 0;
        $transaction = $this->generateTransaction($dwl, $item, $differenceBalance);
        $subtransaction = $transaction->getFirstSubTransaction();
        $customerProduct = $subtransaction->getCustomerProduct();

        $item['customer'] = ['balance' => $subtransaction->getDetail('dwl.customer.balance')];
        try {
            $this->getTransactionManager()->beginTransaction();
            $this->getCommissionManager()->setCommissionInformationForTransaction($transaction, $dwl);
            $this->saveTransaction($transaction);
            $this->getDWLManager()->updateDWLItemBalance(
                $customerProduct,
                $dwl->getDate(),
                $differenceBalance,
                $this->dwlNeedsToGenerateFile
            );
            $this->getTransactionManager()->commit();

            return $transaction;
        } catch (\Exception $e) {
            $this->getTransactionManager()->rollback();
            $this->log($e->getMessage(), 'error', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
            ]);

            throw $e;
        }
    }

    private function saveTransaction(Transaction $transaction)
    {
        $action = 'new';
        if (!$transaction->isNew()) {
            $action = $this->getSettingManager()->getSetting('dwl.resubmit.action', self::RESUBMIT_DEFAULT_ACTION);
        }
        $this->getTransactionManager()->processTransaction($transaction, $action);
    }

    private function generateTransaction(DWL $dwl, array $item, &$differenceBalance = 0)
    {
        $currencyDateRate = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $dwl->getDate()->format('Y-m-d') . ' 23:59:59'
        );
        $subtransaction = $this->getTransactionRepository()->getSubtransactionByProductAndDwlId($item['id'], $dwl->getId());
        if ($subtransaction !== null) {
            $differenceBalance = (new Number($subtransaction->getAmount()))->minus($item['amount']);
            $revertedBalance = $this->revertBalance($subtransaction->getCustomerProduct(), $subtransaction);
            $subtransaction->getCustomerProduct()->setBalance($revertedBalance);
            if ($subtransaction->getDetail('dwl.customer.balance', null) === null) {
                $subtransaction->setDetail('dwl.customer.balance', $revertedBalance);
            }
            $transaction = $subtransaction->getParent();
        } else {
            $customerProduct = $this->getMemberProductRepository()->find($item['id']);
            $transaction = new Transaction();
            $transaction->setCustomer($customerProduct->getCustomer());
            $transaction->setType(Transaction::TRANSACTION_TYPE_DWL);
            $transaction->setCurrency($dwl->getCurrency());
            $transaction->setDetail('dwl', ['id' => $dwl->getId()]);

            $subtransaction = new SubTransaction();
            $subtransaction->setType(Transaction::TRANSACTION_TYPE_DWL);
            $subtransaction->setCustomerProduct($customerProduct);
            $subtransaction->setDetail('dwl.customer.balance', $customerProduct->getBalance());
            $transaction->addSubTransaction($subtransaction);
        }

        $transaction->setNumber(sprintf('%s-%s-%s-%s', date('Ymd-His'), Transaction::TRANSACTION_TYPE_DWL, $dwl->getId(), $item['id']));
        $transaction->setDate(new DateTime());
        $subtransaction->setDetail('dwl.id', $dwl->getId());
        $subtransaction->setDetail('dwl.turnover', $item['turnover']);
        $subtransaction->setDetail('dwl.gross', $item['gross']);
        $subtransaction->setDetail('dwl.winLoss', $item['winLoss']);
        $subtransaction->setDetail('dwl.commission', $item['commission']);
        $subtransaction->setAmount($item['amount']);
        $transaction->retainImmutableDataForDWL();

        return $transaction;
    }

    private function revertBalance(MemberProduct $customerProduct, SubTransaction $subtransaction)
    {
        $currentBalance = $customerProduct->getBalance();
        $subtransactionAmount = $subtransaction->getDetail('convertedAmount', $subtransaction->getAmount());

        return (new Number($currentBalance))->minus($subtransactionAmount)->__toString();
    }

    private function computeTotals(array &$totals, array $item)
    {
        $totals['record'] += 1;
        $totals['turnover'] = (new Number($totals['turnover']))->minus($item['turnover'])->__toString();
        $totals['grossCommission'] = (new Number($totals['grossCommission']))->minus($item['gross'])->__toString();
        $totals['memberWinLoss'] = (new Number($totals['memberWinLoss']))->minus($item['winLoss'])->__toString();
        $totals['memberCommission'] = (new Number($totals['memberCommission']))->minus($item['commission'])->__toString();
        $totals['memberAmount'] = (new Number($totals['memberAmount']))->minus($item['amount'])->__toString();
    }

    private function getData(DWL $dwl)
    {
        $jsonFileName = $this->getMediaManager()->getFilePath(sprintf("dwl/%s_v_%s.json", $dwl->getId(), $dwl->getVersion()));
        $data = \GuzzleHttp\json_decode(file_get_contents($jsonFileName), true);
        $totalErrors = 0;
        foreach ($data['items'] as $item) {
            $itemTotalErrors = count($item['errors'] ?? []);
            if ($itemTotalErrors > 0) {
                $totalErrors += $itemTotalErrors;
            }
        }

        return [
            'data' => $data,
            'totalErrors' => $totalErrors,
            'totalItems' => count($data['items']),
        ];
    }

    private function guardProcessDWL(DWL $dwl, bool $forceSubmition = false)
    {
        if ($dwl->getStatus() !== DWL::DWL_STATUS_SUBMITED) {
            throw new LogicException("DWL can't be processed since it was not submited");
        }

        $jsonFileName = sprintf("dwl/%s_v_%s.json", $dwl->getId(), $dwl->getVersion());
        if (!$this->getMediaManager()->isFileExists($jsonFileName)) {
            throw new FileNotFoundException($jsonFileName);
        }
    }

    private function getMemberProductRepository(): MemberProductRepository
    {
        return $this->getEntityManager()->getRepository(MemberProduct::class);
    }
    
    private function getCommissionManager(): CommissionManager
    {
        return $this->container->get('commission.manager');
    }

    private function getCurrencyManager(): CurrencyManager
    {
        return $this->container->get('currency.manager');
    }
}
