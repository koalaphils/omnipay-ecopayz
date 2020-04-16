<?php

namespace DbBundle\Repository;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use DbBundle\Entity\Winloss;

class WinlossRepository extends BaseRepository
{
    public function getWinlossByDateMember($memberId, $dateFrom, $dateTo, $product): ?array
    {
        $queryBuilder = $this->createQueryBuilder('w')
            ->select("w.product, SUM(COALESCE(w.payout, 0)) totalPayout, SUM(COALESCE(w.turnover, 0)) totalTurnover")
            ->where('w.product = :product AND w.status = :status AND (w.date between :dateFrom and :dateTo) AND w.member = :member')
            ->groupBy('w.product')
            ->setParameters([
                'product' => $product,
                'status' => 1,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'member' => $memberId
            ]);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}