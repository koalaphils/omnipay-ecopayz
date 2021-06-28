<?php

namespace DbBundle\Entity;

use AppBundle\ValueObject\Money;
use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\VersionableInterface;
use DbBundle\Entity\Traits\ActionTrait;
use DbBundle\Entity\Traits\VersionableTrait;
use DbBundle\Entity\User;
use DbBundle\Utils\VersionableUtils;

class CurrencyRate extends Entity implements ActionInterface, VersionableInterface
{
    use ActionTrait;
    use VersionableTrait;

    const DEFAULT_NUMBER_TO_DISPLAY_HISTORY = 10;

    private $sourceCurrency;
    private $destinationCurrency;
    private $rate;
    private $destinationRate;
    private $creator;

    public function getCreator() : ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getSourceCurrency(): Currency
    {
        return $this->sourceCurrency;
    }

    public function getDestinationCurrency(): Currency
    {
        return $this->destinationCurrency;
    }

    public function getRate(): string
    {
        return $this->rate;
    }

    public function getSourceRate(): string
    {
        return $this->getRate();
    }
    
    public function setSourceRate(string $rate): void
    {
        $this->setRate($rate);
    }
    
    public function getRateAsMoney(): Money
    {
        return new Money($this->sourceCurrency, $this->rate);
    }

    public function setSourceCurrency(Currency $sourceCurrency): void
    {
        $this->sourceCurrency = $sourceCurrency;
    }

    public function setDestinationCurrency(Currency $destinationCurrency): void
    {
        $this->destinationCurrency = $destinationCurrency;
    }

    public function setRate(string $rate): void
    {
        $this->rate = $rate;
    }

    public function getDestinationRate(): string
    {
        return $this->destinationRate;
    }
    
    public function setDestinationRate(string $rate): void
    {
        $this->destinationRate = $rate;
    }
    
    public function generateResourceId(): string
    {
        return $this->sourceCurrency->getId();
    }

    public function preserveOriginal(): void
    {
        VersionableUtils::preserveOriginal($this);
    }

    public function getVersionedProperties(): array
    {
        return [
            'destinationCurrency',
            'rate',
            'creator',
        ];
    }
}
