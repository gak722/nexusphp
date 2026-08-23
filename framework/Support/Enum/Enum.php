<?php
declare(strict_types=1);

namespace Nexus\Support\Enum;

class Enum
{
    /**
     * @template T of \BackedEnum|\UnitEnum
     * @param class-string<T> $enumClass
     * @return array<int, string|int>
     */
    public static function values(string $enumClass): array
    {
        $values = [];
        foreach ($enumClass::cases() as $case) {
            $values[] = $case instanceof \BackedEnum ? $case->value : $case->name;
        }
        return $values;
    }

    /**
     * @template T of \BackedEnum|\UnitEnum
     * @param class-string<T> $enumClass
     * @return array<int, string>
     */
    public static function names(string $enumClass): array
    {
        return array_map(fn($case) => $case->name, $enumClass::cases());
    }

    /**
     * @template T of \BackedEnum|\UnitEnum
     * @param class-string<T> $enumClass
     * @return array<string|int, string>
     */
    public static function options(string $enumClass): array
    {
        $options = [];
        foreach ($enumClass::cases() as $case) {
            $key = $case instanceof \BackedEnum ? $case->value : $case->name;
            $options[$key] = $case->name;
        }
        return $options;
    }
}
