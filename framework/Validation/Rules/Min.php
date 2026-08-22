<?php
declare(strict_types=1);

namespace Nexus\Validation\Rules;

use Nexus\Validation\RuleInterface;

class Min implements RuleInterface
{
    public function __construct(protected int|float $min) {}

    public function passes(string $attribute, mixed $value, array $data = []): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_numeric($value)) {
            return (float) $value >= $this->min;
        }

        if (is_string($value)) {
            return mb_strlen($value) >= $this->min;
        }

        if (is_array($value)) {
            return count($value) >= $this->min;
        }

        return false;
    }

    public function message(string $attribute): string
    {
        return "The {$attribute} field must be at least {$this->min}.";
    }
}
