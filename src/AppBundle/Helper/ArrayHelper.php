<?php

namespace AppBundle\Helper;

class ArrayHelper
{
    public static function append($array, $value, $key = null)
    {
        if (is_null($key)) {
            array_push($array, $value);
        } else {
            $array = $array + [$key => $value];
        }

        return $array;
    }

    public static function get($array, $key, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default instanceof Closure ? $default() : $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    public static function has($array, $key)
    {
        if (empty($array) || is_null($key)) {
            return false;
        }

        if (array_key_exists($key, $array)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }

            $array = $array[$segment];
        }

        return true;
    }

    public static function set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param array  $array
     * @param string $prepend
     *
     * @return array
     */
    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param array  $array
     * @param string $delimeter
     * @param string $prepend
     * @param bool   $notLast
     *
     * @return type
     */
    public static function dot($array, $delimeter = '.', $prepend = '', $notLast = false)
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $results = array_merge($results, static::dot($value, $delimeter, $prepend . $key . $delimeter, $notLast));
            } else {
                if ($notLast) {
                    $results[rtrim($prepend, $delimeter)][$key] = $value;
                } else {
                    $results[$prepend . $key] = $value;
                }
            }
        }

        return $results;
    }

    public static function forget(&$array, $keys)
    {
        $original = &$array;

        $keys = (array) $keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            $parts = explode('.', $key);

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    $parts = [];
                }
            }

            unset($array[array_shift($parts)]);

            // clean up after each pass
            $array = &$original;
        }
    }

    public static function prepend($array, $value, $key = null)
    {
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }

        return $array;
    }
}
