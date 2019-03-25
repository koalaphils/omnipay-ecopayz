<?php

namespace AppBundle\Helper;

use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

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

    public static function snakeCase(string $value): string
    {
        $converter = new CamelCaseToSnakeCaseNameConverter();

        return $converter->normalize($value);
    }
}
