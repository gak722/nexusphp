<?php
declare(strict_types=1);

namespace Nexus\Database\Schema;

class ColumnDefinition
{
    public bool $nullable = false;
    public mixed $default = null;
    public bool $unique = false;
    public bool $autoIncrement = false;
    public ?string $comment = null;

    public function __construct(public string $name, public string $type, public array $attributes = []) {}

    public function nullable(bool $nullable = true): static
    {
        $this->nullable = $nullable;
        return $this;
    }

    public function default(mixed $value): static
    {
        $this->default = $value;
        return $this;
    }

    public function unique(): static
    {
        $this->unique = true;
        return $this;
    }

    public function index(): static
    {
        $this->attributes['index'] = true;
        return $this;
    }
}
