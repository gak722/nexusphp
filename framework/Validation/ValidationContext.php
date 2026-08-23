<?php
declare(strict_types=1);

namespace Nexus\Validation;

use Nexus\Database\Connection;

/**
 * Context object supplied to validation rules during evaluation.
 */
class ValidationContext
{
    public function __construct(
        public readonly string $attribute,
        public readonly mixed $value,
        public readonly array $data = [],
        public readonly ?object $targetModel = null,
        public readonly ?Connection $dbConnection = null,
        public readonly array $metadata = []
    ) {}

    public function getValue(string $path, mixed $default = null): mixed
    {
        return \Nexus\Support\Arr::get($this->data, $path, $default);
    }

    public function hasValue(string $path): bool
    {
        return \Nexus\Support\Arr::has($this->data, $path);
    }
}
