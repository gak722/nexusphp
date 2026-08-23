<?php
declare(strict_types=1);

namespace Nexus\Binding;

use Nexus\Validation\ValidationErrors;

/**
 * Context and configuration for Model/DTO data binding.
 */
class BindingContext
{
    public function __construct(
        public readonly bool $allowUnsafeFields = false,
        public readonly string $onUnknownField = 'ignore', // 'ignore', 'error', 'exception'
        public readonly int $maxDepth = 10,
        public readonly int $currentDepth = 0,
        public readonly array $metadata = []
    ) {}

    public function nextLevel(): static
    {
        return new static(
            allowUnsafeFields: $this->allowUnsafeFields,
            onUnknownField: $this->onUnknownField,
            maxDepth: $this->maxDepth,
            currentDepth: $this->currentDepth + 1,
            metadata: $this->metadata
        );
    }
}
