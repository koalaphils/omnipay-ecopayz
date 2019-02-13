<?php

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('array_get')) {
    function array_get($array, $key, $default = null)
    {
        return \AppBundle\Helper\ArrayHelper::get($array, $key, $default);
    }
}

if (!function_exists('array_has')) {
    function array_has($array, $key)
    {
        return \AppBundle\Helper\ArrayHelper::has($array, $key);
    }
}

if (!function_exists('array_set')) {
    function array_set(&$array, $key, $value)
    {
        return \AppBundle\Helper\ArrayHelper::set($array, $key, $value);
    }
}

if (!function_exists('array_dot')) {
    function array_dot($array, $delimeter = '.', $prepend = '', $notLast = false)
    {
        return \AppBundle\Helper\ArrayHelper::dot($array, $delimeter, $prepend, $notLast);
    }
}

if (!function_exists('array_forget')) {
    function array_forget(&$array, $keys)
    {
        return \AppBundle\Helper\ArrayHelper::forget($array, $keys);
    }
}

if (!function_exists('array_append')) {
    function array_append($array, $value, $key = null)
    {
        return \AppBundle\Helper\ArrayHelper::append($array, $value, $key);
    }
}

if (!function_exists('array_prepend')) {
    function array_prepend($array, $value, $key = null)
    {
        return \AppBundle\Helper\ArrayHelper::prepend($array, $value, $key);
    }
}

if (!function_exists('file_partial')) {
    function file_partial($fileName, $fileTitle = null, $contentType = 'application/octet-stream')
    {
        return \AppBundle\Helper\FileHelper::partial($fileName, $fileTitle, $contentType);
    }
}
if (!function_exists('generate_code')) {
    function generate_code($length = 9, $addDashes = false, $availableSets = 'luds')
    {
        $sets = [];
        if (strpos($availableSets, 'l') !== false) {
            //will include as random small letters
            $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        }
        if (strpos($availableSets, 'u') !== false) {
            //will include as random capital letters #We exclude letter I due to ISO
            $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        }
        if (strpos($availableSets, 'd') !== false) {
            //will include as random digit #We exclude number one due to ISO
            $sets[] = '23456789';
        }
        if (strpos($availableSets, 's') !== false) {
            //will include as random special characters. #We exclude dot due to ISO
            $sets[] = '!@#$%&*?';
        }
        $all = '';
        $password = '';
        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }
        $all = str_split($all);
        for ($i = 0; $i < $length - count($sets); ++$i) {
            $password .= $all[array_rand($all)];
        }
        $password = str_shuffle($password);
        if (!$addDashes) {
            return $password;
        }
        $dashLen = floor(sqrt($length));
        $dashStr = '';
        while (strlen($password) > $dashLen) {
            $dashStr .= substr($password, 0, $dashLen) . '-';
            $password = substr($password, $dashLen);
        }
        $dashStr .= $password;

        return $dashStr;
    }
}

if (!function_exists('currency_exchangerate')) {
    function currency_exchangerate($amount, $fromRate, $toRate)
    {
        $eq = 'x(z/r)';
        $vars = [
            'x' => $amount,
            'r' => $fromRate,
            'z' => $toRate,
        ];
        $value = AppBundle\ValueObject\Number::parseEquation($eq, $vars);

        return $value->toString();
    }
}

if (!function_exists('date_dbvalue')) {
    function date_dbvalue($value, $fromFormat, $toFormat)
    {
        $date = $value;
        if (is_string($date)) {
            $date = \DateTime::createFromFormat($fromFormat, $value);
        } elseif (is_numeric($date)) {
            $date = new \DateTime($date);
        }

        if (!($date instanceof DateTime)) {
            throw new \Exception(sprintf('Can\'t convert %s to DateTime', gettype($date)));
        }

        if ($toFormat instanceof \Doctrine\DBAL\Platforms\AbstractPlatform) {
            $toFormat = $toFormat->getDateFormatString();
        } elseif ($toFormat instanceof \Doctrine\ORM\EntityManagerInterface) {
            $toFormat = $toFormat->getConnection()->getDatabasePlatform()->getDateFormatString();
        }

        return $date->format($toFormat);
    }
}

if (!function_exists('datetime_dbvalue')) {
    function datetime_dbvalue($value, $fromFormat, $toFormat)
    {
        $date = $value;
        if (is_string($date)) {
            $date = \DateTime::createFromFormat($fromFormat, $value);
        } elseif (is_numeric($date)) {
            $date = new \DateTime($date);
        }

        if (!($date instanceof DateTime)) {
            throw new \Exception(sprintf('Can\'t convert %s to DateTime', gettype($date)));
        }

        if ($toFormat instanceof \Doctrine\DBAL\Platforms\AbstractPlatform) {
            $toFormat = $toFormat->getDateTimeFormatString();
        } elseif ($toFormat instanceof \Doctrine\ORM\EntityManagerInterface) {
            $toFormat = $toFormat->getConnection()->getDatabasePlatform()->getDateTimeFormatString();
        }

        return $date->format($toFormat);
    }
}

if (!function_exists('process')) {
    function process($command, $logFile = null)
    {
        if (is_array($command)) {
            $command = implode(' ', $command);
        }

        if ($logFile !== null) {
            $logFile = trim($logFile, " \t\n\r\0\x0B\/");
            $command .= ' >> ' . $logFile . ' 2>&1';
        }

        $process = new \Symfony\Component\Process\Process($command . ' &');
        $process->run();

        return $process->getPid();
    }
}

if (!function_exists('is_cli')) {
    function is_cli()
    {
        if (defined('STDIN')
            || php_sapi_name() === 'cli'
            || array_key_exists('SHELL', $_ENV)
            || (
                empty($_SERVER['REMOTE_ADDR'])
                && !isset($_SERVER['HTTP_USER_AGENT'])
                && count($_SERVER['argv']) > 0
            )
            || !array_key_exists('REQUEST_METHOD', $_SERVER)
        ) {
            return true;
        }

        return false;
    }
}

if (!function_exists('camel_case')) {
    function camel_case($value)
    {
        return AppBundle\Helper\StrHelper::camel($value);
    }
}

if (!function_exists('studly_case')) {
    function studly_case($value)
    {
        return AppBundle\Helper\StrHelper::studly($value);
    }
}

if (!function_exists('remove_trailing_decimals')) {
    function remove_trailing_decimals(string $decimal): string
    {
        eval('$evaluatedCommission = ' . $decimal .';');

        return (string) $evaluatedCommission;
    }
}

if (!function_exists('number_are_equal')) {
    function number_are_equal($number, $numberToCompare): bool
    {
        return remove_trailing_decimals((string) $number) === remove_trailing_decimals((string) $numberToCompare);
    }
}
