<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\Customer as Member;

class MemberBanner extends Entity implements ActionInterface, TimestampInterface, AuditInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    private $referralLink;
    private $trackingHtmlCode;
    private $campaignName;
    private $memberWebsite;
    private $memberReferralName;
    private $bannerImage;
    private $member;

    public function __construct()
    {
        $this->referralLink = '';
        $this->trackingHtmlCode = '';
    }

    public static function create(Member $member): MemberBanner
    {
        $memberBanner = new self();

        $memberBanner->setMember($member);

        return $memberBanner;
    }

    public function setCampaignName(string $campaignName): MemberBanner
    {
        $this->campaignName = $campaignName;

        return $this;
    }

    public function getCampaignName(): string
    {
        return $this->campaignName;
    }

    public function setMemberWebsite(MemberWebsite $memberWebsite): MemberBanner
    {
        $this->memberWebsite = $memberWebsite;

        return $this;
    }

    public function getMemberWebsite(): MemberWebsite
    {
        return $this->memberWebsite;
    }

    public function setMemberReferralName(MemberReferralName $memberReferralName): MemberBanner
    {
        $this->memberReferralName = $memberReferralName;

        return $this;
    }

    public function getMemberReferralName(): MemberReferralName
    {
        return $this->memberReferralName;
    }

    public function setBannerImage(BannerImage $bannerImage): MemberBanner
    {
        $this->bannerImage = $bannerImage;

        return $this;
    }

    public function getBannerImage(): BannerImage
    {
        return $this->bannerImage;
    }

    public function setMember(Member $member): MemberBanner
    {
        $this->member = $member;

        return $this;
    }

    public function getMember(): Member
    {
        return $this->member;
    }

    public function setReferralLink(string $referralLink): MemberBanner
    {
        $this->referralLink = $referralLink;

        return $this;
    }

    public function getReferralLink(): string
    {
        return $this->referralLink;
    }

    public function setTrackingHtmlCode(string $trackingHtmlCode): MemberBanner
    {
        $this->trackingHtmlCode = $trackingHtmlCode;

        return $this;
    }

    public function getTrackingHtmlCode(): string
    {
        return $this->trackingHtmlCode;
    }

    public function getFilename(): string
    {
        return $this->getBannerImage()->getFilename();
    }

    public function getCategory(): int
    {
        return AuditRevisionLog::CATEGORY_MEMBER_BANNER;
    }

    public function getIgnoreFields(): array
    {
        return ['trackingHtmlCode', 'createdBy', 'createdAt'];
    }

    public function getAssociationFields(): array
    {
        return ['memberWebsite', 'memberReferralName', 'bannerImage', 'member'];
    }

    public function getIdentifier(): ?int
    {
        return $this->getId();
    }

    public function getLabel(): string
    {
        return sprintf('%s (%s)', $this->getLanguage(), $this->getTypeText());
    }

    public function getAuditDetails(): array
    {
        return ['campaignName' => $this->getCampaignName()];
    }

    public function isAudit(): bool
    {
        return true;
    }

    public function getLanguage(): string
    {
        return $this->getBannerImage()->getLanguage();
    }

    public function getTypeText(): string
    {
        return $this->getBannerImage()->getTypeText();
    }

    public function getTrackingCode(): string
    {
        return $this->getMemberReferralName()->getName();
    }

    public function getSize(): string
    {
        return $this->getBannerImage()->getDimension();
    }

    public function getReferralLinkOptions(): array
    {
        return [
            'language' => $this->getLanguage(),
            'type' => $this->getTypeText(),
            'trackingCode' => $this->getTrackingCode(),
        ];
    }

    public function getTrackingHtmlOptions(): array
    {
        return array_merge($this->getReferralLinkOptions(), [
            'filename' => $this->getFilename(),
            'dimension' => $this->getSize(),
            'referralLink' => $this->getReferralLink(),
        ]);
    }
}

