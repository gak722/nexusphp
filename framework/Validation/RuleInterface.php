<?php
declare(strict_types=1);

namespace Nexus\Validation;

/**
 * Validation Rule Interface with ValidationContext support
 */
interface RuleInterface
{
    /**
     * Determine if the validation rule passes.
     */
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool;

    /**
     * Get the validation error message.
     */
    public function message(string $attribute): string;
}
