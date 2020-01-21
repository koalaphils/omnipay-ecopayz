<?php

namespace DbBundle\Repository;

use DateTime;
use DateTimeInterface;
use DbBundle\Entity\CommissionPeriod;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\DWL;
use DbBundle\Entity\Transaction;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;

class CommissionPeriodRepository extends BaseRepository
{
    public function getCommissionPeriodList(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $queryBuilder = $this->createQueryBuilder('cs');
        $queryBuilder
            ->orderBy('cs.dwlDateFrom', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);
        if (array_has($filters, 'statuses')) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('cs.status', ':statuses'));
            $queryBuilder->setParameter('statuses', $filters['statuses']);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getCommissionPeriodListWithStatuses(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $queryBuilder = $this->createQueryBuilder('cs');
        $queryBuilder
            ->select(
                'cs as commissionPeriod',
                'COUNT(mc.id) totalCommission',
                'SUM(IF(mc.processStatus = :noAction, 1, 0)) totalNoActionYet',
                'SUM(IF(mc.processStatus = :computing, 1, 0)) totalComputing',
                'SUM(IF(mc.processStatus = :computed, 1, 0)) totalComputed',
                'SUM(IF(mc.processStatus = :computingError, 1, 0)) totalComputingError',
                'SUM(IF(mc.processStatus = :paying, 1, 0)) totalPaying',
                'SUM(IF(mc.processStatus = :paid, 1, 0)) totalPaid',
                'SUM(IF(mc.processStatus = :payingError, 1, 0)) totalPayingError'
            )
            ->leftJoin(\DbBundle\Entity\MemberRunningCommission::class, 'mc', Query\Expr\Join::WITH, 'mc.commissionPeriod = cs.id')
            ->orderBy('cs.dwlDateFrom', 'DESC')
            ->groupBy('cs.id')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->setParameters([
                'noAction' => \DbBundle\Entity\MemberRunningCommission::PROCESS_STATUS_NONE,
                'computing' => \DbBundle\Entity\MemberRunningCommission::PROCESS_STATUS_COMPUTING,
                'computed' => \DbBundle\Entity\MemberRunningCommission::PROCESS_STATUS_COMPUTED,
                'computingError' => \DbBundle\Entity\MemberRunningCommission::PROCESS_STATUS_COMPUTATION_ERROR,
                'paying' => \DbBundle\Entity\MemberRunningCommission::PROCESS_STATUS_PAYING,
                'paid' => \DbBundle\Entity\MemberRunningCommission::PROCESS_STATUS_PAID,
                'payingError' => \DbBundle\Entity\MemberRunningCommission::PROCESS_STATUS_PAY_ERROR,
            ]);
        if (array_has($filters, 'statuses')) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('cs.status', ':statuses'));
            $queryBuilder->setParameter('statuses', $filters['statuses']);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function countCommissionList(array $filters = []): int
    {
        $queryBuilder = $this->createQueryBuilder('cs');
        $queryBuilder->select('COUNT(cs.id) as total');
        if (array_has($filters, 'statuses')) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('cs.status', ':statuses'));
            $queryBuilder->setParameter('statuses', $filters['statuses']);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getCommissionForDWL(DWL $dwl): ?CommissionPeriod
    {
        return $this->getCommissionPeriodForDate($dwl->getDate());
    }

