<?php

namespace AppBundle\ValueObject;

class Number
{
    public const ROUND_UP = 1;
    public const ROUND_DOWN = 2;
    public const ROUND_HALF_UP = 3;

    protected const CONFIG = [
        'precision' => 20,
        'round' => true,
        'round_type' => Number::ROUND_DOWN,
    ];

    protected $value;
    protected $original;
    protected $currentConfig;

    public static function format($num, $config = []): string
    {
        if (!preg_match('/^([+-]?(\d+(\.\d*)?)|(\.\d+))$/', $num)) {
            throw new \Exception('Number is expecting 1 parameters to be a number.');
        }

        $config = array_replace_recursive(self::CONFIG, $config);

        $broken_number = explode('.', $num . '');
        if (count($broken_number) != 2) {
            $broken_number[1] = str_pad('', $config['precision'], '0', STR_PAD_RIGHT);
        } else {
            $broken_number[1] = str_pad($broken_number[1], $config['precision'], '0', STR_PAD_RIGHT);
        }

        $value = implode('.', $broken_number);

        if ($config['round']) {
            $value = self::round($value, $config['precision'], $config['round_type']);
        }

        return $value;
    }

    public static function formatToMinimumDecimal($value, int $numOfDecimal): string
    {
        $result = explode('.', $value);

        if (!isset($result[1])) {
            $result[1] = '';
        }

        return $result[0] . '.' . str_pad(rtrim($result[1], '0'), $numOfDecimal, '0', STR_PAD_RIGHT);
    }

    public static function isNumber(string $number): bool
    {
        return preg_match('/^([+-]?(\d+(\.\d*)?)|(\.\d+))$/', $number);
    }

    public static function add($num1, $num2): Number
    {
        $num1 = new Number($num1);

        return $num1->plus($num2);
    }

    public static function div($num1, $num2): Number
    {
        $num1 = new Number($num1);
        return $num1->dividedBy($num2);
    }

    public static function mul($num1, $num2): Number
    {
        $num1 = new Number($num1);

        return $num1->times($num2);
    }

    public static function pow($num1, $exponent): Number
    {
        $num1 = new Number($num1);

        return $num1->toPower($exponent);
    }

    public static function sub($num1, $num2): Number
    {
        $num1 = new Number($num1);

        return $num1->minus($num2);
    }

    public function __construct($num, $config = array())
    {
        $config = array_replace_recursive(static::CONFIG, $config);
        $this->currentConfig = $config;
        $num = $this->convertScientificToDecimal((string) $num);

        if (preg_match('/^([+-]?(\d+(\.\d*)?)|(\.\d+))$/', $num)) {
            $this->value = static::format($num, $this->currentConfig);
        } else {
            throw new \Exception('Number is expecting 1 parameters to be a number.');
        }
    }

    public function parse($num): string
    {
        return self::format($num, $this->currentConfig);
    }

    public function convertScientificToDecimal(string $num): string
    {
        $parts = explode('E', $num);

        if(count($parts) === 2){
            $exp = abs(end($parts)) + strlen($parts[0]);
            $decimal = number_format($num, $exp);

            return rtrim($decimal, '.0');
        }
        else{
            return $num;
        }
    }

    public function dividedBy($num): Number
    {
        $num = $this->parse($num);
        $result = $this->value / $num;

        return new Number($result, $this->currentConfig);
    }

    public function minus($num): Number
    {
        $num = $this->parse($num);
        $result = $this->value - $num;

        return new Number($result, $this->currentConfig);
    }

    public function modulo($num): Number
    {
        $num = $this->parse($num);
        $result = $this->value / $num;

        $real = intval($result);

        $prod = $this->parse($real) * $num;

        return $this->minus($prod);
    }

    public function plus($num): Number
    {
        $num = $this->parse($num);
        $result = $this->value + $num;

        return new Number($result, $this->currentConfig);
    }

    public function times($num): Number
    {
        $num = $this->parse($num);
        $result = $this->value * $num;

        return new Number($result, $this->currentConfig);
    }

    public function toPower($num): Number
    {
        return new Number(pow($this->value, $num), $this->currentConfig);
    }

    public function equals($num): bool
    {
        $num = $this->parse($num);

        return $this->value == $num;
    }

    public function greaterThan($num): bool
    {
        $num = $this->parse($num);

        return $this->value > $num;
    }

    public function greaterThanOrEqual($num): bool
    {
        $num = $this->parse($num);

        return $this->value >= $num;
    }

    public function lessThan($num): bool
    {
        $num = $this->parse($num);

        return $this->value < $num;
    }

    public function lessThanOrEqual($num): bool
    {
        $num = $this->parse($num);

        return $this->value < $num;
    }

    public function notEqual($num): bool
    {
        $num = $this->parse($num);

        return $this->value != $num;
    }

    public function toFloat()
    {
        eval('$var = ' . $this->value . ';');

        return $var;
    }

