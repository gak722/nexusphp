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

        $data = @unserialize($content);
        if (!is_array($data) || !isset($data['expires_at'])) {
            return $default;
        }

        if ($data['expires_at'] !== 0 && time() > $data['expires_at']) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $path = $this->getFilePath($key);
        $expiresAt = $ttl !== null ? time() + $ttl : 0;
        $payload = serialize(['expires_at' => $expiresAt, 'value' => $value]);

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
}
