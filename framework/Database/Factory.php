<?php
declare(strict_types=1);

namespace Nexus\Database;

/**
 * Model Factory for testing and database seeding.
 */
abstract class Factory
{
    protected string $model;
    protected int $count = 1;
    protected array $states = [];

    /**
     * Define the model's default state.
     */
    abstract public function definition(): array;

    public static function new(array $attributes = []): static
    {
        $instance = new static();
        if (!empty($attributes)) {
            $instance->states[] = $attributes;
        }
        return $instance;
    }

    public function count(int $count): static
    {
        $clone = clone $this;
        $clone->count = max(1, $count);
        return $clone;
    }

    public function state(callable|array $state): static
    {
        $clone = clone $this;
        $clone->states[] = $state;
        return $clone;
    }

    public function make(array $attributes = []): Model|array
    {
        if ($this->count === 1) {
            return $this->makeInstance($attributes);
        }

        $instances = [];
        for ($i = 0; $i < $this->count; $i++) {
            $instances[] = $this->makeInstance($attributes);
        }

        return $instances;
    }

    public function create(array $attributes = []): Model|array
    {
        $instances = $this->make($attributes);

        if (is_array($instances)) {
            foreach ($instances as $instance) {
                $instance->save();
            }
        } else {
            $instances->save();
        }

        return $instances;
    }

    protected function makeInstance(array $attributes = []): Model
    {
        if (!class_exists($this->model)) {
            throw new \RuntimeException("Model class [{$this->model}] not found.");
        }

        $definition = $this->definition();

        foreach ($this->states as $state) {
            $resolvedState = is_callable($state) ? $state($definition) : $state;
            $definition = array_merge($definition, (array) $resolvedState);
        }

        $finalAttributes = array_merge($definition, $attributes);

        return new $this->model($finalAttributes);
    }
}
