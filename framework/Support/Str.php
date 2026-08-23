<?php
declare(strict_types=1);

namespace Nexus\Support;

use Nexus\Support\Parser\BooleanParser;
use Nexus\Support\Parser\DurationParser;
use Nexus\Support\Parser\FloatParser;
use Nexus\Support\Parser\IntegerParser;
use Nexus\Support\Parser\SizeParser;

/**
 * Modern UTF-8 and Parsing Aware String Helper Class
 */
class Str
{
    public static function upper(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    public static function lower(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }

    public static function ucfirst(string $value): string
    {
        return mb_strtoupper(mb_substr($value, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($value, 1, null, 'UTF-8');
    }

    public static function lcfirst(string $value): string
    {
        return mb_strtolower(mb_substr($value, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($value, 1, null, 'UTF-8');
    }

    public static function studly(string $value): string
    {
        $words = explode(' ', str_replace(['-', '_'], ' ', $value));
        $studlyWords = array_map(fn($word) => static::ucfirst($word), $words);
        return implode('', $studlyWords);
    }

    public static function camel(string $value): string
    {
        return static::lcfirst(static::studly($value));
    }

    public static function snake(string $value, string $delimiter = '_'): string
    {
        if (!ctype_lower($value)) {
            $value = preg_replace('/(?<!^)[A-Z]/', $delimiter . '$0', $value);
        }
        return static::lower($value);
    }

    public static function kebab(string $value): string
    {
        return static::snake($value, '-');
    }

    public static function slug(string $title, string $separator = '-'): string
    {
        $title = static::lower($title);
        $title = preg_replace('/[^a-z0-9\s-]/u', '', $title);
        $title = preg_replace('/[\s-]+/', $separator, $title);
        return trim((string)$title, $separator);
    }

    public static function squish(string $value): string
    {
        return preg_replace('~\s+~u', ' ', trim($value));
    }

    public static function contains(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    public static function startsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    public static function endsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_ends_with($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    public static function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    public static function isIp(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    public static function isUuid(string $value): bool
    {
        return \Nexus\Support\Uuid\Uuid::isValid($value);
    }

    public static function isUlid(string $value): bool
    {
        return \Nexus\Support\Ulid\Ulid::isValid($value);
    }

    public static function isJson(string $value): bool
    {
        if (trim($value) === '') return false;
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public static function before(string $subject, string $search): string
    {
        if ($search === '') return $subject;
        $pos = strpos($subject, $search);
        return $pos === false ? $subject : substr($subject, 0, $pos);
    }

    public static function after(string $subject, string $search): string
    {
        if ($search === '') return $subject;
        $pos = strpos($subject, $search);
        return $pos === false ? $subject : substr($subject, $pos + strlen($search));
    }

    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }
        return rtrim(mb_substr($value, 0, $limit, 'UTF-8')) . $end;
    }

    public static function random(int $length = 16): string
    {
        $bytes = random_bytes((int) ceil($length / 2));
        return substr(bin2hex($bytes), 0, $length);
    }

    public static function uuid(): string
    {
        return \Nexus\Support\Uuid\Uuid::v4()->toString();
    }

    public static function ulid(): string
    {
        return \Nexus\Support\Ulid\Ulid::generate()->toString();
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

    // Parsing Delegate Methods
    public static function parseBoolean(mixed $value): \Nexus\Support\Result\ParseResult
    {
        return (new BooleanParser())->parse($value);
    }

    public static function parseInteger(mixed $value): \Nexus\Support\Result\ParseResult
    {
        return (new IntegerParser())->parse($value);
    }

    public static function parseFloat(mixed $value): \Nexus\Support\Result\ParseResult
    {
        return (new FloatParser())->parse($value);
    }

    public static function parseSize(mixed $value): \Nexus\Support\Result\ParseResult
    {
        return (new SizeParser())->parse($value);
    }

    public static function parseDuration(mixed $value): \Nexus\Support\Result\ParseResult
    {
        return (new DurationParser())->parse($value);
    }
}
