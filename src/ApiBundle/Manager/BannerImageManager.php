<?php

namespace ApiBundle\Manager;

use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\BannerImage;

class BannerImageManager extends AbstractManager
{
    public function list(): array
    {
        return $this->getRepository()->findAll();
    }

    public function view(string $filename): string
    {
        return $this->getMediaManager()->renderFile($filename);
    }

    public function getRepository(): \DbBundle\Repository\BannerImageRepository
    {
        return $this->getDoctrine()->getRepository(BannerImage::class);
    }

    private function getMediaManager(): \MediaBundle\Manager\MediaManager
    {
        return $this->get('media.manager');
    }
}