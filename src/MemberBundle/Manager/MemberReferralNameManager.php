<?php

namespace MemberBundle\Manager;

use AppBundle\Manager\AbstractManager;
use AppBundle\Manager\SettingManager;
use DbBundle\Entity\MemberReferralName;
use DbBundle\Repository\MemberReferralNameRepository;

class MemberReferralNameManager extends AbstractManager
{
    public function suspendReferralName(int $memberReferralNameId): void
    {
        $memberReferralName = $this->getRepository()->find($memberReferralNameId);
        $memberReferralName->suspend();
        $this->getEntityManager()->persist($memberReferralName);
        $this->getEntityManager()->flush($memberReferralName);
    }

    public function activateReferralName(int $memberReferralNameId): void
    {
        $memberReferralName = $this->getRepository()->find($memberReferralNameId);
        $memberReferralName->activate();
        $this->getEntityManager()->persist($memberReferralName);
        $this->getEntityManager()->flush($memberReferralName);
    }

    public function canAddMoreReferralName(int $memberId): bool
    {
        $totalActive = $this->getRepository()->getReferralNameActiveCount($memberId);
        $limit = $this->getSettingManager()->getSetting('member.referralName.max');

        return $totalActive < $limit;
    }

    protected function getRepository(): MemberReferralNameRepository
    {
        return $this->getDoctrine()->getRepository(MemberReferralName::class);
    }

    private function getSettingManager(): SettingManager
    {
        return $this->container->get('app.setting_manager');
    }
}
