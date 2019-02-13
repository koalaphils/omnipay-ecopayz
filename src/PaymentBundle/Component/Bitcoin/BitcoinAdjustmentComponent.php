<?php

namespace PaymentBundle\Component\Bitcoin;

use Doctrine\ORM\EntityManagerInterface;
use DbBundle\Entity\BitcoinRateSetting;
use PaymentBundle\Component\Model\BitcoinAdjustment;

class BitcoinAdjustmentComponent implements BitcoinAdjustmentInterface
{
    private $em;

    public function __construct(EntityManagerInterface $em) 
    {
        $this->em = $em;
    }

    public function getAdjustment(): BitcoinAdjustment
    {
        $bitcoinRateSettings = $this->em->getRepository(BitcoinRateSetting::class)->findAll();
        $bitcoinAdjustment = new BitcoinAdjustment($bitcoinRateSettings);

        return $bitcoinAdjustment;
    }
}