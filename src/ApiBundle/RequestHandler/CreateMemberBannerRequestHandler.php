<?php

namespace ApiBundle\RequestHandler;

use ApiBundle\Request\CreateMemberBannerRequest;
use DbBundle\Entity\BannerImage;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\MemberBanner;
use DbBundle\Entity\MemberReferralName;
use DbBundle\Entity\MemberWebsite;
use Doctrine\Bundle\DoctrineBundle\Registry;

class CreateMemberBannerRequestHandler
{
    private $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function handle(CreateMemberBannerRequest $createMemberBannerRequest, Member $member): MemberBanner
    {
        $memberBanner = MemberBanner::create($member);
        $memberBanner->setCampaignName($createMemberBannerRequest->getCampaignName());
        $memberBanner->setBannerImage(
            $this->getBannerImageRepository()->getByTypeLanguageSize(
                $createMemberBannerRequest->getBannerImageType(),
                $createMemberBannerRequest->getLanguage(),
                $createMemberBannerRequest->getSize()
            )
        );

        if (!empty($website = $createMemberBannerRequest->getWebsite())) {
            $memberBanner->setMemberWebsite($this->createMemberWebsite($website, $member));
        }

        $memberBanner->setMemberReferralName(
            $this->createMemberReferralName($createMemberBannerRequest->getTrackingCode(), $member)
        );

        $this->save($memberBanner);

        return $memberBanner;
    }

    private function save(MemberBanner $memberBanner): void
    {
        $this->getEntityManager()->persist($memberBanner);
        $this->getEntityManager()->flush();
    }

    private function createMemberWebsite(string $website, Member $member): MemberWebsite
    {
        $memberWebsite = $this->getMemberWebsiteRepository()->findOneByWebsite($website);

        if (is_null($memberWebsite)) {
            $memberWebsite = MemberWebsite::create($member);
            $memberWebsite->setWebsite($website);

            $this->getEntityManager()->persist($memberWebsite);
        }

        return $memberWebsite;
    }

    private function createMemberReferralName(string $referralName, Member $member): MemberReferralName
    {
        $memberReferralName = $this->getMemberReferralNameRepository()->findOneByName($referralName);

        if (is_null($memberReferralName)) {
            $memberReferralName = MemberReferralName::create($member);
            $memberReferralName->setName($referralName);

            $this->getEntityManager()->persist($memberReferralName);
        }

        return $memberReferralName;
    }

    private function getBannerImageRepository(): \DbBundle\Repository\BannerImageRepository
    {
        return $this->doctrine->getRepository(BannerImage::class);
    }

    private function getMemberWebsiteRepository(): \DbBundle\Repository\MemberWebsiteRepository
    {
        return $this->doctrine->getRepository(MemberWebsite::class);
    }

    private function getMemberReferralNameRepository(): \DbBundle\Repository\MemberReferralNameRepository
    {
        return $this->doctrine->getRepository(MemberReferralName::class);
    }

    private function getEntityManager(): \Doctrine\ORM\EntityManager
    {
        return $this->doctrine->getManager();
    }
}