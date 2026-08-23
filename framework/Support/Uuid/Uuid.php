<?php
declare(strict_types=1);

namespace Nexus\Support\Uuid;

class Uuid
{
    public function __construct(protected string $value)
    {
        if (!static::isValid($value)) {
            throw new \InvalidArgumentException("Invalid UUID string: '{$value}'");
        }
    }

    public static function v4(): static
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40); // version 4
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80); // variant RFC 4122

        $hex = bin2hex($bytes);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));

        return new static($uuid);
    }

    public static function isValid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid) === 1;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