    public function getLastCommissionPeriod(): ?CommissionPeriod
    {
        $queryBuilder = $this->createQueryBuilder('cs');
        $queryBuilder->orderBy('cs.dwlDateTo', 'DESC')
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getCommissionPeriodForDate(DateTimeInterface $date): ?CommissionPeriod
    {
        $queryBuilder = $this->createQueryBuilder('cs');
        $queryBuilder->where('cs.dwlDateTo >= :date')
            ->orderBy('cs.dwlDateTo', 'ASC')
            ->setMaxResults(1)
            ->setParameter('date', $date->format('Y-m-d').' 00:00:00');

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getPreceedingCommissionPeriod(CommissionPeriod $commissionPeriod): ?CommissionPeriod
    {
        $queryBuilder = $this->createQueryBuilder('cs');
        $queryBuilder->where('cs.dwlDateTo < :date')
            ->orderBy('cs.dwlDateTo', 'DESC')
            ->setMaxResults(1)
            ->setParameter('date', $commissionPeriod->getDWLDateFrom());

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getSucceedingCommissionPeriod(CommissionPeriod $commissionPeriod): ?CommissionPeriod
    {
        $queryBuilder = $this->createQueryBuilder('cs');
        $queryBuilder->where('cs.dwlDateTo > :date')
            ->orderBy('cs.dwlDateTo', 'ASC')
            ->setMaxResults(1)
            ->setParameter('date', $commissionPeriod->getDWLDateTo());

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getLastCommissionThatWasExecuted(): ?CommissionPeriod
    {
        $queryBuilder = $this->createQueryBuilder('cs');
        $queryBuilder->where('cs.status = :status')
            ->orderBy('cs.dwlDateFrom', 'DESC')
            ->setMaxResults(1)
            ->setParameter('status', CommissionPeriod::STATUS_SUCCESSFULL_EXECUTION);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getCommissionPeriodThatWasNotExecutedOnOrBefore(
        DateTimeInterface $date,
        bool $execution = false
    ): ?CommissionSchedule {
        $queryBuilder = $this->createQueryBuilder('cs');
        if ($execution) {
            $queryBuilder->where('cs.payoutAt <= :date')
                ->orderBy('cs.payoutAt', 'ASC');
        } else {
            $queryBuilder->where('cs.dwlDateFrom <= :date')
                ->orderBy('cs.dwlDateFrom', 'DESC');
        }
        $queryBuilder
            ->andWhere('cs.status = :status')
            ->setMaxResults(1)
            ->setParameter('date', $date)
            ->setParameter('executed', CommissionPeriod::STATUS_NOT_YET_EXECUTED);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getCommissionPeriodsAfterTheDate(DateTimeInterface $date): array
    {
        $queryBuilder = $this->createQueryBuilder('cp');
        $queryBuilder
            ->where($queryBuilder->expr()->gt('cp.dwlDateTo', ':date'))
            ->orderBy('cp.dwlDateTo', 'ASC')
            ->setParameter('date', $date);

        return $queryBuilder->getQuery()->getResult();
    }

    public function getCommissionPeriodBeforeOrOnTheDate(DateTimeInterface $date): ?CommissionPeriod
    {
        $queryBuilder = $this->createQueryBuilder('cp');
        $queryBuilder
            ->where($queryBuilder->expr()->lte('cp.dwlDateFrom', ':date'))
            ->orderBy('cp.dwlDateFrom', 'DESC')
            ->setParameter('date', $date)
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getCommissionPeriodIdThatWasNotYetComputed(): ?CommissionPeriod
    {
        $now = new DateTime('now');
        $queryBuilder = $this->createQueryBuilder('cp');
        $queryBuilder
            ->where($queryBuilder->expr()->andX(
                $queryBuilder->expr()->lte('cp.dwlDateTo', ':date'),
                $queryBuilder->expr()->eq('cp.status', ':status')
            ))
            ->orderBy('cp.dwlDateTo', 'ASC')
            ->setMaxResults(1)
            ->setParameter('date', $now)
            ->setParameter('status', CommissionPeriod::STATUS_NOT_YET_COMPUTED)
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getCommissionPeriodIdThatWasNotPaid(): ?CommissionPeriod
    {
        $now = new DateTime('now');
        $queryBuilder = $this->createQueryBuilder('cp');
        $queryBuilder
            ->where($queryBuilder->expr()->andX(
                $queryBuilder->expr()->lte('cp.payoutAt', ':date'),
                $queryBuilder->expr()->eq('cp.status', ':status')
            ))
            ->orderBy('cp.payoutAt', 'ASC')
            ->setMaxResults(1)
            ->setParameter('date', $now)
            ->setParameter('status', CommissionPeriod::STATUS_SUCCESSFULL_COMPUTATION)
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getReferrersIdForCommissionPeriod(CommissionPeriod $period, array $memberIds = []): IterableResult
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder
            ->select('m.id')
            ->from(Member::class, 'm')
            ->innerJoin(Member::class, 'r', Join::WITH, $queryBuilder->expr()->andX('r.affiliate = m.id'))
            ->innerJoin(Transaction::class, 't', Join::WITH, $queryBuilder->expr()->andX()->addMultiple([
                $queryBuilder->expr()->eq('t.customer', 'r.id'),
                $queryBuilder->expr()->isNotNull('t.dwlId'),
                $queryBuilder->expr()->eq('t.type', ':type')
            ]))
            ->innerJoin(DWL::class, 'd', Join::WITH, $queryBuilder->expr()->andX()->addMultiple([
                $queryBuilder->expr()->eq('d.id', 't.dwlId'),
                $queryBuilder->expr()->gte('d.date', ':fromDate'),
                $queryBuilder->expr()->lte('d.date', ':toDate')
            ]))
            ->groupBy('m.id')
            ->setParameters([
                'type' => Transaction::TRANSACTION_TYPE_DWL,
                'fromDate' => $period->getDWLDateFrom(),
                'toDate' => $period->getDWLDateTo(),
            ])
        ;
        if(!empty($memberIds)) {
            $queryBuilder
                ->where($queryBuilder->expr()->in('m.id', ':memberIds'))
                ->setParameter('memberIds', $memberIds);
        }


        return $queryBuilder->getQuery()->iterate(null, Query::HYDRATE_SCALAR);
    }

    public function getTransactionsForCommissionPeriod(CommissionPeriod $period, Member $member, int $limit = 20, int $offset = 0): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder
            ->select('t', 'm', 'd')
            ->from(Transaction::class, 't')
            ->innerJoin(DWL::class, 'd', Join::WITH, $queryBuilder->expr()->andX()->addMultiple([
                $queryBuilder->expr()->eq('d.id', 't.dwlId'),
                $queryBuilder->expr()->gte('d.date', ':fromDate'),
                $queryBuilder->expr()->lte('d.date', ':toDate')
            ]))
            ->innerJoin('t.customer', 'm', Join::WITH, $queryBuilder->expr()->andX()->addMultiple([
                $queryBuilder->expr()->isNotNull('m.affiliate'),
                $queryBuilder->expr()->eq('t.customer', 'm.id'),
                $queryBuilder->expr()->eq('m.affiliate', ':memberId'),
            ]))
            ->where($queryBuilder->expr()->andX()->addMultiple([
                $queryBuilder->expr()->isNotNull('t.dwlId'),
                $queryBuilder->expr()->eq('t.type', ':type'),
            ]))
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->setParameters([
                'type' => Transaction::TRANSACTION_TYPE_DWL,
                'fromDate' => $period->getDWLDateFrom(),
                'toDate' => $period->getDWLDateTo(),
                'memberId' => $member->getId(),
            ]);
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    public function getSuccessfulPayoutOrComputationCommissionPeriods(int $offset, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('csp');

        $queryBuilder->select('csp.id AS commissionPeriodId', 'csp.dwlDateFrom', 'csp.dwlDateTo')
            ->where(
                $queryBuilder->expr()->orX()->addMultiple([
                    $queryBuilder->expr()->eq('csp.status', ':successPayout'),
                    $queryBuilder->expr()->eq('csp.status', ':successComputation')
                ])
            )
            ->setParameter('successPayout', CommissionPeriod::STATUS_SUCCESSFULL_PAYOUT)
            ->setParameter('successComputation', CommissionPeriod::STATUS_SUCCESSFULL_COMPUTATION)
            ->orderBy('csp.dwlDateTo', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function remove($entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush($entity);
    }

    public function save($entity, bool $reconnect = false): void
    {
        if ($reconnect) {
            $this->reconnectToDatabase();
        }

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush($entity);
    }

    public function getCommissionPeriodsForDateRange(\DateTimeInterface $fromDate, DateTimeInterface $toDate): array
    {
        return $this->createQueryBuilder('csp')
            ->where('csp.dwlDateFrom >= :fromDate')
            ->andWhere('csp.dwlDateTo <= :toDate')
            ->orderBy('csp.dwlDateFrom', 'ASC')
            ->setParameters([
                'fromDate' => $fromDate->format('Y-m-d'),
                'toDate' => $toDate->format('Y-m-d'),
            ])
            ->getQuery()
            ->getResult();
    }
}
