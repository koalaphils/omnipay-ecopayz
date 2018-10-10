<?php

namespace DbBundle\Repository;

use DbBundle\Entity\BannerImage;

class BannerImageRepository extends BaseRepository
{
    public function findAll(): array
    {
        $queryBuilder = $this->createQueryBuilder('bi');

        $queryBuilder->select('bi')
            ->where($queryBuilder->expr()->eq('bi.isActive', ':isActive'))
            ->setParameter('isActive', true);

        return $queryBuilder->getQuery()->getResult();
    }

    public function getByTypeLanguageSize(int $type, string $language, string $dimension): ?BannerImage
    {
        $queryBuilder = $this->createQueryBuilder('bi');

        $queryBuilder->select('bi')
            ->where($queryBuilder->expr()->andX(
                $queryBuilder->expr()->eq('bi.type', ':type'),
                $queryBuilder->expr()->eq('bi.language', ':language'),
                $queryBuilder->expr()->eq('bi.dimension', ':dimension'),
                $queryBuilder->expr()->eq('bi.isActive', ':isActive')
            ))
            ->setParameters([
                'type' => $type,
                'language' => $language,
                'dimension' => $dimension,
                'isActive' => true,
            ]);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
