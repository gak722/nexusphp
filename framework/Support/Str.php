<?php
declare(strict_types=1);

namespace Nexus\Support;

/**
 * String Helper Utility Class
 */
class Str
{
    public static function studly(string $value): string
    {
        $words = explode(' ', str_replace(['-', '_'], ' ', $value));
        $studlyWords = array_map(fn($word) => ucfirst($word), $words);
        return implode('', $studlyWords);
    }

    public static function camel(string $value): string
    {
        return lcfirst(static::studly($value));
    }

    public static function snake(string $value): string
    {
        if (!ctype_lower($value)) {
            $value = preg_replace('/(?<!^)[A-Z]/', '_$0', $value);
        }
        return strtolower($value);
    }

    public static function ucfirst(string $value): string
    {
        return mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1);
    }

    public static function classBasename(string|object $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }

    public static function plural(string $value): string
    {
        if (str_ends_with($value, 's')) {
            return $value;
        }
        if (str_ends_with($value, 'y') && !in_array(substr($value, -2, 1), ['a', 'e', 'i', 'o', 'u'], true)) {
            return substr($value, 0, -1) . 'ies';
        }
        return $value . 's';
    }

    public static function random(int $length = 16): string
    {
        $bytes = random_bytes((int) ceil($length / 2));
        return substr(bin2hex($bytes), 0, $length);
    }
}
