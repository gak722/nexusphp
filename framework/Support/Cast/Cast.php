<?php
declare(strict_types=1);

namespace Nexus\Support\Cast;

use Nexus\Support\Parser\BooleanParser;
use Nexus\Support\Parser\DurationParser;
use Nexus\Support\Parser\FloatParser;
use Nexus\Support\Parser\IntegerParser;
use Nexus\Support\Parser\JsonParser;
use Nexus\Support\Parser\SizeParser;

class Cast
{
    public static function toBool(mixed $value, bool $default = false): bool
    {
        return (new BooleanParser())->parse($value)->valueOr($default);
    }

    public static function toInt(mixed $value, int $default = 0): int
    {
        return (new IntegerParser())->parse($value)->valueOr($default);
    }

    public static function toFloat(mixed $value, float $default = 0.0): float
    {
        return (new FloatParser())->parse($value)->valueOr($default);
    }

    public static function toString(mixed $value): string
    {
        if (is_string($value)) return $value;
        if (is_numeric($value) || is_bool($value)) return (string) $value;
        if (is_array($value) || is_object($value)) return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        return '';
    }

    public static function toSize(mixed $value, int $default = 0): int
    {
        return (new SizeParser())->parse($value)->valueOr($default);
    }

    public static function toDuration(mixed $value, int $default = 0): int
    {
        return (new DurationParser())->parse($value)->valueOr($default);
    }

    public static function toJson(mixed $value, int $flags = 0): string
    {
        return json_encode($value, $flags | JSON_THROW_ON_ERROR);
    }

    public static function fromJson(string $value, mixed $default = null): mixed
    {
        return (new JsonParser())->parse($value)->valueOr($default);
    }

    /**
     * @template T of \BackedEnum|\UnitEnum
     * @param class-string<T> $enumClass
     * @param mixed $value
     * @return T|null
     */
    public static function toEnum(string $enumClass, mixed $value): mixed
    {
        if ($value instanceof $enumClass) {
            return $value;
        }

        if (is_subclass_of($enumClass, \BackedEnum::class)) {
            return $enumClass::tryFrom($value) ?? $enumClass::tryFrom((int)$value);
        }

        return null;
    }
}
