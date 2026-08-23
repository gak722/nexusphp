<?php
declare(strict_types=1);

namespace Nexus\Validation\Rules;

use Nexus\Validation\RuleInterface;

use Nexus\Validation\ValidationContext;

class Required implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null) {
            return false;
        }
        if (is_string($value) && trim($value) === '') {
            return false;
        }
        if (is_array($value) && empty($value)) {
            return false;
        }
        return true;
    }

    public function message(string $attribute): string
    {
        return "The {$attribute} field is required.";
    }
}
