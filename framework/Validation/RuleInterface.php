<?php
declare(strict_types=1);

namespace Nexus\Validation;

/**
 * Validation Rule Interface Contract
 */
interface RuleInterface
{
    public function passes(string $attribute, mixed $value, array $data = []): bool;
    public function message(string $attribute): string;
}
