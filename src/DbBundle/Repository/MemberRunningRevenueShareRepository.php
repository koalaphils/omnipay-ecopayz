<?php

namespace DbBundle\Repository;

use DbBundle\Entity\CommissionPeriod;
use DbBundle\Entity\MemberRunningRevenueShare;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;

class MemberRunningRevenueShareRepository extends BaseRepository
{
    public function getMemberRunningRevenueShares(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $queryBuilder = $this->createQueryBuilder('mrs');
        $queryBuilder
            ->select('mrs, cs')
            ->innerJoin('mrs.revenueSharePeriod', 'cs')
            ->where('cs.revenueShareStatus = :successPayout OR cs.revenueShareStatus = :successComputation')
            ->setParameter('successPayout', CommissionPeriod::STATUS_SUCCESSFULL_PAYOUT)
            ->setParameter('successComputation', CommissionPeriod::STATUS_SUCCESSFULL_COMPUTATION)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
        ;

        if (array_has($filters, 'memberId')) {
            $queryBuilder
                ->andWhere('mrs.member = :memberId')
                ->setParameter('memberId', $filters['memberId']);
        }

        if (array_has($filters, 'commissionIds') && !empty($filters['commissionIds'])) {
            $queryBuilder
                ->andWhere('cs.id IN (:commissionIds)')
                ->setParameter('commissionIds', $filters['commissionIds']);
        }

        return $queryBuilder->getQuery()->getResult();
    }


    public function totalRunningRevenueShareOfMember(int $memberId): string
    {
        $queryBuilder = $this->createQueryBuilder('mrs')
            ->select('mrs.runningRevenueShare as totalRunningRevenueShare')
            ->join('mrs.revenueSharePeriod', 'cs')
            ->where('mrs.member = :memberId AND (cs.status = :successPayout OR cs.status = :successComputation)')
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
    

    public function save($entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush($entity);
    }
}