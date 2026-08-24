<?php
declare(strict_types=1);

namespace Nexus\Cache;

/**
 * File System Cache Driver
 */
class FileCache implements CacheInterface
{
    public function __construct(protected string $storagePath)
    {
        if (!is_dir($this->storagePath)) {
            @mkdir($this->storagePath, 0755, true);
        }
    }

    protected function getFilePath(string $key): string
    {
        return $this->storagePath . '/' . md5($key) . '.cache';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $path = $this->getFilePath($key);
        if (!file_exists($path)) {
            return $default;
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            return $default;
        }

        $data = @json_decode($content, true);
        if (!is_array($data) || !isset($data['v'])) {
            // legacy migration path: attempt controlled unserialize only when enabled (prohibited in production)
            if (getenv('CACHE_LEGACY_UNSERIALIZE') === 'true') {
                if (env('APP_ENV') === 'production') {
                    throw new \RuntimeException('Legacy cache unserialization is prohibited in production.');
                }
                $legacy = @unserialize($content, ['allowed_classes' => false]);
                if (is_array($legacy) && isset($legacy['expires_at'])) {
                    if ($legacy['expires_at'] !== 0 && time() > $legacy['expires_at']) {
                        $this->delete($key);
                        return $default;
                    }
                    return $legacy['value'];
                }
            }
            return $default;
        }

        $expiresAt = (int) ($data['expires_at'] ?? 0);
        if ($expiresAt !== 0 && time() > $expiresAt) {
            $this->delete($key);
            return $default;
        }

        return $data['value'] ?? $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $path = $this->getFilePath($key);
        $expiresAt = $ttl !== null ? time() + $ttl : 0;
        $envelope = ['v' => 1, 'value' => $value, 'type' => gettype($value), 'expires_at' => $expiresAt];
        try {
            $payload = json_encode($envelope, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return false;
        }

        return file_put_contents($path, $payload, LOCK_EX) !== false;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): bool
    {
        $path = $this->getFilePath($key);
        if (file_exists($path)) {
            return @unlink($path);
        }
        return true;
    }

    public function clear(): bool
    {
        $files = glob($this->storagePath . '/*.cache');
        if (is_array($files)) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        return true;
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
        $path = $this->getFilePath($key);
        $fp = @fopen($path, 'c+');
        if (!$fp) {
            return 0;
        }
        flock($fp, LOCK_EX);
        $content = stream_get_contents($fp);
        $current = 0;
        $expiresAt = $ttl !== null ? time() + $ttl : 0;
        if ($content !== false && $content !== '') {
            $data = @json_decode($content, true);
            if (!is_array($data) || !isset($data['v'])) {
                if (getenv('CACHE_LEGACY_UNSERIALIZE') === 'true') {
                    $legacy = @unserialize($content, ['allowed_classes' => false]);
                    if (is_array($legacy) && isset($legacy['value']) && is_numeric($legacy['value'])) {
                        if ($legacy['expires_at'] === 0 || time() <= $legacy['expires_at']) {
                            $current = (int) $legacy['value'];
                            if ($legacy['expires_at'] !== 0 && $ttl === null) {
                                $expiresAt = $legacy['expires_at'];
                            }
                        }
                    }
                }
            } else {
                if (isset($data['value']) && is_numeric($data['value'])) {
                    if (($data['expires_at'] ?? 0) === 0 || time() <= (int) $data['expires_at']) {
                        $current = (int) $data['value'];
                        if (($data['expires_at'] ?? 0) !== 0 && $ttl === null) {
                            $expiresAt = (int) $data['expires_at'];
                        }
                    }
                }
            }
        }
        $newVal = $current + $value;
        $envelope = ['v' => 1, 'value' => $newVal, 'type' => 'number', 'expires_at' => $expiresAt];
        try {
            $payload = json_encode($envelope, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            flock($fp, LOCK_UN);
            fclose($fp);
            return $newVal;
        }
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, $payload);
        flock($fp, LOCK_UN);
        fclose($fp);
        return $newVal;
    }
}
