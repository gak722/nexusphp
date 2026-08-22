<?php
declare(strict_types=1);

namespace Nexus\Validation\Rules;

use Nexus\Validation\RuleInterface;

class Max implements RuleInterface
{
    public function __construct(protected int|float $max) {}

    public function passes(string $attribute, mixed $value, array $data = []): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_numeric($value)) {
            return (float) $value <= $this->max;
        }

        if (is_string($value)) {
            return mb_strlen($value) <= $this->max;
        }

        if (is_array($value)) {
            return count($value) <= $this->max;
        }

        return false;
    }

    public function message(string $attribute): string
    {
        return "The {$attribute} field must not exceed {$this->max}.";
    }
}
