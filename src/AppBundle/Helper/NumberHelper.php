<?php

namespace AppBundle\Helper;

class NumberHelper
{
    public static function subtract(string $num1, string $num2): string
    {
        $decimal = static::getMaxDecimal($num1, $num2);
        $difference = static::format($num1, $decimal) - static::format($num2, $decimal);

        return sprintf('%f', $difference);
    }

    public static function add(string $num1, string $num2): string
    {
        $decimal = static::getMaxDecimal($num1, $num2);
        $sum = static::format($num1, $decimal) + static::format($num2, $decimal);

        return sprintf('%f', $sum);
    }

    public static function multiply(string $num1, string $num2): string
    {
        $decimal = static::getMaxDecimal($num1, $num2);
        $product = static::format($num1, $decimal) * static::format($num2, $decimal);

        return sprintf('%f', $product);
    }

    public static function divide(string $num1, string $num2): string
    {
        $decimal = static::getMaxDecimal($num1, $num2);
        $qoutient = static::format($num1, $decimal) / static::format($num2, $decimal);

        return sprintf('%f', $qoutient);
    }

    public static function format(string $number, int $decimal): string
    {
        return number_format($number, $decimal, '.', '');
    }

    public static function getMaxDecimal(string $num1, string $num2): int
    {
        $maxDecimal = 0;
        $explodedNum1 = explode('.', $num1);
        if (count($explodedNum1) === 2) {
            $maxDecimal = strlen($explodedNum1[1]);
        }
        $explodedNum2 = explode('.', $num2);
        if (count($explodedNum2) === 2 && strlen($explodedNum2[1]) > $maxDecimal) {
            $maxDecimal = strlen($explodedNum2[1]);
        }

        return $maxDecimal;
    }
    
    public static function toFloat($value): float
    {
        eval('$var = ' . $value . ';');
        
        return $var;
    }
}
