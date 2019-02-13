<?php

namespace BrokerageBundle\Service;

use AppBundle\ValueObject\Number;
use BrokerageBundle\Exceptions\NoMemberProductException;
use CommissionBundle\Manager\CommissionManager;
use DbBundle\Entity\AuditRevisionLog;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\DWL;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\AuditRevisionRepository;
use DbBundle\Repository\CustomerProductRepository as MemberProductRepository;
use DbBundle\Repository\DWLRepository;
use DbBundle\Repository\SubTransactionRepository;
use DbBundle\Repository\TransactionRepository;
use Doctrine\ORM\EntityManager;
use TransactionBundle\Manager\TransactionManager;

class RecomputeDwlService
{
    private $transactionRepository;
    private $auditRepository;
    private $memberProductRepository;
    private $subTransactionRepository;
    private $dwlRepository;
    private $commissionManager;
    private $transactionManager;
    /**
     *
     * @var EntityManager
     */
    private $entityManager;

    public function recomputeDWLForMemberProduct(
        MemberProduct $memberProduct,
        DWL $dwl,
        string $winLoss,
        string $stake,
        string $turnover,
        string $currentBalance
    ): SubTransaction {
        $subTransactions = $this
            ->subTransactionRepository
            ->getSubTransactionsByDwlAndMemberProduct($dwl->getId(), $memberProduct->getId());

        for ($i = 1; $i < count($subTransactions); $i++) {
            $this->deleteMultipleSubTransaction($subTransactions[$i]);
        }

        $subTransaction = $subTransactions[0];

        if (!($subTransaction instanceof SubTransaction)) {
            $subTransaction = new SubTransaction();
            $subTransaction->setCustomerProduct($memberProduct);
        }

        $this->recomputeDWLSubTransaction($subTransaction, $dwl, $winLoss, $stake, $turnover, $currentBalance);
        $subTransaction->setDWLExcludeInList(false);
        $this->save($subTransaction);

        return $subTransaction;
    }

    public function updateDWLForMemberProduct(
        MemberProduct $memberProduct,
        DWL $dwl,
        string $winLoss,
        string $stake,
        string $turnover,
        ?string $currentBalance,
        bool $excludeInList = false
    ): SubTransaction {
        $subTransactions = $this
            ->subTransactionRepository
            ->getSubTransactionsByDwlAndMemberProduct($dwl->getId(), $memberProduct->getId());

        for ($i = 1; $i < count($subTransactions); $i++) {
            $this->deleteMultipleSubTransaction($subTransactions[$i]);
        }

        $subTransaction = $subTransactions[0];

        if (is_null($subTransaction)) {
            $subTransaction = new SubTransaction();
            $subTransaction->setCustomerProduct($memberProduct);
            $this->updateNewSubTransaction($subTransaction, $dwl, $winLoss, $turnover, $stake, $currentBalance);
        }

        $this->updateSubTransaction($subTransaction, $winLoss, $turnover, $stake, $currentBalance);
        $subTransaction->setDWLExcludeInList($excludeInList);
        $this->saveSubtransactionOnly($subTransaction);

        return $subTransaction;
    }

    public function recomputeDWLSubTransaction(
        SubTransaction $subTransaction,
        DWL $dwl,
        string $winLoss,
        string $stake,
        string $turnover,
        ?string $currentBalance
    ): void {
        if (!is_null($subTransaction->getId())) {
            $this->revertBalance($subTransaction->getCustomerProduct(), $subTransaction);
            $this->updateSubTransaction($subTransaction, $winLoss, $turnover, $stake, $currentBalance);
        } else {
            $this->updateNewSubTransaction($subTransaction, $dwl, $winLoss, $turnover, $stake, $currentBalance);
        }

        $transaction = $subTransaction->getParent();
        $this->commissionManager->setCommissionInformationForTransaction($transaction, $dwl);
        $subTransaction->getCustomerProduct()->addAmountToBalance($subTransaction->getConvertedAmount());
    }

