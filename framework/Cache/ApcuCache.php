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
        if (!$success) {
            return $default;
        }
        // If value is a JSON envelope return its value, otherwise return raw
        if (is_string($value)) {
            $data = @json_decode($value, true);
            if (is_array($data) && isset($data['v'])) {
                $expiresAt = (int) ($data['expires_at'] ?? 0);
                if ($expiresAt !== 0 && time() > $expiresAt) {
                    $this->delete($key);
                    return $default;
                }
                return $data['value'] ?? $default;
            }
            if (getenv('CACHE_LEGACY_UNSERIALIZE') === 'true') {
                $legacy = @unserialize($value, ['allowed_classes' => false]);
                if ($legacy !== false) {
                    return $legacy;
                }
            }
        }
        return $value;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!function_exists('apcu_store')) {
            return false;
        }
        $expiresAt = $ttl !== null ? time() + $ttl : 0;
        $envelope = ['v' => 1, 'value' => $value, 'type' => gettype($value), 'expires_at' => $expiresAt];
        try {
            $payload = json_encode($envelope, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return false;
        }
        return apcu_store($key, $payload, $ttl ?? 0);
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
        $raw = apcu_fetch($key, $success);
        if (!$success) {
            $new = $value;
            $this->set($key, $new, $ttl);
            return $new;
        }
        // attempt to decode envelope
        if (is_string($raw)) {
            $data = @json_decode($raw, true);
            if (is_array($data) && isset($data['v']) && is_numeric($data['value'])) {
                $current = (int)$data['value'];
                $expiresAt = (int)($data['expires_at'] ?? 0);
                $new = $current + $value;
                $envelope = ['v' => 1, 'value' => $new, 'type' => 'number', 'expires_at' => $expiresAt];
                try {
                    $payload = json_encode($envelope, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    return $new;
                }
                apcu_store($key, $payload, $expiresAt !== 0 ? $expiresAt - time() : 0);
                return $new;
            }
        }
        // fallback to apcu_inc when possible
        $newVal = @apcu_inc($key, $value, $success);
        if ($success) {
            return (int)$newVal;
        }
        // last resort: overwrite
        $new = (int)$raw + $value;
        $this->set($key, $new, $ttl);
        return $new;
    }
}
