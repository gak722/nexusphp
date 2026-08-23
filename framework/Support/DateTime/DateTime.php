<?php
declare(strict_types=1);

namespace Nexus\Support\DateTime;

use DateTimeImmutable;
use DateTimeZone;

class DateTime extends DateTimeImmutable
{
    public static function parse(string $datetime, ?string $timezone = null): static
    {
        $tz = $timezone ? new DateTimeZone($timezone) : new DateTimeZone(date_default_timezone_get());
        return new static($datetime, $tz);
    }

    public static function now(?string $timezone = null): static
    {
        return static::parse('now', $timezone);
    }

    public static function today(?string $timezone = null): static
    {
        return static::parse('today', $timezone);
    }

    public static function tomorrow(?string $timezone = null): static
    {
        return static::parse('tomorrow', $timezone);
    }

    public static function yesterday(?string $timezone = null): static
    {
        return static::parse('yesterday', $timezone);
    }

    public static function fromFormat(string $format, string $datetime, ?string $timezone = null): static
    {
        $tz = $timezone ? new DateTimeZone($timezone) : new DateTimeZone(date_default_timezone_get());
        $dt = DateTimeImmutable::createFromFormat($format, $datetime, $tz);
        if (!$dt) {
            throw new \InvalidArgumentException("Invalid datetime format '{$format}' for string '{$datetime}'");
        }
        return static::parse($dt->format('Y-m-d H:i:s.u'), $tz->getName());
    }

    public function inTimezone(string $timezone): static
    {
        return $this->setTimezone(new DateTimeZone($timezone));
    }

    public function startOfDay(): static
    {
        return $this->setTime(0, 0, 0, 0);
    }

    public function endOfDay(): static
    {
        return $this->setTime(23, 59, 59, 999999);
    }

    public function startOfMonth(): static
    {
        return $this->modify('first day of this month')->startOfDay();
    }

    public function endOfMonth(): static
    {
        return $this->modify('last day of this month')->endOfDay();
    }

    public function startOfYear(): static
    {
        return $this->modify('first day of January')->startOfDay();
    }

    public function endOfYear(): static
    {
        return $this->modify('last day of December')->endOfDay();
    }

    public function addDays(int $days): static
    {
        return $this->modify("{$days} days");
    }

    public function subDays(int $days): static
    {
        return $this->modify("-{$days} days");
    }

    public function isToday(): bool
    {
        return $this->format('Y-m-d') === static::today($this->getTimezone()->getName())->format('Y-m-d');
    }

    public function isPast(): bool
    {
        return $this < static::now($this->getTimezone()->getName());
    }

    public function isFuture(): bool
    {
        return $this > static::now($this->getTimezone()->getName());
    }

    public function diffForHumans(?self $other = null): string
    {
        $other ??= static::now($this->getTimezone()->getName());
        $diff = $this->diff($other);

        if ($diff->y > 0) return $diff->y === 1 ? '1 year ago' : "{$diff->y} years ago";
        if ($diff->m > 0) return $diff->m === 1 ? '1 month ago' : "{$diff->m} months ago";
        if ($diff->d >= 7) {
            $weeks = (int) floor($diff->d / 7);
            return $weeks === 1 ? '1 week ago' : "{$weeks} weeks ago";
        }
        if ($diff->d > 0) return $diff->d === 1 ? 'yesterday' : "{$diff->d} days ago";
        if ($diff->h > 0) return $diff->h === 1 ? '1 hour ago' : "{$diff->h} hours ago";
        if ($diff->i > 0) return $diff->i === 1 ? '1 minute ago' : "{$diff->i} minutes ago";

        return 'just now';
    }
}
