<?php

namespace PaymentBundle\Component\Bitcoin;

use Doctrine\ORM\EntityManagerInterface;
use DbBundle\Entity\BitcoinRateSetting;
use PaymentBundle\Component\Model\BitcoinAdjustment;
use DbBundle\Entity\Transaction;

class BitcoinAdjustmentComponent implements BitcoinAdjustmentInterface
{
    private $em;

    public function __construct(EntityManagerInterface $em) 
    {
        $this->em = $em;
    }

    public function getAdjustment(int $type = Transaction::TRANSACTION_TYPE_DEPOSIT): BitcoinAdjustment
    {
        $bitcoinRateSettings = $this->em->getRepository(BitcoinRateSetting::class)->findAllRateSetting($type);
        $bitcoinAdjustment = new BitcoinAdjustment($bitcoinRateSettings);

        return $bitcoinAdjustment;
    }
}