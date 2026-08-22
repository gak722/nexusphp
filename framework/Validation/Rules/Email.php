<?php
declare(strict_types=1);

namespace Nexus\Validation\Rules;

use Nexus\Validation\RuleInterface;

class Email implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array $data = []): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        return filter_var((string) $value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function message(string $attribute): string
    {
        return "The {$attribute} field must be a valid email address.";
    }
}
