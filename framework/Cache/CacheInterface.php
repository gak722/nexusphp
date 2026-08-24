<?php
declare(strict_types=1);

namespace Nexus\Cache;

/**
 * Unified Cache Contract
 */
interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value, ?int $ttl = null): bool;
    public function has(string $key): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
    public function remember(string $key, int $ttl, \Closure $callback): mixed;
    /**
     * Increment a numeric cache value atomically. If the key does not exist it will be created
     * with the increment value and optional TTL. Returns the new numeric value.
     *
     * Note: cache drivers store values in a JSON envelope for cross-driver safety.
     */
    public function increment(string $key, int $value = 1, ?int $ttl = null): int;
}
