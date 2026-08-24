<?php
declare(strict_types=1);

namespace Nexus\Session;

use Nexus\Support\Arr;

/**
 * Handles application session state with dot-notation support.
 */
class SessionManager
{
    protected bool $started = false;

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        if (!headers_sent()) {
            session_start();
            $this->started = true;
        }
    }

    public function put(string $key, mixed $value): void
    {
        $this->start();
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }
        Arr::set($_SESSION, $key, $value);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return Arr::get($_SESSION, $key, $default);
    }

    public function all(): array
    {
        $this->start();
        return $_SESSION ?? [];
    }

    public function has(string $key): bool
    {
        $this->start();
        return Arr::has($_SESSION, $key);
    }

    public function forget(string|array $keys): void
    {
        $this->start();
        Arr::forget($_SESSION, $keys);
    }

    public function flush(): void
    {
        $this->start();
        $_SESSION = [];
    }

    public function regenerate(bool $destroy = false): bool
    {
        $this->start();
        if (session_status() === PHP_SESSION_ACTIVE) {
            return session_regenerate_id($destroy);
        }
        return false;
    }

    /**
     * Store flash data for the next request.
     */
    public function flash(string $key, mixed $value): void
    {
        $this->put($key, $value);
        $this->push('_flash.new', $key);
        $this->removeFrom('_flash.old', $key);
    }

    /**
     * Push a value onto an array session value.
     */
    public function push(string $key, mixed $value): void
    {
        $array = $this->get($key, []);
        if (!is_array($array)) {
            $array = [$array];
        }
        $array[] = $value;
        $this->put($key, $array);
    }

    protected function removeFrom(string $arrayKey, string $value): void
    {
        $array = $this->get($arrayKey, []);
        if (is_array($array)) {
            $index = array_search($value, $array, true);
            if ($index !== false) {
                unset($array[$index]);
                $this->put($arrayKey, array_values($array));
            }
        }
    }

    /**
     * Age the flash data for the session.
     */
    public function ageFlashData(): void
    {
        $this->start();
        $old = $this->get('_flash.old', []);
        if (is_array($old)) {
            foreach ($old as $key) {
                $this->forget($key);
            }
        }
        $new = $this->get('_flash.new', []);
        $this->put('_flash.old', is_array($new) ? $new : []);
        $this->put('_flash.new', []);
    }
}
