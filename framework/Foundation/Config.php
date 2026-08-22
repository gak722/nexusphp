<?php
declare(strict_types=1);

namespace Nexus\Foundation;

/**
 * Framework Configuration Loader
 */
class Config
{
    protected array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        try {
            if (!str_contains($key, '.')) {
                return $this->items[$key] ?? $default;
            }

            $array = $this->items;
            foreach (explode('.', $key) as $segment) {
                if (is_array($array) && array_key_exists($segment, $array)) {
                    $array = $array[$segment];
                } else {
                    return $default;
                }
            }

            return $array;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public function set(string $key, mixed $value): void
    {
        try {
            $keys = explode('.', $key);
            $array = &$this->items;

            while (count($keys) > 1) {
                $key = array_shift($keys);
                if (!isset($array[$key]) || !is_array($array[$key])) {
                    $array[$key] = [];
                }
                $array = &$array[$key];
            }

            $array[array_shift($keys)] = $value;
        } catch (\Throwable $e) {
            // Silently ignore configuration mutation errors in safe fallback mode.
        }
    }

    public function has(string $key): bool
    {
        try {
            return $this->get($key) !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function all(): array
    {
        return $this->items;
    }
}
