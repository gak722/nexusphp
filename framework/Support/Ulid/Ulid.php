<?php
declare(strict_types=1);

namespace Nexus\Support\Ulid;

class Ulid
{
    protected static string $crockford = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public function __construct(protected string $value)
    {
        if (!static::isValid($value)) {
            throw new \InvalidArgumentException("Invalid ULID string: '{$value}'");
        }
    }

    public static function generate(?int $timeMs = null): static
    {
        $timeMs ??= (int) (microtime(true) * 1000);
        $timeChars = '';
        for ($i = 9; $i >= 0; $i--) {
            $mod = $timeMs % 32;
            $timeChars = static::$crockford[$mod] . $timeChars;
            $timeMs = (int) ($timeMs / 32);
        }

        $randChars = '';
        $bytes = random_bytes(10);
        for ($i = 0; $i < 10; $i++) {
            $randChars .= static::$crockford[ord($bytes[$i]) % 32];
            $randChars .= static::$crockford[(ord($bytes[$i]) >> 3) % 32];
        }

        return new static(substr($timeChars . $randChars, 0, 26));
    }

    public static function isValid(string $ulid): bool
    {
        return preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/i', $ulid) === 1;
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
