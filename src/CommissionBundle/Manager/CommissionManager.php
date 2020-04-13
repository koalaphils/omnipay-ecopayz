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
    public function recomputeAndPayoutRevenueShareForPeriod(int $commissionPeriodId, string $usernameForAuditLog, string $action): bool
    {
        $period = $this->getCommissionPeriodRepository()->find($commissionPeriodId);
        if (!$period instanceof CommissionPeriod) {
            return false;
        }

        try {

            if ($action == 'pay'){
                $arrayJob = [
                    'system',
                    '--period',
                    $period->getId(),
                    '--env',
                    $this->kernelEnvironment,
                ];
            } else {
                $arrayJob = [
                    'system',
                    '--period',
                    $period->getId(),
                    '--env',
                    $this->kernelEnvironment,
                ];
            }

            $computeJob = new Job('revenueshare:period:'.$action,
                $arrayJob,
                true,
                $action
            );

            $this->entityManager->persist($computeJob);
            $this->entityManager->flush($computeJob);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
