<?php

namespace AppBundle\ValueObject;

class Number
{
    protected static $config = array(
        'precision' => 20,
        'round' => true
    );
    protected $value;
    protected $original;

    public static function setConfig($config): void
    {
        static::$config = array_replace_recursive(static::$config, $config);
    }

    public static function format($num, $config = array()): string
    {
        if (!preg_match('/^([+-]?(\d+(\.\d*)?)|(\.\d+))$/', $num)) {
            throw new \Exception('Number is expecting 1 parameters to be a number.');
        }

        $config = array_replace_recursive(static::$config, $config);

        $broken_number = explode('.', $num . '');
        if (count($broken_number) != 2) {
            $broken_number[1] = str_pad('', $config['precision'], '0', STR_PAD_RIGHT);
        } else {
            $broken_number[1] = str_pad($broken_number[1], $config['precision'], '0', STR_PAD_RIGHT);
        }

        if ($config['round']) {
            if ($config['precision'] < strlen($broken_number[1])) {
                $pre = substr($broken_number[1], $config['precision'], 1);
                $broken_number[1] = substr($broken_number[1], 0, $config['precision']);
                if ($pre >= 5) {
                    $broken_number[1] += 1;
                }
            }
        }

        return implode('.', $broken_number);
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
        $config = array_replace_recursive(static::$config, $config);
        $num = $this->convertScientificToDecimal((string) $num);

        if (preg_match('/^([+-]?(\d+(\.\d*)?)|(\.\d+))$/', $num)) {
            $this->value = static::format($num, $config);
        } else {
            throw new \Exception('Number is expecting 1 parameters to be a number.');
        }
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
        $num = $this->format($num);
        $result = $this->value / $num;

        return new Number($result);
    }

    public function minus($num): Number
    {
        $num = $this->format($num);
        $result = $this->value - $num;

        return new Number($result);
    }

    public function modulo($num): Number
    {
        $num = $this->format($num);
        $result = $this->value / $num;

        $real = intval($result);

        $prod = $this->format($real) * $num;

        return $this->sub($prod);
    }

    public function plus($num): Number
    {
        $num = $this->format($num);
        $result = $this->value + $num;

        return new Number($result);
    }

    public function times($num): Number
    {
        $num = $this->format($num);
        $result = $this->value * $num;

        return new Number($result);
    }

    public function toPower($num): Number
    {
        return new Number(pow($this->value, $num));
    }

    public function equals($num): bool
    {
        $num = $this->format($num);

        return $this->value == $num;
    }

    public function greaterThan($num): bool
    {
        $num = $this->format($num);

        return $this->value > $num;
    }

    public function greaterThanOrEqual($num): bool
    {
        $num = $this->format($num);

        return $this->value >= $num;
    }

    public function lessThan($num): bool
    {
        $num = $this->format($num);

        return $this->value < $num;
    }

    public function lessThanOrEqual($num): bool
    {
        $num = $this->format($num);

        return $this->value < $num;
    }

    public function notEqual($num): bool
    {
        $num = $this->format($num);

        return $this->value != $num;
    }

    public function toFloat()
    {
        eval('$var = ' . $this->value . ';');

        return $var;
    }

    public static function parseEquation(string $string, array $vars = [], bool $compute = true): Number
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
                    $array_parse[$index] = self::parseEquation($parse, $vars, $compute);
                } else {
                    $array_parse[$index] = '(' . self::parseEquation($parse, $vars, $compute) . ')';
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
            return (new Number(self::compute($array_parse, $vars)));
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

    private static function compute($array)
    {
        $new_array = $array;
        foreach (self::getOperators() as $opr) {
            for ($index = 0; $index < count($new_array); $index++) {
                $val = $new_array[$index];
                if (self::isOperator($val) && $val === $opr) {
                    $num1 = $new_array[$index - 1];
                    $num2 = $new_array[$index + 1];
                    $num = new Number($num1);
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
                        default: $num = new Number($num2);
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
}
