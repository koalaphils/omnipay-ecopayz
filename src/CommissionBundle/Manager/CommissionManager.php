<?php

namespace CommissionBundle\Manager;

use DateTimeInterface;
use CurrencyBundle\Manager\CurrencyManager;
use DateTimeImmutable;
use DbBundle\Entity\CommissionPeriod;
use DbBundle\Entity\DWL;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\CommissionPeriodRepository;
use Doctrine\ORM\EntityManager;
use JMS\JobQueueBundle\Entity\Job;
use MemberBundle\Manager\MemberManager;

class CommissionManager
{
    private $currencyManager;
    private $memberManager;
    private $commissionPeriodRepository;
    private $kernelEnvironment;
    private $entityManager;

    public function __construct(string $environment, EntityManager $entityManager)
    {
        $this->kernelEnvironment = $environment;
        $this->entityManager = $entityManager;
    }

    public function setCommissionInformationForTransaction(Transaction $transaction, DWL $dwl): void
    {
        $currencyDateRate = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $dwl->getDate()->format('Y-m-d') . ' 23:59:59'
        );

        if (!$transaction->getCustomer()->hasReferrer()) {
            return;
        }
        $firstSubtransaction = $transaction->getFirstSubTransaction();
        $referrer = $transaction->getCustomer()->getReferrer();
        $referrerCurrency = $transaction->getCustomer()->getReferrer()->getCurrency();
        $currencyRate = $this
            ->getCurrencyManager()
            ->getConvertionRate($transaction->getCurrency(), $referrerCurrency, $currencyDateRate);
        $productCommissionPercentage = $this->getMemberManager()->getMemberCommissionForProductForDate(
            $referrer,
            $firstSubtransaction->getCustomerProduct()->getProduct(),
            $currencyDateRate
        );

        $transaction->computeCommission($currencyRate, $productCommissionPercentage);
    }

    public function getCommissionPeriodForDate(DateTimeInterface $currentDate): ?CommissionPeriod
    {
        return $this
            ->getCommissionPeriodRepository()
            ->getCommissionPeriodForDate($currentDate);
    }

    public function getCommissionPeriodIdThatWasNotYetComputed(): ?CommissionPeriod
    {
        $this->getCommissionPeriodRepository()->reconnectToDatabase();

        return $this->getCommissionPeriodRepository()->getCommissionPeriodIdThatWasNotYetComputed();
    }

    public function getCommissionPeriodIdThatWasNotPaid(): ?CommissionPeriod
    {
        $this->getCommissionPeriodRepository()->reconnectToDatabase();

        return $this->getCommissionPeriodRepository()->getCommissionPeriodIdThatWasNotPaid();
    }

    public function setCurrencyManager(CurrencyManager $currencyManager): void
    {
        $this->currencyManager = $currencyManager;
    }

    public function setMemberManager(MemberManager $memberManager): void
    {
        $this->memberManager = $memberManager;
    }

    private function getMemberManager(): MemberManager
    {
        return $this->memberManager;
    }

    private function getCurrencyManager(): CurrencyManager
    {
        return $this->currencyManager;
    }

    public function setCommissionPeriodRepository(CommissionPeriodRepository $commissionPeriodRepository): void
    {
        $this->commissionPeriodRepository = $commissionPeriodRepository;
    }

    private function getCommissionPeriodRepository(): CommissionPeriodRepository
    {
        return $this->commissionPeriodRepository;
    }

    /**
     * @param int $commissionPeriodId
     * @return bool whether operation was successfull
     */
    public function recomputeAndPayoutCommissionForPeriod(int $commissionPeriodId, string $usernameForAuditLog, bool $forceRecompute = false): bool
    {
        $period = $this->getCommissionPeriodRepository()->find($commissionPeriodId);
        if (!$period instanceof CommissionPeriod) {
            return false;
        }

        try {
            $computeJob = new Job('commission:period:compute',
                [
                    $usernameForAuditLog,
                    '--period',
                    $period->getId(),
                    '--recompute',
                    $forceRecompute ? '1' : '0',
                    '--env',
                    $this->kernelEnvironment,
                ],
                true,
                'payout'
            );

            $payJob = new Job('commission:period:pay',
                [
                    $usernameForAuditLog,
                    '--period',
                    $period->getId(),
                    '--env',
                    $this->kernelEnvironment,
                ],
                true,
                'payout'
            );
            $payJob->addDependency($computeJob);

            $this->entityManager->persist($computeJob);
            $this->entityManager->persist($payJob);
            $this->entityManager->flush($computeJob);
            $this->entityManager->flush($payJob);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
