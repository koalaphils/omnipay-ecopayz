<?php

namespace PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use DbBundle\Entity\Customer;
use PaymentBundle\Component\Model\BitcoinAdjustment;

class BitcoinRateSettingSaveEvent extends Event
{
    public const NAME = 'btc.rate_settings_save';
    
    private $bitcoinAdjustment;

    public function __construct(BitcoinAdjustment $bitcoinAdjustment)
    {
        $this->bitcoinAdjustment = $bitcoinAdjustment;
    }

    public function getBitcoinAdjustment(): BitcoinAdjustment
    {
        return $this->bitcoinAdjustment;
    }
}
