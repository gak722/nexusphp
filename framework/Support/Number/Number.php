<?php
declare(strict_types=1);

namespace Nexus\Support\Number;

class Number
{
    public static function clamp(int|float $value, int|float $min, int|float $max): int|float
    {
        return max($min, min($max, $value));
    }

    public static function format(int|float $value, int $decimals = 2, ?string $locale = null): string
    {
        if (class_exists(\NumberFormatter::class) && $locale !== null) {
            $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
            $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $decimals);
            return $formatter->format($value);
        }

        return number_format((float)$value, $decimals, '.', ',');
    }

    public static function percentage(int|float $value, int $decimals = 0, ?string $locale = null): string
    {
        $percentVal = $value * 100;
        return static::format($percentVal, $decimals, $locale) . '%';
    }

    public static function currency(int|float $value, string $currency = 'USD', ?string $locale = 'en_US'): string
    {
        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter($locale ?? 'en_US', \NumberFormatter::CURRENCY);
            return $formatter->formatCurrency((float)$value, $currency);
        }

        $symbol = match ($currency) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹',
            default => $currency . ' ',
        };

        return $symbol . number_format((float)$value, 2, '.', ',');
    }

    public static function size(int|float $bytes, int $decimals = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max((float)$bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $decimals) . ' ' . $units[$pow];
    }

    public static function isEven(int $value): bool
    {
        return $value % 2 === 0;
    }

    public static function isOdd(int $value): bool
    {
        return $value % 2 !== 0;
    }

    public static function between(int|float $value, int|float $min, int|float $max): bool
    {
        return $value >= $min && $value <= $max;
    }
}
