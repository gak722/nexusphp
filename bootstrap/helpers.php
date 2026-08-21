<?php
declare(strict_types=1);

/**
 * Bootstrap global helper functions for NexusPHP.
 */

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return \Nexus\Support\Env::get($key, $default);
    }
}

if (!function_exists('app')) {
    function app(?string $abstract = null): mixed
    {
        if ($abstract === null) {
            return \Nexus\Foundation\Application::getInstance();
        }

        return \Nexus\Foundation\Application::getInstance()->make($abstract);
    }
}

if (!function_exists('value')) {
    function value(mixed $value, ...$args): mixed
    {
        return $value instanceof \Closure ? $value(...$args) : $value;
    }
}
