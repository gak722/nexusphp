<?php
declare(strict_types=1);

namespace Nexus\Http\Resources;

use JsonSerializable;
use Nexus\Http\JsonResponse;

abstract class JsonResource implements JsonSerializable
{
    public mixed $resource;

    public function __construct(mixed $resource)
    {
        $this->resource = $resource;
    }

    public static function make(mixed ...$parameters): static
    {
        return new static(...$parameters);
    }

    public static function collection(mixed $resource): ResourceCollection
    {
        return new class($resource, static::class) extends ResourceCollection {};
    }

    abstract public function toArray(): array;

    public function resolve(): array
    {
        if ($this->resource === null) {
            return [];
        }

        return $this->toArray();
    }

    public function response(): JsonResponse
    {
        return new JsonResponse($this->resolve());
    }

    public function jsonSerialize(): array
    {
        return $this->resolve();
    }

    /**
     * Conditional value helper.
     */
    protected function when(bool $condition, mixed $value, mixed $default = null): mixed
    {
        if ($condition) {
            return is_callable($value) ? $value() : $value;
        }

        return is_callable($default) ? $default() : $default;
    }

    /**
     * Proxies property access to the underlying resource.
     */
    public function __get(string $key): mixed
    {
        if (is_object($this->resource)) {
            return $this->resource->{$key} ?? null;
        }

        if (is_array($this->resource)) {
            return $this->resource[$key] ?? null;
        }

        return null;
    }

    /**
     * Proxies method calls to the underlying resource.
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (is_object($this->resource) && method_exists($this->resource, $method)) {
            return $this->resource->{$method}(...$parameters);
        }

        throw new \BadMethodCallException("Method [{$method}] does not exist on resource.");
    }
}
