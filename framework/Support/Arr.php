<?php
declare(strict_types=1);

namespace Nexus\Support;

/**
 * Array Helper Utility Class
 */
class Arr
{
    public static function get(mixed $array, mixed $key, mixed $default = null): mixed
    {
        if (!is_array($array) && !($array instanceof \ArrayAccess)) {
            return $default;
        }

        if ($key === null) {
            return $array;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (!str_contains((string)$key, '.')) {
            return $array[$key] ?? $default;
        }

        foreach (explode('.', (string)$key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    public static function flatten(array $array): array
    {
        $result = [];
        array_walk_recursive($array, function ($a) use (&$result) {
            $result[] = $a;
        });
        return $result;
    }
}
