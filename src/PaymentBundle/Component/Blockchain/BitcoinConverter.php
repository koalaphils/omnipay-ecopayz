<?php

namespace PaymentBundle\Component\Blockchain;

use AppBundle\ValueObject\Number;

/**
 * Description of BlockchainConversion
 *
 * @author cydrick
 */
class BitcoinConverter
{
    private const SATOSHI_VALUE = "100000000";

    public static function convertToBtc(string $value): string
    {
        return Number::div($value, self::SATOSHI_VALUE)->toString();
    }

    public static function convertToSatoshi(string $value): string
    {
        $product = Number::mul($value, self::SATOSHI_VALUE)->toString();
        
        return $product;
    }
}
