<?php
declare(strict_types=1);

namespace Nexus\Security;

/**
 * Sliding Window In-Memory Rate Limiter
 */
class RateLimiter
{
    protected static array $hits = [];

    public static function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds = 60): bool
    {
        $now = time();
        self::cleanOldHits($key, $now - $decaySeconds);

        return count(self::$hits[$key] ?? []) >= $maxAttempts;
    }

    public static function hit(string $key): void
    {
        self::$hits[$key][] = time();
    }

    public static function resetAttempts(string $key): void
    {
        unset(self::$hits[$key]);
    }

    protected static function cleanOldHits(string $key, int $cutoff): void
    {
        if (isset(self::$hits[$key])) {
            self::$hits[$key] = array_filter(self::$hits[$key], fn($time) => $time > $cutoff);
        }
    }
}
