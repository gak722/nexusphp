<?php
declare(strict_types=1);

namespace Nexus\Cache;

/**
 * In-Memory APCu Opcode Cache Driver
 */
class ApcuCache implements CacheInterface
{
    public function __construct()
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            // Fallback or notice if APCu is disabled
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!function_exists('apcu_fetch')) {
            return $default;
        }
        $success = false;
        $value = apcu_fetch($key, $success);
        return $success ? $value : $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!function_exists('apcu_store')) {
            return false;
        }
        return apcu_store($key, $value, $ttl ?? 0);
    }

    public function has(string $key): bool
    {
        if (!function_exists('apcu_exists')) {
            return false;
        }
        return apcu_exists($key);
    }

    public function delete(string $key): bool
    {
        if (!function_exists('apcu_delete')) {
            return false;
        }
        return apcu_delete($key);
    }

    public function clear(): bool
    {
        if (!function_exists('apcu_clear_cache')) {
            return false;
        }
        return apcu_clear_cache();
    }

    public function remember(string $key, int $ttl, \Closure $callback): mixed
    {
        $value = $this->get($key);
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function increment(string $key, int $value = 1, ?int $ttl = null): int
    {
        if (!function_exists('apcu_inc')) {
            return 0;
        }
        $success = false;
        $newVal = apcu_inc($key, $value, $success);
        if (!$success) {
            apcu_store($key, $value, $ttl ?? 0);
            return $value;
        }
        return (int) $newVal;
    }
}
