<?php
declare(strict_types=1);

namespace Nexus\Support;

use Nexus\Session\SessionManager;
use Nexus\Foundation\Application;

/**
 * Static accessor for application session state.
 */
class Session
{
    protected static ?SessionManager $manager = null;

    protected static function manager(): SessionManager
    {
        if (static::$manager === null) {
            $app = Application::getInstance();
            if ($app->has(SessionManager::class)) {
                static::$manager = $app->make(SessionManager::class);
            } else {
                static::$manager = new SessionManager();
                $app->instance(SessionManager::class, static::$manager);
            }
        }
        return static::$manager;
    }

    public static function start(): void
    {
        static::manager()->start();
    }

    public static function put(string $key, mixed $value): void
    {
        static::manager()->put($key, $value);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::manager()->get($key, $default);
    }

    public static function all(): array
    {
        return static::manager()->all();
    }

    public static function has(string $key): bool
    {
        return static::manager()->has($key);
    }

    public static function forget(string|array $keys): void
    {
        static::manager()->forget($keys);
    }

    public static function flush(): void
    {
        static::manager()->flush();
    }

    public static function regenerate(bool $destroy = false): bool
    {
        return static::manager()->regenerate($destroy);
    }

    public static function flash(string $key, mixed $value): void
    {
        static::manager()->flash($key, $value);
    }
}
