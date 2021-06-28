<?php

namespace PaymentBundle\Model\Bitcoin;

use Doctrine\Common\Collections\ArrayCollection;

use DbBundle\Entity\BitcoinRateSetting;
use DbBundle\Entity\Interfaces\PreservesOriginalInterface;
use DbBundle\Entity\Traits\PreservesOriginalTrait;

class BitcoinRateSettingsDTO implements PreservesOriginalInterface
{
    use PreservesOriginalTrait;

    private $defaultRateSetting;
    private $bitcoinRateSettings;

    public function __construct(BitcoinRateSetting $defaultRateSetting)
    {
        $this->defaultRateSetting = $defaultRateSetting;
    }

    public function setDefaultRateSetting(BitcoinRateSetting $defaultRateSetting): BitcoinRateSettingsDTO
    {
        $this->defaultRateSetting = $defaultRateSetting;

        return $this;
    }

    public function getDefaultRateSetting(): BitcoinRateSetting
    {
        return $this->defaultRateSetting;
    }

    public function addBitcoinRateSetting(BitcoinRateSetting $bitcoinRateSetting)
    {
        if (!$this->bitcoinRateSettings->contains($bitcoinRateSetting)) {
            $this->bitcoinRateSettings->add($bitcoinRateSetting);
        }
    }

    public function setBitcoinRateSettings(?array $bitcoinRateSettings): BitcoinRateSettingsDTO
    {
        $this->bitcoinRateSettings = $bitcoinRateSettings;

        return $this;
    }

    public function getBitcoinRateSettings(): ?array
    {
        return $this->bitcoinRateSettings;
    }
}
