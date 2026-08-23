<?php
declare(strict_types=1);

namespace Nexus\Validation;

/**
 * Structured validation error container supporting nested dot-notation paths and custom formatting.
 */
class ValidationErrors implements \ArrayAccess, \Countable, \JsonSerializable
{
    protected array $messages = [];

    public function __construct(array $messages = [])
    {
        foreach ($messages as $key => $msgs) {
            $this->add($key, (array) $msgs);
        }
    }

    public function add(string $key, string|array $messages): static
    {
        foreach ((array) $messages as $msg) {
            $this->messages[$key][] = $msg;
        }
        return $this;
    }

    public function has(string $key): bool
    {
        if (isset($this->messages[$key]) && !empty($this->messages[$key])) {
            return true;
        }

        // Support dot wildcard checking
        if (str_contains($key, '*')) {
            $pattern = '/^' . str_replace('\*', '[^\.]+', preg_quote($key, '/')) . '$/';
            foreach (array_keys($this->messages) as $k) {
                if (preg_match($pattern, $k)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function first(?string $key = null, ?string $default = null): ?string
    {
        if ($key === null) {
            foreach ($this->messages as $msgs) {
                if (!empty($msgs)) {
                    return $msgs[0];
                }
            }
            return $default;
        }

        $get = $this->get($key);
        return !empty($get) ? $get[0] : $default;
    }

    public function get(string $key): array
    {
        if (isset($this->messages[$key])) {
            return $this->messages[$key];
        }

        if (str_contains($key, '*')) {
            $pattern = '/^' . str_replace('\*', '[^\.]+', preg_quote($key, '/')) . '$/';
            $matched = [];
            foreach ($this->messages as $k => $msgs) {
                if (preg_match($pattern, $k)) {
                    $matched = array_merge($matched, $msgs);
                }
            }
            return $matched;
        }

        return [];
    }

    public function all(): array
    {
        return \Nexus\Support\Arr::flatten($this->messages);
    }

    public function toArray(): array
    {
        return $this->messages;
    }

    public function isEmpty(): bool
    {
        return empty($this->messages);
    }

    public function count(): int
    {
        return count($this->messages);
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string) $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->add((string) $offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->messages[(string) $offset]);
    }
}
