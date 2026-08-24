<?php
declare(strict_types=1);

namespace Nexus\Security;

use Nexus\Foundation\Application;
use Nexus\Cache\CacheManager;

/**
 * Shared Multi-Process Rate Limiter with In-Memory & Distributed Store Support
 */
class RateLimiter
{
    protected static array $hits = [];

    public static function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds = 60): bool
    {
        $cache = static::getCache();
        if ($cache !== null) {
            $currentHits = (int) ($cache->get("rate_limit:{$key}") ?? 0);
            return $currentHits >= $maxAttempts;
        }

        $now = time();
        self::cleanOldHits($key, $now - $decaySeconds);

        return count(self::$hits[$key] ?? []) >= $maxAttempts;
    }

    public static function hit(string $key, int $decaySeconds = 60): void
    {
        $cache = static::getCache();
        if ($cache !== null) {
            $cacheKey = "rate_limit:{$key}";
            if (method_exists($cache, 'increment')) {
                $cache->increment($cacheKey, 1, $decaySeconds);
            } else {
                $current = (int) ($cache->get($cacheKey) ?? 0);
                $cache->set($cacheKey, $current + 1, $decaySeconds);
            }
            return;
        }

        self::$hits[$key][] = time();
    }

    public static function resetAttempts(string $key): void
    {
        $cache = static::getCache();
        if ($cache !== null) {
            $cache->delete("rate_limit:{$key}");
            return;
        }

        unset(self::$hits[$key]);
    }

    protected static function cleanOldHits(string $key, int $cutoff): void
    {
        if (isset(self::$hits[$key])) {
            self::$hits[$key] = array_filter(self::$hits[$key], fn($time) => $time > $cutoff);
        }
    }

    protected static function getCache(): mixed
    {
        try {
            $app = Application::getInstance();
            if ($app->has(CacheManager::class)) {
                return $app->make(CacheManager::class)->driver();
            }
        } catch (\Throwable $e) {
            // Fallback to in-memory array if Cache service is unavailable
        }

        return null;
    }
}
