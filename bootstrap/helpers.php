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

if (!function_exists('config')) {
    /**
     * Get a value from the shared configuration repository using dot notation,
     * e.g. config('security.headers.Content-Security-Policy').
     */
    function config(string $key, mixed $default = null): mixed
    {
        return \Nexus\Foundation\Application::getInstance()
            ->make(\Nexus\Foundation\Config::class)
            ->get($key, $default);
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('response')) {
    function response(string $content = '', int $status = 200, array $headers = []): \Nexus\Http\Response|\Nexus\Http\ResponseFactory
    {
        if (func_num_args() === 0) {
            return new \Nexus\Http\ResponseFactory();
        }
        return new \Nexus\Http\Response($content, $status, $headers);
    }
}

if (!function_exists('view')) {
    function view(string $name, array $data = [], int $status = 200, array $headers = []): \Nexus\Http\Response
    {
        return (new \Nexus\Http\ResponseFactory())->view($name, $data, $status, $headers);
    }
}

if (!function_exists('str')) {
    function str(?string $string = null): \Nexus\Support\Str\Stringable|string
    {
        if ($string === null) {
            return new \Nexus\Support\Str();
        }
        return \Nexus\Support\Str\Stringable::of($string);
    }
}

if (!function_exists('collect')) {
    function collect(mixed $items = []): \Nexus\Support\Collection
    {
        return \Nexus\Support\Collection::make(is_array($items) ? $items : iterator_to_array($items));
    }
}

if (!function_exists('now')) {
    function now(?string $timezone = null): \Nexus\Support\DateTime\DateTime
    {
        return \Nexus\Support\DateTime\DateTime::now($timezone);
    }
}

if (!function_exists('today')) {
    function today(?string $timezone = null): \Nexus\Support\DateTime\DateTime
    {
        return \Nexus\Support\DateTime\DateTime::today($timezone);
    }
}

if (!function_exists('blank')) {
    function blank(mixed $value): bool
    {
        if ($value === null) return true;
        if (is_string($value)) return trim($value) === '';
        if (is_numeric($value) || is_bool($value)) return false;
        if ($value instanceof \Countable) return count($value) === 0;
        return empty($value);
    }
}

if (!function_exists('filled')) {
    function filled(mixed $value): bool
    {
        return !blank($value);
    }
}

if (!function_exists('tap')) {
    function tap(mixed $value, ?callable $callback = null): mixed
    {
        if ($callback === null) {
            return $value;
        }
        $callback($value);
        return $value;
    }
}

