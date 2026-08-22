<?php
declare(strict_types=1);

namespace Nexus\Cache;

use Nexus\Foundation\Application;
use Nexus\Foundation\Config;

/**
 * Cache Driver Factory & Manager
 */
class CacheManager
{
    protected array $drivers = [];

    public function __construct(protected Application $app) {}

    public function driver(?string $name = null): CacheInterface
    {
        $name = $name ?? $this->config()->get('cache.default', env('CACHE_DRIVER', 'file'));

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        return $this->drivers[$name];
    }

    protected function createDriver(string $name): CacheInterface
    {
        $config = $this->config();
        $storeConfig = $config->get("cache.stores.{$name}", []);

        $redisHost = $storeConfig['host'] ?? env('REDIS_HOST', '127.0.0.1');
        $redisPort = (int) ($storeConfig['port'] ?? env('REDIS_PORT', 6379));
        $filePath = ($storeConfig['path'] ?? null) ?: $this->app->storagePath('cache');

        return match ($name) {
            'file' => new FileCache($filePath),
            'apcu' => function_exists('apcu_enabled') && apcu_enabled()
                ? new ApcuCache()
                : new FileCache($filePath),
            'redis' => class_exists('\Redis')
                ? new RedisCache($redisHost, $redisPort)
                : new FileCache($filePath),
            default => new FileCache($filePath),
        };
    }

    protected function config(): Config
    {
        try {
            if ($this->app->has(Config::class)) {
                $config = $this->app->make(Config::class);

                if ($config instanceof Config) {
                    return $config;
                }
            }
        } catch (\Throwable $e) {
            // Fallback to standalone default Config instance
        }

        return new Config();
    }
}
