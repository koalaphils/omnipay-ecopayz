<?php

namespace DbBundle\Repository;

use DbBundle\Entity\BitcoinRateSetting;

class BitcoinRateSettingRepository extends BaseRepository
{
    public function findNonDefaultRateSettings(int $type): array
    {
        return $this->createQueryBuilder('rate')
            ->select('rate')
            ->where('rate.isDefault = false')
            ->andWhere('rate.type = ' . $type)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findDefaultSetting(int $type): ?BitcoinRateSetting
    {
        return $this->createQueryBuilder('rate')
            ->select('rate')
            ->where('rate.isDefault = true')
            ->andWhere('rate.type = ' . $type)
            ->orderBy('rate.updatedAt', 'DESC')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }

    public function findAllRateSetting(int $type): array
    {
        return $this->createQueryBuilder('rate')
            ->select('rate')
            ->andWhere('rate.type = ' . $type)
            ->getQuery()
            ->getResult()
        ;
    }
}