    public function changeToZeroAllSubtransactionNotInGivenIds(array $subTransactionIds, DWL $dwl): void
    {
        $this->subTransactionRepository->reconnectToDatabase();
        $subTransactions = $this->subTransactionRepository->getDwlSubTransactionNotInGivenIds($subTransactionIds, $dwl->getId());
        foreach ($subTransactions as $result) {
            $subTransaction = $result[0];
            $this->recomputeDWLSubTransaction($subTransaction, $dwl, '0', '0', '0', null);
            $subTransaction->setDWLExcludeInList(true);
            $this->save($subTransaction);
        }
    }

    public function changeToZeroSubtransaction(DWL $dwl, int $subtransactionId, bool $updateMemberProductBalance = true): void
    {
        $subTransaction = $this->subTransactionRepository->getDwlSubTransactionById($subtransactionId);
        if ($updateMemberProductBalance) {
            $this->recomputeDWLSubTransaction($subTransaction, $dwl, '0', '0', '0', null);
            $subTransaction->setDWLExcludeInList(true);
            $this->save($subTransaction);
        } else {
            $this->updateSubTransaction($subTransaction, '0', '0', '0', null);
            $subTransaction->setDWLExcludeInList(true);
            $this->saveSubtransactionOnly($subTransaction);
        }
    }

    public function revertBalance(MemberProduct $memberProduct, SubTransaction $subTransaction): void
    {
        $totalAdded = $this->computeTotalAmountFromLog($subTransaction);
        $memberProduct->setBalance(Number::sub($memberProduct->getBalance(), $totalAdded->toString())->toString());
    }

    public function updateSubTransaction(
        SubTransaction $subTransaction,
        string $winLoss,
        string $turnover,
        string $stake,
        ?string $currentBalance
    ): void {
        $subTransaction
            ->setAmount($winLoss)
            ->setDwlGrossCommission('0.00')
            ->setDwlWinLoss($winLoss)
            ->setDwlTurnover($turnover)
            ->setDwlCommission('0.00')
            ->setDwlBrokerageStake($stake)
            ->setDwlBrokerageWinLoss($winLoss);

        if (!is_null($currentBalance)) {
            $subTransaction->setDwlCustomerBalance($currentBalance);
        }

        $transaction = $subTransaction->getParent();
        $transaction->setSubTransactions([$subTransaction]);

        $this->transactionManager->processTransactionSummary($transaction);
        $transaction->retainImmutableDataForDWL();
        $transaction->retainImmutableData();
    }

    public function updateNewSubTransaction(
        SubTransaction $subTransaction,
        DWL $dwl,
        string $winLoss,
        string $turnover,
        string $stake,
        ?string $currentBalance
    ): SubTransaction {
        $memberProduct = $subTransaction->getCustomerProduct();

        $transaction = new Transaction();
        $transaction
            ->setCustomer($memberProduct->getCustomer())
            ->setType(Transaction::TRANSACTION_TYPE_DWL)
            ->setCurrency($memberProduct->getCurrency())
            ->setDwlId($dwl->getId())
            ->setNumber(sprintf(
                '%s-%s-%s-%s',
                date('Ymd-His'),
                Transaction::TRANSACTION_TYPE_DWL,
                $dwl->getId(),
                $memberProduct->getBrokerageSyncId()
            ))
            ->setDate(new \DateTime('now'))
            ->setStatus(Transaction::TRANSACTION_STATUS_END);

        $subTransaction
            ->setType(Transaction::TRANSACTION_TYPE_DWL)
            ->setAmount($winLoss)
            ->setDwlId($dwl->getId())
            ->setDwlGrossCommission('0.00')
            ->setDwlWinLoss($winLoss)
            ->setDwlTurnover($turnover)
            ->setDwlCommission('0.00')
            ->setDwlBrokerageStake($stake)
            ->setDwlBrokerageWinLoss($winLoss);

        if (is_null($currentBalance)) {
            $subTransaction->setDwlCustomerBalance($subTransaction->getCustomerProduct()->getBalance());
        } else {
            $subTransaction->setDwlCustomerBalance($currentBalance);
        }

        $transaction->addSubTransaction($subTransaction);

        $this->transactionManager->processTransactionSummary($transaction);
        $transaction->retainImmutableDataForDWL();
        $transaction->retainImmutableData();

        return $subTransaction;
    }

