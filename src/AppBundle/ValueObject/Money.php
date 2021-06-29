<?php

namespace AppBundle\ValueObject;

use AppBundle\ValueObject\Money;
use DbBundle\Entity\Currency;
use InvalidArgumentException;

/**
 * Description of Money
 *
 * @author cydrick
 */
class Money
{
    private $currency;
    private $amount;

    public function __construct(Currency $currency, string $amount)
    {
        $this->currency = $currency;
        $this->amount = $amount;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getCurrencyCode(): string
    {
        return $this->getCurrency()->getCode();
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function convertToCurrency(Currency $destinationCurrency, string $fromRate, string $toRate): Money
    {
        if ($destinationCurrency->getCode() === $this->getCurrency()->getCode()) {
            throw new InvalidArgumentException("You can't convert to same currency");
        }

        $convertedAmount = currency_exchangerate(
            $this->getAmount(),
            $fromRate,
            $toRate
        );

        return new Money($destinationCurrency, $convertedAmount);
    }

    public function sameCurrency(Currency $currency): bool
    {
        return $this->currency->getId() === $currency->getId();
    }

    public function equal(Money $otherMoney): bool
    {
        return $this->sameCurrency($otherMoney->getCurrency()) && $this->getAmount() === $otherMoney->getAmount();
    }
}
