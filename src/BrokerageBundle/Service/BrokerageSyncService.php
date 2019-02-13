<?php

namespace BrokerageBundle\Service;

use AppBundle\Traits\UserAwareTrait;
use AppBundle\ValueObject\Number;
use BrokerageBundle\Exceptions\UnableToSaveJobException;
use CommissionBundle\Manager\CommissionManager;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\DWL;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\CommissionPeriodRepository;
use DbBundle\Repository\CustomerProductRepository as MemberProductRepository;
use DbBundle\Repository\DWLRepository;
use DbBundle\Repository\SubTransactionRepository;
use Doctrine\ORM\EntityManager;
use DWLBundle\Command\DwlGenerateFileCommand;
use JMS\JobQueueBundle\Entity\Job;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use TransactionBundle\Manager\TransactionManager;

class BrokerageSyncService
{
    use UserAwareTrait;

    private $memberProductRepository;
    private $dwlRepository;
    private $subTransactionRepository;
    private $commissionPeriodRepository;
    private $transactionManager;
    private $recomputeDwlService;
    private $commissionManager;
    private $entityManager;
    private $environment;
    private $tokenStorage;

    public function syncWinLoss(\DateTimeInterface $date, array $members = []): array
    {
        $syncIdsAffected = [];
        $dwls = [];

        foreach ($members as $memberBet) {
            $memberProduct = $this->getMemberProductRepository()->getSyncedMemberProduct($memberBet['sync_id']);
            if (!($memberProduct instanceof MemberProduct)) {
                continue;
            }
            $dwlKey = $memberProduct->getProduct()->getId() . '-' . $memberProduct->getCurrency()->getId();

            if (array_key_exists($dwlKey, $dwls)) {
                $dwl = $dwls[$dwlKey];
            } else {
                $dwl = $this->getDWLRepository()->findDWLByDateProductAndCurrency(
                    $memberProduct->getProduct()->getId(),
                    $memberProduct->getCurrency()->getId(),
                    $date
                );
            }

            if (!($dwl instanceof $dwl)) {
                continue;
            }
            $dwls[$dwlKey] = $dwl;

            $this->syncMemberProductWinLoss($memberProduct, $dwl, $memberBet['win_loss'], $memberBet['turnover'], $memberBet['stake']);
            $syncIdsAffected[] = $memberBet['sync_id'];
        }

        try {
            $this->getEntityManager()->beginTransaction();
            foreach ($dwls as $dwl) {
                $totalRecord = $this->getSubTranactionManager()->getTotalSubTransactionForDwl($dwl->getId());
                $dwl->setTotalRecord($totalRecord);
                $this->getEntityManager()->persist($dwl);
                $this->getEntityManager()->flush($dwl);

                $this->addJobForRegenerateDwlFile($dwl);
            }
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
        }

        try {
            foreach ($this->getCommissionPeriodsForDwls($dwls) as $period) {
                $this
                    ->getCommissionManager()
                    ->recomputeAndPayoutCommissionForPeriod($period->getId(), $this->_getUser()->getUsername());
            }
        } catch (Exception $ex) {
            // Do nothing this method must be successfull even a job for commission got an error.
        }

        return $syncIdsAffected;
    }

    public function syncMemberProductWinLoss(
        MemberProduct $memberProduct,
        DWL $dwl,
        string $winLoss,
        string $turnover,
        string $stake
    ): void {
        $subTransaction = $this
            ->getSubTransactionRepository()
            ->getSubTransactionByDwlAndMemberProduct($dwl->getId(), $memberProduct->getId());
        if (!($subTransaction instanceof SubTransaction)) {
            $subTransaction = $this
                ->getRecomputeDwlService()
                ->generateSubTransaction($memberProduct, $dwl, $winLoss, $turnover, $stake);
        } else {
            $this->getRecomputeDwlService()->revertBalance($memberProduct, $subTransaction);
            $this->getRecomputeDwlService()->updateSubTransaction($subTransaction, $winLoss, $turnover, $stake);
        }
        $transaction = $subTransaction->getParent();
        $this->getCommissionManager()->setCommissionInformationForTransaction($transaction, $dwl);
        $memberProduct->addAmountToBalance($subTransaction->getConvertedAmount());

        try {
            $this->getEntityManager()->beginTransaction();
            $this->getEntityManager()->persist($memberProduct);
            $this->getEntityManager()->flush($memberProduct);
            $this->getEntityManager()->persist($transaction);
            $this->getEntityManager()->flush($transaction);
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();

            throw $e;
        }
    }

