<?php
declare(strict_types=1);

namespace Nexus\Support\Str;

use Nexus\Support\Str as BaseStr;

class Stringable
{
    public function __construct(protected string $value) {}

    public static function of(string $value): static
    {
        return new static($value);
    }

    public function upper(): static
    {
        return new static(BaseStr::upper($this->value));
    }

    public function lower(): static
    {
        return new static(BaseStr::lower($this->value));
    }

    public function ucfirst(): static
    {
        return new static(BaseStr::ucfirst($this->value));
    }

    public function camel(): static
    {
        return new static(BaseStr::camel($this->value));
    }

    public function snake(string $delimiter = '_'): static
    {
        return new static(BaseStr::snake($this->value, $delimiter));
    }

    public function kebab(): static
    {
        return new static(BaseStr::kebab($this->value));
    }

    public function slug(string $separator = '-'): static
    {
        return new static(BaseStr::slug($this->value, $separator));
    }

    public function squish(): static
    {
        return new static(BaseStr::squish($this->value));
    }

    public function trim(): static
    {
        return new static(trim($this->value));
    }

    public function limit(int $limit = 100, string $end = '...'): static
    {
        return new static(BaseStr::limit($this->value, $limit, $end));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
