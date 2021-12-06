<?php

namespace AppBundle\ValueObject;

use Decimal\Decimal;

class Number
{
    public const ROUND_UP = Decimal::ROUND_UP;
    public const ROUND_DOWN = Decimal::ROUND_DOWN;
    public const ROUND_HALF_UP = Decimal::ROUND_HALF_UP;

    protected const CONFIG = [
        'precision' => 20,
        'round' => true,
        'round_type' => Number::ROUND_HALF_UP,
    ];

    protected $value;
    protected $original;
    protected $currentConfig;

    public static function format($num = '0', $config = []): string
    {
        return self::parse($num, 'en', $config);
    }

    public static function formatToMinimumDecimal($value = '0', int $numOfDecimal): string
    {
        return self::parse($value, 'en', ['precision' => $numOfDecimal]);
    }

    public static function isNumber($number = '0'): bool
    {
        try{
            self::parse($number);
            return true;
        }catch (\Exception $ex){
            return false;
        }
    }

    public static function add(string $num1, string $num2): Number
    {
        $num1 = new Number($num1);

        return $num1->plus($num2);
    }

    public static function div(string $num1, string $num2): Number
    {
        $num1 = new Number($num1);
        return $num1->dividedBy($num2);
    }

    public static function mul(string $num1, string $num2): Number
    {
        $num1 = new Number($num1);

        return $num1->times($num2);
    }

    public static function pow(string $num1, string $exponent): Number
    {
        $num1 = new Number($num1);

        return $num1->toPower($exponent);
    }

    public static function sub(string $num1, string $num2): Number
    {
        $num1 = new Number($num1);

        return $num1->minus($num2);
    }

    public function __construct($num = '0', $config = array())
    {
        $config = array_replace_recursive(static::CONFIG, $config);
        $this->currentConfig = $config;

        $this->value = self::parse($num, 'en', $config);
    }

    public static function parse($num = '0', $locale = 'en', array $config = self::CONFIG): string
    {
        static $formatters = array();
        $config = array_replace_recursive(self::CONFIG, $config);

        if(empty($formatters)){
            $formatters = array(
                'en' => \NumberFormatter::create('en', \NumberFormatter::DECIMAL),
                'fr' => \NumberFormatter::create('fr', \NumberFormatter::DECIMAL),
                'de' => \NumberFormatter::create('de', \NumberFormatter::DECIMAL),
                'es' => \NumberFormatter::create('es', \NumberFormatter::DECIMAL),
            );
        }

        $value = (string) $formatters[$locale]->parse($num);

        if(intl_is_failure($formatters[$locale]->getErrorCode())){
            foreach(array_filter($formatters, function($item, $key) use($locale){
                return strcasecmp($key, $locale) !== 0;
            }, ARRAY_FILTER_USE_BOTH) as $formatter){
                $value = (string) $formatter->parse($num);
                if(!intl_is_failure($formatter->getErrorCode())){
                    break;
                }
            }
        }

        if(empty(trim($value)) || strtolower(trim($value)) === 'null' || boolval(trim($value)) == false){
            return Decimal::valueOf('0')->toFixed($config['precision'], false, $config['round_type']);
        }

        if(is_numeric($value)){
            return (Decimal::valueOf($value))->toFixed($config['precision'], false, $config['round_type']);
        }
        throw new \Exception('Number is expecting 1 parameters to be a number. ' . $num . ' provided.');
    }

    public function dividedBy(string $num): Number
    {
        return new Number((Decimal::valueOf($this->value))->div($num)->toFixed($this->currentConfig['precision'], false), $this->currentConfig);
    }

    public function minus(string $num): Number
    {
        return new Number((Decimal::valueOf($this->value))->sub($num)->toFixed($this->currentConfig['precision'], false), $this->currentConfig);
    }

    public function modulo(string $num): Number
    {
        return new Number((Decimal::valueOf($this->value))->mod($num)->toFixed($this->currentConfig['precision'], false), $this->currentConfig);
    }

    public function plus(string $num): Number
    {
        return new Number((Decimal::valueOf($this->value))->add($num)->toFixed($this->currentConfig['precision'], false), $this->currentConfig);
    }

    public function times(string $num): Number
    {
        return new Number((Decimal::valueOf($this->value))->mul($num)->toFixed($this->currentConfig['precision'], false), $this->currentConfig);
    }

    public function toPower(string $num): Number
    {
        return new Number((Decimal::valueOf($this->value))->pow($num)->toFixed($this->currentConfig['precision'], false), $this->currentConfig);
    }

    public function equals(string $num): bool
    {
        return (Decimal::valueOf($this->value, $this->currentConfig['precision']))->compareTo(Decimal::valueOf($num, $this->currentConfig['precision'])) === 0;
    }

    public function greaterThan(string $num): bool
    {
        return (Decimal::valueOf($this->value, $this->currentConfig['precision']))->compareTo(Decimal::valueOf($num, $this->currentConfig['precision'])) > 0;
    }

    public function greaterThanOrEqual(string $num): bool
    {
        return (Decimal::valueOf($this->value, $this->currentConfig['precision']))->compareTo(Decimal::valueOf($num, $this->currentConfig['precision'])) >= 0;
    }

    public function lessThan(string $num): bool
    {
        return (Decimal::valueOf($this->value, $this->currentConfig['precision']))->compareTo(Decimal::valueOf($num, $this->currentConfig['precision'])) < 0;
    }

    public function lessThanOrEqual(string $num): bool
    {
        return (Decimal::valueOf($this->value, $this->currentConfig['precision']))->compareTo(Decimal::valueOf($num, $this->currentConfig['precision'])) <= 0;
    }

    public function notEqual(string $num): bool
    {
        return (Decimal::valueOf($this->value, $this->currentConfig['precision']))->compareTo(Decimal::valueOf($num, $this->currentConfig['precision'])) !== 0;
    }

    public function toFloat()
    {
        return floatval($this->value);
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
        return (Decimal::valueOf($this->value))->toFixed($numOfDecimal, false);
    }

    public static function round($value = '0', $precision = 0, $roundType = 0): string
    {
        if ($roundType === 0) {
            $roundType = self::$config['round_type'];
        }

        return self::parse($value, 'en', ['precision' => $precision, 'round_type' => $roundType]);
    }

    public function toFixed($precision = 0, $roundType = 0): string
    {
        if ($roundType === 0) {
            $roundType = $this->currentConfig['round_type'];
        }

        return self::round($this->value, $precision, $roundType);
    }
}
