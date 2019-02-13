<?php

namespace DbBundle\Repository;

use DbBundle\Entity\BitcoinRateSetting;

class BitcoinRateSettingRepository extends BaseRepository
{
    public function findNonDefaultRateSettings(): array
    {
        return $this->createQueryBuilder('rate')
            ->select('rate')
            ->where('rate.isDefault = false')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findDefaultSetting(): ?BitcoinRateSetting
    {
        return $this->createQueryBuilder('rate')
            ->select('rate')
            ->where('rate.isDefault = true')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
