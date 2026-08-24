<?php
declare(strict_types=1);

namespace Nexus\Cache;

/**
 * High-Throughput Redis Cache Driver
 */
class RedisCache implements CacheInterface
{
    protected ?\Redis $redis = null;

    public function __construct(string $host = '127.0.0.1', int $port = 6379)
    {
        if (class_exists('\Redis')) {
            try {
                $this->redis = new \Redis();
                @$this->redis->connect($host, $port, 1.5);
            } catch (\Throwable $e) {
                $this->redis = null;
            }
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->redis === null) {
            return $default;
        }
        $val = $this->redis->get($key);
        if ($val === false) {
            return $default;
        }
        $data = @unserialize($val, ['allowed_classes' => false]);
        return $data !== false ? $data : $val;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if ($this->redis === null) {
            return false;
        }
        $serialized = serialize($value);
        if ($ttl !== null && $ttl > 0) {
            return $this->redis->setex($key, $ttl, $serialized);
        }
        return $this->redis->set($key, $serialized);
    }

    public function has(string $key): bool
    {
        if ($this->redis === null) {
            return false;
        }
        return (bool) $this->redis->exists($key);
    }

    public function delete(string $key): bool
    {
        if ($this->redis === null) {
            return false;
        }
        return $this->redis->del($key) > 0;
    }

    public function clear(): bool
    {
        if ($this->redis === null) {
            return false;
        }
        return $this->redis->flushDB();
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
        if ($this->redis === null) {
            return 0;
        }
        $newVal = (int) $this->redis->incrBy($key, $value);
        if ($ttl !== null && $ttl > 0 && $newVal === $value) {
            $this->redis->expire($key, $ttl);
        }
        return $newVal;
    }
}