    public function computeTotalAmountFromLog(SubTransaction $subTransaction): Number
    {
        $totalAdded = new Number(0);
        $lastAuditLog = $this->auditRepository->getLastAuditLogFor(
            (string) $subTransaction->getId(),
            SubTransaction::class,
            AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_DWL
        );
        $auditLogs = $this->auditRepository->getAuditLogsForDWLSubtransactionIdentifier(
            $subTransaction,
            \DateTimeImmutable::createFromMutable($subTransaction->getParent()->getCreatedAt())->modify('-1 second'),
            \DateTimeImmutable::createFromMutable($lastAuditLog->getAuditRevision()->getTimestamp())->modify('+1 second')
        );

        $lastCustomerProductLog = null;
        $lastCustomerProductLogWithSubtransaction = null;

        while ($auditLogs->next()) {
            $auditLog = $auditLogs->current()[0];
            if ($auditLog->getCategory() === AuditRevisionLog::CATEGORY_CUSTOMER_PRODUCT && $auditLog->hasDetail('fields.balance')) {
                $lastCustomerProductLog = $auditLog;
            } elseif ($auditLog->getCategory() === AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_DWL && !is_null($lastCustomerProductLog)) {
                $beforeBalance = new Number($lastCustomerProductLog->getDetail('fields.balance.0', '0.00'));
                $updatedBalance = new Number($lastCustomerProductLog->getDetail('fields.balance.1', '0.00'));
                if ($updatedBalance->equals($auditLog->getDetail('details.customerProduct.balance'))) {
                    $totalAdded = $totalAdded->plus($updatedBalance->minus($beforeBalance->toString())->toString());
                    $lastCustomerProductLogWithSubtransaction = $lastCustomerProductLog;
                } else {
                    $lastCustomerProductLogWithSubtransaction = null;
                }
                $lastCustomerProductLog = null;
            }
        }

        return $totalAdded;
    }

    public function deleteMultipleSubTransaction(SubTransaction $subtransaction): void
    {
        $transaction = $subtransaction->getParent();
        $this->entityManager->remove($subtransaction);
        $this->entityManager->flush($subtransaction);
        $this->entityManager->remove($transaction);
        $this->entityManager->flush($transaction);
    }

    public function setMemberProductRepository(MemberProductRepository $memberProductRepository): void
    {
        $this->memberProductRepository = $memberProductRepository;
    }

    public function setTransactionRepository(TransactionRepository $transactionRepository): void
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function setSubTransactionRepository(SubTransactionRepository $subTransactionRepository): void
    {
        $this->subTransactionRepository = $subTransactionRepository;
    }

    public function setAuditRepository(AuditRevisionRepository $auditRepository): void
    {
        $this->auditRepository = $auditRepository;
    }

    public function setDWLRepository(DWLRepository $dwlRepository): void
    {
        $this->dwlRepository = $dwlRepository;
    }

    public function setCommissionManager(CommissionManager $commissionManager): void
    {
        $this->commissionManager = $commissionManager;
    }

    public function setTransactionManager(TransactionManager $transactionManager): void
    {
        $this->transactionManager = $transactionManager;
    }

    public function setEntityManager(EntityManager $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    private function saveSubtransactionOnly(SubTransaction $subTransaction): void
    {
        $transaction = $subTransaction->getParent();
        try {
            $this->entityManager->beginTransaction();
            $this->entityManager->persist($subTransaction);
            $this->entityManager->flush($subTransaction);
            $this->entityManager->persist($transaction);
            $this->entityManager->flush($transaction);
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();

            throw $e;
        }
    }

    private function save(SubTransaction $subTransaction): void
    {
        $transaction = $subTransaction->getParent();
        try {
            $this->entityManager->beginTransaction();
            $this->entityManager->persist($subTransaction->getCustomerProduct());
            $this->entityManager->flush($subTransaction->getCustomerProduct());
            $this->entityManager->persist($transaction);
            $this->entityManager->flush($transaction);
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();

            throw $e;
        }
    }
}
