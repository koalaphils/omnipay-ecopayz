<?php

namespace AppBundle\Helper;

class StrHelper
{
    public static function camel($value)
    {
        return lcfirst(static::studly($value));
    }

    public static function studly($value)
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return str_replace(' ', '', $value);
    }
}
