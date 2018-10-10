<?php

namespace ApiBundle\Request;

use DbBundle\Entity\BannerImage;

class CreateMemberBannerRequest
{
    const TYPE_PROMOTION = 'promotion';
    const TYPE_ADVERTISEMENT = 'advertisement';

    private $website;
    private $type;
    private $language;
    private $size;
    private $campaignName;
    private $trackingCode;

    public function __construct()
    {
        $this->website = '';
        $this->type = '';
        $this->language = '';
        $this->size = '';
        $this->campaignName = '';
        $this->trackingCode = '';
    }

    public static function create(): CreateMemberBannerRequest
    {
        return new self();
    }

    public function getWebsite(): string
    {
        return $this->website;
    }

    public function setWebsite(string $website): CreateMemberBannerRequest
    {
        $this->website = $website;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): CreateMemberBannerRequest
    {
        $this->type = $type;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): CreateMemberBannerRequest
    {
        $this->language = $language;

        return $this;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function setSize(string $size): CreateMemberBannerRequest
    {
        $this->size = $size;

        return $this;
    }

    public function getCampaignName(): string
    {
        return $this->campaignName;
    }

    public function setCampaignName(string $campaignName): CreateMemberBannerRequest
    {
        $this->campaignName = $campaignName;

        return $this;
    }

    public function getTrackingCode(): string
    {
        return $this->trackingCode;
    }

    public function setTrackingCode(string $trackingCode): CreateMemberBannerRequest
    {
        $this->trackingCode = $trackingCode;

        return $this;
    }

    public static function getTypes(): array
    {
        $types = [
            self::TYPE_PROMOTION,
            self::TYPE_ADVERTISEMENT
        ];

        return array_combine($types, $types);
    }

    public static function getLanguages(): array
    {
        $languages = [
            'EN', 'FR', 'DE', 'ES', 'AO'
        ];

        return array_combine($languages, $languages);
    }

    public function getBannerImageType(): int
    {
        return $this->isPromotion() ? BannerImage::TYPE_PROMOTION : BannerImage::TYPE_ADVERTISEMENT;
    }

    public function isPromotion(): bool
    {
        return $this->getType() == self::TYPE_PROMOTION;
    }

    public function isAdvertisement(): bool
    {
        return $this->getType() == self::TYPE_ADVERTISEMENT;
    }
}