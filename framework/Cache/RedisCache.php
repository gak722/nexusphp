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
        $data = @json_decode($val, true);
        if (is_array($data) && isset($data['v'])) {
            $expiresAt = (int) ($data['expires_at'] ?? 0);
            if ($expiresAt !== 0 && time() > $expiresAt) {
                $this->delete($key);
                return $default;
            }
            return $data['value'] ?? $default;
        }

        // legacy fallback controlled by env var (prohibited in production)
        if (getenv('CACHE_LEGACY_UNSERIALIZE') === 'true') {
            if (env('APP_ENV') === 'production') {
                throw new \RuntimeException('Legacy cache unserialization is prohibited in production.');
            }
            $legacy = @unserialize($val, ['allowed_classes' => false]);
            if ($legacy !== false) {
                return $legacy;
            }
        }

        return $val;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if ($this->redis === null) {
            return false;
        }
        $expiresAt = $ttl !== null ? time() + $ttl : 0;
        $envelope = ['v' => 1, 'value' => $value, 'type' => gettype($value), 'expires_at' => $expiresAt];
        try {
            $payload = json_encode($envelope, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return false;
        }
        if ($ttl !== null && $ttl > 0) {
            return $this->redis->setex($key, $ttl, $payload);
        }
        return $this->redis->set($key, $payload);
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
                $now = time();
                $expiresAt = $ttl !== null ? $now + $ttl : 0;
                $script = <<<'LUA'
local key = KEYS[1]
local inc = tonumber(ARGV[1])
local expires_at = tonumber(ARGV[2])
local now = tonumber(ARGV[3])
local v = redis.call('GET', key)
if not v then
    local env = {v=1, value=inc, type='number', expires_at=expires_at}
    local s = cjson.encode(env)
    if expires_at ~= 0 then
        redis.call('SET', key, s, 'EX', expires_at - now)
    else
        redis.call('SET', key, s)
    end
    return inc
end
local ok, parsed = pcall(cjson.decode, v)
if not ok or type(parsed) ~= 'table' or parsed.value == nil then
    local cur = tonumber(v) or 0
    cur = cur + inc
    local env = {v=1, value=cur, type='number', expires_at=expires_at}
    local s = cjson.encode(env)
    if expires_at ~= 0 then
        redis.call('SET', key, s, 'EX', expires_at - now)
    else
        redis.call('SET', key, s)
    end
    return cur
end
local cur = tonumber(parsed.value) or 0
cur = cur + inc
parsed.value = cur
local s = cjson.encode(parsed)
if parsed.expires_at and parsed.expires_at ~= 0 then
    local ttl = parsed.expires_at - now
    if ttl > 0 then
        redis.call('SET', key, s, 'EX', ttl)
    else
        redis.call('SET', key, s)
    end
else
    redis.call('SET', key, s)
end
return cur
LUA;
                try {
                        $result = $this->redis->eval($script, [$key, (string)$value, (string)$expiresAt, (string)$now], 1);
                        return (int) $result;
                } catch (\Throwable $e) {
                        // fallback: non-atomic best-effort
                        $val = $this->get($key, 0);
                        $new = (int)$val + $value;
                        $this->set($key, $new, $ttl);
                        return $new;
                }
    }
}