    private function addJobForRegenerateDwlFile(DWL $dwl): void
    {
        try {
            $job = new Job(DwlGenerateFileCommand::COMMAND_NAME, [
                $dwl->getId(),
                '--env',
                $this->environment,
            ]);

            $this->getEntityManager()->persist($job);
            $this->getEntityManager()->flush($job);
        } catch (\Exception $e) {
            throw new UnableToSaveJobException(sprintf('Unable to save job: regenerate file for dwl %s', $dwl->getId()), $e->getCode(), $e);
        }
    }

    private function getCommissionPeriodsForDwls(array $dwls): array
    {
        $fromDate = null;
        $toDate = null;

        foreach ($dwls as $dwl) {
            $dwlDate = \DateTimeImmutable::createFromMutable($dwl->getDate());
            if (is_null($fromDate)) {
                $fromDate = $dwlDate;
            } elseif ($dwlDate < $fromDate) {
                $fromDate = $dwlDate;
            }

            if (is_null($toDate)) {
                $toDate = $dwlDate;
            } elseif ($dwlDate > $toDate) {
                $toDate = $dwlDate;
            }
        }

        return $this->getCommissionPeriodRepository()->getCommissionPeriodsForDateRange($fromDate, $toDate);
    }

    public function setMemberProductRepository(MemberProductRepository $memberProductRepository): self
    {
        $this->memberProductRepository = $memberProductRepository;

        return $this;
    }

    public function setDwlRepository(DWLRepository $dwlRepository): self
    {
        $this->dwlRepository = $dwlRepository;

        return $this;
    }

    public function setSubTransactionRepository(SubTransactionRepository $subTransactionRepository): self
    {
        $this->subTransactionRepository = $subTransactionRepository;

        return $this;
    }

    public function setTransactionManager(TransactionManager $transactionManager): self
    {
        $this->transactionManager = $transactionManager;

        return $this;
    }

    public function setRecomputeDwlService(RecomputeDwlService $recomputeDwlService): self
    {
        $this->recomputeDwlService = $recomputeDwlService;

        return $this;
    }

    public function setCommissionManager(CommissionManager $commissionManager): self
    {
        $this->commissionManager = $commissionManager;

        return $this;
    }

    public function setEntityManager(EntityManager $entityManager): self
    {
        $this->entityManager = $entityManager;

        return $this;
    }

    public function setCommissionPeriodRepository(CommissionPeriodRepository $commissionPeriodRepository): self
    {
        $this->commissionPeriodRepository = $commissionPeriodRepository;

        return $this;
    }

    public function setTokenStorage(TokenStorageInterface $tokenStorage): self
    {
        $this->tokenStorage = $tokenStorage;

        return $this;
    }

    public function setKernelEnvironment(string $environment): self
    {
        $this->environment = $environment;

        return $this;
    }

    private function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    private function getCommissionManager(): CommissionManager
    {
        return $this->commissionManager;
    }

    private function getMemberProductRepository(): MemberProductRepository
    {
        return $this->memberProductRepository;
    }

    private function getDwlRepository(): DWLRepository
    {
        return $this->dwlRepository;
    }

    private function getSubTranactionManager(): SubTransactionRepository
    {
        return $this->subTransactionRepository;
    }

    private function getTansactionManager(): TransactionManager
    {
        return $this->transactionManager;
    }

    private function getRecomputeDwlService(): RecomputeDwlService
    {
        return $this->recomputeDwlService;
    }

    private function getCommissionPeriodRepository(): CommissionPeriodRepository
    {
        return $this->commissionRepository;
    }

    protected function getSecurityTokenStorage()
    {
        return $this->tokenStorage;
    }

    protected function hasSecurityTokenStorage()
    {
        return $this->tokenStorage instanceof TokenStorageInterface;
    }
}
