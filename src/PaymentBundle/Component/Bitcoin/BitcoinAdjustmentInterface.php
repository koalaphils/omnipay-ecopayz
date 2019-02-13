<?php

namespace PaymentBundle\Component\Bitcoin;

use PaymentBundle\Component\Model\BitcoinAdjustment;

interface BitcoinAdjustmentInterface
{
    /**
     * Return the latest adjustment for bitcoin rate
     * Format: 
     * [
     *  [
     *      "range_start" => <float>,
     *      "range_end" => <float>,
     *      "fixed_adjustment" => <signed decimal>,
     *      "percent_adjustment"=> <signed decimal>,
     *      "is_default" => boolean
     *  ],
     *  ...
     * ]
     * 
     * @return array
     */
    public function getAdjustment(): BitcoinAdjustment;
}
