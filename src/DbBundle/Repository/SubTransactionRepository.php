<?php

namespace DbBundle\Repository;

use DbBundle\Entity\CommissionPeriod;
use DbBundle\Entity\Transaction;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * SubTransactionRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SubTransactionRepository extends BaseRepository
{
    public function getTransactionIds(array $filters = [], array $memberIds = []): array
    {
        $queryBuilder = $this->createQueryBuilder('subtransaction');
        $queryBuilder
            ->select('GROUP_CONCAT(transaction.id)')
            ->innerJoin('subtransaction.parent', 'transaction')
            ->innerJoin('transaction.customer', 'customer')
            ->innerJoin('subtransaction.customerProduct', 'customerProduct')
        ;

        if (array_has($filters, 'search')) {
            $queryBuilder
                ->orWhere("(JSON_EXTRACT(subtransaction.details, '$.immutableCustomerProductData.username') LIKE :search "
                    . "AND customerProduct.userName LIKE :search) OR "
                    . "JSON_EXTRACT(subtransaction.details, '$.immutableCustomerProductData.username') LIKE :search")
                ->setParameter('search', '%' . $filters['search'] . '%')
            ;

            if (!empty($memberIds)) {
                $queryBuilder
                    ->andWhere('customer.id IN (:memberIds)')
                    ->setParameter('memberIds', $memberIds)
                ;
            }
        }

        return explode(',', $queryBuilder->getQuery()->getSingleScalarResult());
    }

    public function getReferralTurnoverWinLossCommissionByReferrer(array $filters, int $referrerId): array
    {
        $queryBuilder = $this->getReferralTurnoverWinLossCommissionByReferrerQb($filters, $referrerId);

        $queryBuilder->select(
            'mp.id AS memberProductId',
                'p.id AS productId', 'p.name AS productName',
                'm.id AS memberId', 'dwlCurrency.code AS currencyCode',
                "SUM(IFNULL(st.dwlTurnover, 0)) totalTurnover",
                "SUM(IFNULL(st.dwlWinLoss, 0)) AS totalWinLoss",
                "SUM(IFNULL(t.commissionComputedOriginal, 0)) AS totalAffiliateCommission"
            );

        if (array_has($filters, 'memberProductIds')) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('mp.id', ':memberProductIds'))
                ->setParameter('memberProductIds', array_get($filters, 'memberProductIds'));
        }

        $queryBuilder
            ->groupBy('memberProductId')
            ->addGroupBy('p.id')
            ->addGroupBy('m.id')
            ->addGroupBy('dwlCurrency.id')
            ->orderBy('p.id')
            ->addOrderBy('m.id');

        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function getTotalReferralTurnoverWinLossCommissionByReferrer(array $filters, int $referrerId): array
    {
        $queryBuilder = $this->getReferralTurnoverWinLossCommissionByReferrerQb($filters, $referrerId);

        $queryBuilder->select('dwlCurrency.code AS currencyCode',
            "SUM(IFNULL(st.dwlTurnover, 0)) totalTurnover",
            "SUM(IFNULL(st.dwlWinLoss, 0)) AS totalWinLoss",
            "SUM(IFNULL(t.commissionComputedOriginal, 0)) AS totalAffiliateCommission"
        );

        $queryBuilder->groupBy('dwlCurrency.id');

        return $queryBuilder->getQuery()->getArrayResult();
    }

    private function getReferralTurnoverWinLossCommissionByReferrerQb(array $filters, int $referrerId): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('st');

        $queryBuilder
            ->join('st.customerProduct', 'mp')
            ->join('st.parent', 't')
            ->join('t.customer', 'm')
            ->join('m.affiliate', 'a', Join::WITH, $queryBuilder->expr()->eq('a.id', ':referrerId'))
            ->join('DbBundle:DWL', 'dwl', Join::WITH, $queryBuilder->expr()->eq('dwl.id', 'st.dwlId'))
            ->join('dwl.currency', 'dwlCurrency')
            ->join('dwl.product', 'p')
            ->andWhere($queryBuilder->expr()->gte('dwl.date', ':startDate'))
            ->setParameter('startDate', $filters['startDate'])
            ->setParameter('referrerId', $referrerId);

        if (array_has($filters, 'dwlDateFrom')) {
            $queryBuilder->andWhere($queryBuilder->expr()->gte('dwl.date', ':dwlDateFrom'))
                ->setParameter('dwlDateFrom', array_get($filters, 'dwlDateFrom'));
        }

        if (array_has($filters, 'dwlDateTo')) {
            $queryBuilder->andWhere($queryBuilder->expr()->lte('dwl.date', ':dwlDateTo'))
                ->setParameter('dwlDateTo', array_get($filters, 'dwlDateTo'));
        }

        $queryBuilder->andWhere($queryBuilder->expr()->eq('t.type', ':dwl'))
            ->andWhere($queryBuilder->expr()->eq('t.status', ':submitted'))
            ->andWhere($queryBuilder->expr()->eq('t.isVoided', ':isVoided'))
            ->setParameter('dwl', Transaction::TRANSACTION_TYPE_DWL)
            ->setParameter('submitted', Transaction::TRANSACTION_STATUS_END)
            ->setParameter('isVoided', false);

        return $queryBuilder;
    }
}
