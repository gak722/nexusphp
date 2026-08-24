<?php
declare(strict_types=1);

namespace Nexus\Http\Resources;

use JsonSerializable;
use Nexus\Http\JsonResponse;
use Nexus\Support\Collection\Collection;
use Traversable;

class ResourceCollection implements JsonSerializable
{
    public mixed $resource;
    public string $collects;
    protected array|Collection $collection;

    public function __construct(mixed $resource, string $collects)
    {
        $this->resource = $resource;
        $this->collects = $collects;
        
        $this->collection = $this->collectResource($resource);
    }

    protected function collectResource(mixed $resource): array|Collection
    {
        if (is_array($resource)) {
            return array_map(fn($item) => new $this->collects($item), $resource);
        }

        if ($resource instanceof Collection) {
            return $resource->map(fn($item) => new $this->collects($item));
        }

        if ($resource instanceof Traversable) {
            $collected = [];
            foreach ($resource as $item) {
                $collected[] = new $this->collects($item);
            }
            return $collected;
        }

        return [$resource];
    }

    public function toArray(): array
    {
        return is_array($this->collection) 
            ? array_map(fn($item) => $item->resolve(), $this->collection)
            : $this->collection->map(fn($item) => $item->resolve())->all();
    }

    public function resolve(): array
    {
        return ['data' => $this->toArray()];
    }

    public function response(): JsonResponse
    {
        return new JsonResponse($this->resolve());
    }

    public function jsonSerialize(): array
    {
        return $this->resolve();
    }
}
