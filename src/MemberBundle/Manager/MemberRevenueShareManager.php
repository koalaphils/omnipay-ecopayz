<?php

namespace MemberBundle\Manager;

use Symfony\Component\HttpFoundation\Request;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\MemberRevenueShare;
use DbBundle\Entity\Product;
use DbBundle\Repository\MemberRevenueShareRepository;

class MemberRevenueShareManager
{
    private $memberRevenueShareRepository;
    private $entityManager;

    public function getRevenueShareSetting(Member $member, Product $product) : MemberRevenueShare
    {
        $memberRevenueShareSettingsRepo = $this->getMemberRevenueShareRepository();
        $memberRevenueShareSetting = $memberRevenueShareSettingsRepo->findByMemberIdAndProductIdGroup($member->getId(), $product->getId());
        $revenueShareSettings = [];
        if (empty($memberRevenueShareSetting)) {
            $revenueShareSettings = new MemberRevenueShare();
            $revenueShareSettings->setMember($member);
            $revenueShareSettings->setProduct($product);
        } else {
            $revenueShareSettings = $memberRevenueShareSetting;
        }

        return $revenueShareSettings;
    }

    public function getRevenueShareSettingByMember(Member $member) : array
    {
        $memberRevenueShareSettingsRepo = $this->getMemberRevenueShareRepository();
        $memberRevenueShareSettings = $memberRevenueShareSettingsRepo->findByMemberId($member->getId());
        
        return $memberRevenueShareSettings;
    }

    public function setMemberManager(MemberManager $memberManager): void
    {
        $this->memberManager = $memberManager;
    }

    private function getMemberManager(): MemberManager
    {
        return $this->memberManager;
    }

    public function setMemberRevenueShareRepository(MemberRevenueShareRepository $memberRevenueShareRepository): void
    {
        $this->memberRevenueShareRepository = $memberRevenueShareRepository;
    }

    private function getMemberRevenueShareRepository(): MemberRevenueShareRepository
    {
        return $this->memberRevenueShareRepository;
    }

    public function getRepository(): MemberRevenueShareRepository
    {
        return $this->getDoctrine()->getRepository(MemberRevenueShare::class);
    }
}