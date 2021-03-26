<?php

namespace MemberBundle\Manager;

use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\MemberWebsite;
use DbBundle\Repository\MemberWebsiteRepository;

class MemberWebsiteManager extends AbstractManager
{
    public function suspendWebsite(int $memberWebsiteId): void
    {
        $memberWebsite = $this->getRepository()->find($memberWebsiteId);
        $memberWebsite->suspend();
        $this->getEntityManager()->persist($memberWebsite);
        $this->getEntityManager()->flush($memberWebsite);
    }

    public function activateWebsite(int $memberWebsiteId): void
    {
        $memberWebsite = $this->getRepository()->find($memberWebsiteId);
        $memberWebsite->activate();
        $this->getEntityManager()->persist($memberWebsite);
        $this->getEntityManager()->flush($memberWebsite);
    }

    public function canAddMoreWebsiteForMember(int $memberId): bool
    {
        $totalActive = $this->getRepository()->getActiveCount($memberId);
        $limit = $this->getSettingManager()->getSetting('member.website.max');

        return $totalActive < $limit;
    }

    protected function getRepository(): MemberWebsiteRepository
    {
        return $this->getDoctrine()->getRepository(MemberWebsite::class);
    }

    private function getSettingManager(): \AppBundle\Manager\SettingManager
    {
        return $this->container->get('app.setting_manager');
    }
}