    final public static function parseEquation(string $string, array $vars = [], bool $compute = true, array $config = []): Number
    {
        $index = 0;
        $array_parse = array();
        $type = null;
        for ($i = 0; $i < strlen($string); $i++) {
            $char = $string[$i];
            if ($char == '(') {

                if ($type == 'num' || $type == 'arr') {
                    $index++;
                    $array_parse[$index] = '*';
                    $index++;
                }

                $type = 'arr';
                $parse = "";
                $open = 0;
                for ($ii = $i + 1; $ii < strlen($string); $ii++) {
                    if ($string[$ii] == "(") {
                        $open++;
                    }
                    if ($string[$ii] == ")") {
                        $open--;
                    }

                    if ($open == -1) {
                        break;
                    } else {
                        $parse .= $string[$ii];
                    }
                }
                $i = $ii;
                if (array_key_exists($index, $array_parse)) {
                    $index++;
                }
                if ($compute) {
                    $array_parse[$index] = self::parseEquation($parse, $vars, $compute, $config);
                } else {
                    $array_parse[$index] = '(' . self::parseEquation($parse, $vars, $compute, $config) . ')';
                }
            } elseif (self::isOperator($char) && $type != 'opr' && !is_null($type)) {
                if (array_key_exists($index, $array_parse)) {
                    $index++;
                }
                $type = 'opr';
                $array_parse[$index] = $char;
            } else {
                if (array_key_exists($index, $array_parse) && $type != 'num') {
                    if ($type == 'arr') {
                        $index++;
                        $array_parse[$index] = '*';
                    }
                    $index++;
                    $array_parse[$index] = '';
                } elseif (!array_key_exists($index, $array_parse)) {
                    $array_parse[$index] = '';
                }
                $type = 'num';
                $array_parse[$index] .= $char;
            }
        }
        self::subtitute($array_parse, $vars);

        if ($compute) {
            return new Number(self::compute($array_parse), $config);
        }

        return implode('', $array_parse);
    }

    private static function subtitute(&$array, $vars = array())
    {
        foreach ($array as &$arr) {
            if (!self::isOperator($arr)) {
                if (is_string($arr) && array_key_exists($arr, $vars)) {
                    $arr = $vars[$arr];
                }
            }
        }
    }

    private static function compute(array $array, array $config = [])
    {
        $new_array = $array;
        foreach (self::getOperators() as $opr) {
            for ($index = 0; $index < count($new_array); $index++) {
                $val = $new_array[$index];
                if (self::isOperator($val) && $val === $opr) {
                    $num1 = $new_array[$index - 1];
                    $num2 = $new_array[$index + 1];
                    $num = new Number($num1, $config);
                    switch ($val) {
                        case '+':
                            $num = $num->plus($num2);
                            break;
                        case '-':
                            $num = $num->minus($num2);
                            break;
                        case '*':
                            $num = $num->times($num2);
                            break;
                        case '/':
                            $num = $num->dividedBy($num2);
                            break;
                        case '%':
                            $num = $num->modulo($num2);
                            break;
                        case '^':
                            $num = $num->toPower($num2);
                            break;
                        default: $num = new Number($num2, $config);
                    }
                    array_splice($new_array, $index - 1, 3, $num . '');
                    $index -= 1;
                }
            }
        }
        return $new_array[0];
    }

    private static function getOperators(): array
    {
        return array('^', '%', '*', '/', '+', '-');
    }

    private static function isOperator($operator): bool
    {
        if ($operator instanceof Number) {
            return false;
        }
        $has = false;
        foreach (self::getOperators() as $op) {
            if ($operator === $op) {
                $has = true;
                break;
            }
        }

        return $has;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return (string) $this->value;
    }

    public function toStringWithMinimumDecimal(int $numOfDecimal): string
    {
        return self::formatToMinimumDecimal($this->value, $numOfDecimal);
    }

    public static function round($value, int $precision = 0, int $roundType = 0): string
    {
        if ($roundType === 0) {
            $roundType = self::$config['round_type'];
        }

        $broken_number = explode('.', $value);

        if (count($broken_number) != 2) {
            $broken_number[1] = str_pad('', $precision, '0', STR_PAD_RIGHT);
        } else {
            $broken_number[1] = str_pad($broken_number[1], $precision + 1, '0', STR_PAD_RIGHT);
        }

        if ($precision > 0) {
            $pre = substr($broken_number[1], $precision, 1);
            $broken_number[1] = substr($broken_number[1], 0, $precision);
            if (($roundType === self::ROUND_HALF_UP && $pre >= 5) || $roundType === self::ROUND_UP) {
                $broken_number[1] += 1;
            }

            $broken_number[1] = str_pad($broken_number[1], $precision, '0', STR_PAD_LEFT);

            return implode('.', $broken_number);
        } elseif ($precision === 0) {
            $pre = substr($broken_number[1], 0, 1);
            if (($roundType === self::ROUND_HALF_UP && $pre >= 5) || $roundType === self::ROUND_UP) {
                $broken_number[0] += 1;
            }

            return $broken_number[0];
        } else {
            $pre = substr($broken_number[0], $precision, 1);
            $real = substr($broken_number[0], 0, $precision);
            if (($roundType === self::ROUND_HALF_UP && $pre >= 5) || $roundType === self::ROUND_UP) {
                $real += 1;
            }

            return str_pad($real, strlen($broken_number[0]), '0', STR_PAD_RIGHT);
        }
    }

    public function toFixed(int $precision = 0, int $roundType = 0): string
    {
        if ($roundType === 0) {
            $roundType = $this->currentConfig['round_type'];
        }

        return self::round($this->value, $precision, $roundType);
    }
}