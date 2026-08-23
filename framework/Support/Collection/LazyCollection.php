<?php
declare(strict_types=1);

namespace Nexus\Support\Collection;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use ArrayIterator;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements ArrayAccess<TKey, TValue>
 * @implements IteratorAggregate<TKey, TValue>
 */
class LazyCollection implements Countable, IteratorAggregate
{
    /** @var callable(): \Generator<TKey, TValue> */
    protected $source;

    public function __construct(callable $source)
    {
        $this->source = $source;
    }

    public static function make(mixed $source = []): static
    {
        if (is_callable($source)) {
            return new static($source);
        }

        return new static(function () use ($source) {
            foreach ($source as $key => $value) {
                yield $key => $value;
            }
        });
    }

    /**
     * @param callable(TValue, TKey): bool $callback
     */
    public function filter(callable $callback): static
    {
        $source = $this->source;
        return new static(function () use ($source, $callback) {
            foreach ($source() as $key => $value) {
                if ($callback($value, $key)) {
                    yield $key => $value;
                }
            }
        });
    }

    /**
     * @template TNewValue
     * @param callable(TValue, TKey): TNewValue $callback
     * @return static
     */
    public function map(callable $callback): static
    {
        $source = $this->source;
        return new static(function () use ($source, $callback) {
            foreach ($source() as $key => $value) {
                yield $key => $callback($value, $key);
            }
        });
    }

    public function take(int $limit): static
    {
        $source = $this->source;
        return new static(function () use ($source, $limit) {
            $count = 0;
            foreach ($source() as $key => $value) {
                if ($count >= $limit) break;
                yield $key => $value;
                $count++;
            }
        });
    }

    public function each(callable $callback): static
    {
        foreach (($this->source)() as $key => $value) {
            if ($callback($value, $key) === false) {
                break;
            }
        }
        return $this;
    }

    public function all(): array
    {
        return iterator_to_array(($this->source)());
    }

    public function count(): int
    {
        return iterator_count(($this->source)());
    }

    public function getIterator(): \Traversable
    {
        return ($this->source)();
    }
}
