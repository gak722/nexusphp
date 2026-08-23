<?php
declare(strict_types=1);

namespace Nexus\Database\Seeding;

use Nexus\Database\Connection;

abstract class Seeder
{
    public function __construct(protected Connection $connection) {}
    abstract public function run(): void;
}

abstract class ModelFactory
{
    protected int $count = 1;

    public static function new(): static
    {
        return new static();
    }

    public function count(int $count): static
    {
        $this->count = $count;
        return $this;
    }

    abstract protected function definition(): array;

    public function make(array $attributes = []): array
    {
        $results = [];
        for ($i = 0; $i < $this->count; $i++) {
            $results[] = array_merge($this->definition(), $attributes);
        }
        return $this->count === 1 ? $results[0] : $results;
    }
}
