<?php

namespace PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use DbBundle\Entity\Customer;
use PaymentBundle\Component\Model\BitcoinAdjustment;

class BitcoinRateSettingSaveEvent extends Event
{
    public const NAME = 'btc.rate_settings_save';
    
    private $bitcoinAdjustment;
    private $transactionType;

    public function __construct(BitcoinAdjustment $bitcoinAdjustment, int $transactionType)
    {
        $this->bitcoinAdjustment = $bitcoinAdjustment;
        $this->transactionType = $transactionType;
    }

    public function getBitcoinAdjustment(): BitcoinAdjustment
    {
        return $this->bitcoinAdjustment;
    }

    public function getTransactionType(): int
    {
        return $this->transactionType;
    }
}
