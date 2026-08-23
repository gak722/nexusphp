<?php
declare(strict_types=1);

namespace Nexus\Support\Duration;

use Nexus\Support\Parser\DurationParser;

class Duration
{
    public function __construct(public readonly int $seconds) {}

    public static function seconds(int $seconds): static
    {
        return new static($seconds);
    }

    public static function minutes(int $minutes): static
    {
        return new static($minutes * 60);
    }

    public static function hours(int $hours): static
    {
        return new static($hours * 3600);
    }

    public static function days(int $days): static
    {
        return new static($days * 86400);
    }

    public static function parse(string $expression): static
    {
        $sec = (new DurationParser())->parse($expression)->value();
        return new static($sec);
    }

    public function toSeconds(): int
    {
        return $this->seconds;
    }

    public function toMinutes(): float
    {
        return $this->seconds / 60;
    }

    public function toHours(): float
    {
        return $this->seconds / 3600;
    }

    public function human(): string
    {
        if ($this->seconds < 60) return "{$this->seconds}s";
        if ($this->seconds < 3600) return round($this->seconds / 60, 1) . "m";
        if ($this->seconds < 86400) return round($this->seconds / 3600, 1) . "h";
        return round($this->seconds / 86400, 1) . "d";
    }
}
