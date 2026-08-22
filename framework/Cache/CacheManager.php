<?php
declare(strict_types=1);

namespace Nexus\Cache;

use Nexus\Foundation\Application;

/**
 * Cache Driver Factory & Manager
 */
class CacheManager
{
    protected array $drivers = [];

    public function __construct(protected Application $app) {}

    public function driver(?string $name = null): CacheInterface
    {
        $name = $name ?? env('CACHE_DRIVER', 'file');

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        return $this->drivers[$name];
    }

    protected function createDriver(string $name): CacheInterface
    {
        return match ($name) {
            'file' => new FileCache($this->app->storagePath('cache')),
            'apcu' => function_exists('apcu_enabled') && apcu_enabled()
                ? new ApcuCache()
                : new FileCache($this->app->storagePath('cache')),
            'redis' => class_exists('\Redis')
                ? new RedisCache(env('REDIS_HOST', '127.0.0.1'), (int) env('REDIS_PORT', 6379))
                : new FileCache($this->app->storagePath('cache')),
            default => new FileCache($this->app->storagePath('cache')),
        };
    }
}
