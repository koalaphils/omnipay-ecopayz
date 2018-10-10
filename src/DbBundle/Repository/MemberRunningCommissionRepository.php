<?php

namespace DbBundle\Repository;

use DbBundle\Entity\CommissionPeriod;
use DbBundle\Entity\CommissionSchedule;
use DbBundle\Entity\MemberRunningCommission;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;

class MemberRunningCommissionRepository extends BaseRepository
{
    public function getMemberRunningCommissions(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $queryBuilder = $this->createQueryBuilder('mrc');
        $queryBuilder
            ->select('mrc, cs, mp')
            ->innerJoin('mrc.memberProduct', 'mp')
            ->innerJoin('mrc.commissionPeriod', 'cs')
            ->where('cs.status = :successPayout OR cs.status = :successComputation')
            ->setParameter('successPayout', CommissionPeriod::STATUS_SUCCESSFULL_PAYOUT)
            ->setParameter('successComputation', CommissionPeriod::STATUS_SUCCESSFULL_COMPUTATION)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
        ;

        if (array_has($filters, 'memberId')) {
            $queryBuilder
                ->andWhere('mp.customer = :memberId')
                ->setParameter('memberId', $filters['memberId']);
        }

        if (array_has($filters, 'commissionIds') && !empty($filters['commissionIds'])) {
            $queryBuilder
                ->andWhere('cs.id IN (:commissionIds)')
                ->setParameter('commissionIds', $filters['commissionIds']);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getMemberRunningCommissionsFromCommissionPeriod(
        int $commissionPeriodId,
        int $limit = 20,
        int $offset = 0
    ): array {
        $queryBuilder = $this->createQueryBuilder('mrc');
        $queryBuilder
            ->select('mrc, mp, m, cs')
            ->innerJoin('mrc.commissionPeriod', 'cs')
            ->innerJoin('mrc.memberProduct', 'mp')
            ->innerJoin('mp.customer', 'm')
            ->where('cs.id = :commissionScheduleId')
            ->orderBy('mrc.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->setParameter('commissionScheduleId', $commissionPeriodId);

        return $queryBuilder->getQuery()->getResult();
    }

    public function getMemberRunningCommissionOnOrBeforeCommissionSchedule(
        CommissionSchedule $commissionSchedule,
        int $limit = 20,
        int $offset = 0
    ): array {
        $queryBuilder = $this->createQueryBuilder('mrc');
        $queryBuilder
            ->select('mrc, mp, m, cs')
            ->innerJoin('mrc.commissionPeriod', 'cs')
            ->innerJoin('mrc.memberProduct', 'mp')
            ->innerJoin('mp.customer', 'm')
            ->leftJoin(
                MemberRunningCommission::class,
                'mrch',
                'WITH',
                'mrc.memberProduct = mrch.memberProduct AND mrc.commissionSchedule < mrch.commissionPeriod'
            )
            ->where('cs.dwlDateTo <= :dwlDateTo')
            ->andwhere('mrch.id IS NULL AND mrc.status = :status')
            ->orderBy('mrc.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->setParameter('dwlDateTo', $commissionSchedule->getDWLDateTo()->format('Y-m-d'))
            ->setParameter('status', MemberRunningCommission::CONDITION_UNMET);
        
        return $queryBuilder->getQuery()->getResult();
    }

    public function getMemberRunningCommissionFromCommissionPeriod(
        int $commissionPeriodId,
        int $memberId
    ): ?MemberRunningCommission {
        $queryBuilder = $this->createQueryBuilder('mrc');
        $queryBuilder
            ->select('mrc, mp')
            ->join('mrc.memberProduct', 'mp')
            ->where('mrc.commissionPeriod = :commissionPeriodId')
            ->andWhere('mp.customer = :memberId')
            ->setParameter('commissionPeriodId', $commissionPeriodId)
            ->setParameter('memberId', $memberId);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getPreceedingMemberRunningCommission(
        MemberRunningCommission $memberRunningCommission
    ): ?MemberRunningCommission {
        $queryBuilder = $this->createQueryBuilder('mrc');
        $queryBuilder->select('mrc, cs, mp')
            ->innerJoin('mrc.memberProduct', 'mp')
            ->innerJoin('mrc.commissionPeriod', 'cs', Join::WITH, 'cs.dwlDateTo < :date')
            ->where('mp.id = :memberProductId')
            ->orderBy('cs.dwlDateTo', 'DESC')
            ->setMaxResults(1)
            ->setParameter('memberProductId', $memberRunningCommission->getMemberProduct()->getId())
            ->setParameter(
                'date',
                $memberRunningCommission->getCommissionPeriod()->getDWLDateFrom()->format('Y-m-d')
            );

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getSucceedingMemberRunningCommission(
        MemberRunningCommission $memberRunningCommission
    ): ?MemberRunningCommission {
        $queryBuilder = $this->createQueryBuilder('mrc');
        $queryBuilder->select('mrc, cs, mp')
            ->innerJoin('mrc.memberProduct', 'mp')
            ->innerJoin('mrc.commissionPeriod', 'cs', Join::WITH, 'cs.dwlDateTo > :date')
            ->where('mp.id = :memberProductId')
            ->orderBy('cs.dwlDateTo', 'ASC')
            ->setMaxResults(1)
            ->setParameter('memberProductId', $memberRunningCommission->getMemberProduct()->getId())
            ->setParameter(
                'date',
                $memberRunningCommission->getCommissionSchedule()->getDWLDateFrom()->format('Y-m-d')
            );

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getPreviousSuccessfulMemberRunningCommissions(int $memberId, int $offset, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('mrc');

        $queryBuilder->select('cs.id AS commissionPeriodId', 'cs.dwlDateFrom', 'cs.dwlDateTo', 'mrc.runningCommission')
            ->join('mrc.memberProduct', 'mb')
            ->join('mrc.commissionPeriod', 'cs', Join::WITH, $queryBuilder->expr()->orX()->addMultiple([
                $queryBuilder->expr()->eq('cs.status', ':successPayout'),
                $queryBuilder->expr()->eq('cs.status', ':successComputation')
            ]))
            ->join('mb.customer', 'm', Join::WITH, $queryBuilder->expr()->eq('m.id', ':memberId'))
            ->setParameter('memberId', $memberId)
            ->setParameter('successPayout', CommissionPeriod::STATUS_SUCCESSFULL_PAYOUT)
            ->setParameter('successComputation', CommissionPeriod::STATUS_SUCCESSFULL_COMPUTATION)
            ->orderBy('cs.dwlDateTo', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function totalRunningCommissionOfMember(int $memberId): string
    {
        $queryBuilder = $this->createQueryBuilder('mrc')
            ->select('mrc.runningCommission as totalRunningCommission')
            ->join('mrc.commissionPeriod', 'cs')
            ->join('mrc.memberProduct', 'mb')
            ->where('mb.customer = :memberId AND (cs.status = :successPayout OR cs.status = :successComputation)')
            ->orderBy('cs.dwlDateTo', 'DESC')
            ->setMaxResults(1)
            ->setParameters([
                'memberId' => $memberId,
                'successPayout' => CommissionPeriod::STATUS_SUCCESSFULL_PAYOUT,
                'successComputation' => CommissionPeriod::STATUS_SUCCESSFULL_COMPUTATION
            ]);

        $totalRunningCommission = $queryBuilder->getQuery()->getOneOrNullResult(Query::HYDRATE_SINGLE_SCALAR);
        if (is_null($totalRunningCommission)) {
            return '0';
        } else {
            return (string) $totalRunningCommission;
        }
    }
    
    public function removeMemberRunningCommissionForPeriod(CommissionPeriod $period): void
    {
        if (!$period->isSuccessfullPayout() || !$period->isFailedComputation() || !$period->isExecutingPayout()) {
            $memberIdsResult = $this
                ->createQueryBuilder('mrc')
                ->select('mrc.id')
                ->where('mrc.commissionPeriod = :periodId')
                ->setParameter('periodId', $period->getId())
                ->getQuery()->getScalarResult()
            ;
            $memberIds = array_map(function ($data) {
                return $data['id'];
            } , $memberIdsResult);
            
            $updateQuery = $this->createQueryBuilder('mrc');
            $updateQuery
                ->update()
                ->set('mrc.succeedingRunningCommission', 'NULL')
                ->where($updateQuery->expr()->in('mrc.succeedingRunningCommission', ':memberIds'))
            ;
            $updateQuery->getQuery()->execute(['memberIds' => $memberIds]);
            
            $queryBuilder = $this->createQueryBuilder('mrc');
            $queryBuilder
                ->delete()
                ->where($queryBuilder->expr()->eq('mrc.commissionPeriod', ':periodId'))
                ->setParameter('periodId', $period->getId())
            ;
            $queryBuilder->getQuery()->execute();
        }
    }
    
    public function totalMemberRunningCommissionForCommissionPeriod(CommissionPeriod $period): int
    {
        $queryBuilder = $this->createQueryBuilder('mrc');
        $queryBuilder
            ->select('COUNT(mrc.id) AS totoalRunninCommission')
            ->where($queryBuilder->expr()->eq('mrc.commissionPeriod', ':periodId'))
            ->setParameter('periodId', $period->getId())
        ;
        
        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
    
    public function save($entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush($entity);
    }
}
