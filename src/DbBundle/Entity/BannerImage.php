<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditAssociationInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;

class BannerImage extends Entity implements ActionInterface, TimestampInterface, AuditAssociationInterface, AuditInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    const TYPE_PROMOTION = 1;
    const TYPE_ADVERTISEMENT = 2;

    private $type;
    private $language;
    private $dimension;
    private $isActive;
    private $details;

    public function __construct()
    {
        $this->isActive = true;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): BannerImage
    {
        $this->type = $type;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): BannerImage
    {
        $this->language = $language;

        return $this;
    }

    public function getDimension(): string
    {
        return $this->dimension;
    }

    public function setDimension(string $dimension): BannerImage
    {
        $this->dimension = $dimension;

        return $this;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function setDetails(array $details): BannerImage
    {
        $this->details = $details;

        return $this;
    }

    public function isPromotion(): bool
    {
        return $this->getType() == self::TYPE_PROMOTION;
    }

    public function isAdvertisement(): bool
    {
        return $this->getType() == self::TYPE_ADVERTISEMENT;
    }

    public function getTypeText(): string
    {
        return self::getTypeString($this->getType());
    }

    public static function getTypeString(int $type): string
    {
        $value = '';

        if ($type == self::TYPE_PROMOTION) {
            $value = 'promotion';
        } elseif ($type == self::TYPE_ADVERTISEMENT) {
            $value = 'advertisement';
        }

        return $value;
    }

    public function getTypeTitleText(): string
    {
        return ucfirst($this->getTypeText());
    }

    public function getLanguageText(): string
    {
        $label = '';

        switch ($this->getLanguage()) {
            case 'EN':
                $label = 'English';
                break;
            case 'FR':
                $label = 'French';
                break;
            case 'DE':
                $label = 'German';
                break;
            case 'ES':
                $label = 'Spanish';
                break;
            case 'AO':
                $label = 'AsianOdds';
        }

        return $label;
    }

    public function getFilename(): string
    {
        return $this->getDetail('filename');
    }

    public function getAssociationFieldName(): string
    {
        return $this->getFilename();
    }

    public function getCategory(): int
    {
        return AuditRevisionLog::CATEGORY_BANNER_IMAGE;
    }

    public function getIgnoreFields(): array
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt'];
    }

    public function getAssociationFields(): array
    {
        return [];
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
        return ['type' => $this->getTypeText(), 'language' => $this->getLanguage(), 'dimension' => $this->getDimension()];
    }

    public function isAudit(): bool
    {
        return true;
    }

    private function getDetail(string $property)
    {
        return array_get($this->getDetails(), $property);
    }
}