<?php
declare(strict_types=1);

namespace Nexus\Validation\Rules;

use Nexus\Validation\RuleInterface;
use Nexus\Validation\ValidationContext;

class Nullable implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        return true;
    }
    public function message(string $attribute): string
    {
        return '';
    }
}

class StringType implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        return $value === null || is_string($value);
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must be a string.";
    }
}

class IntegerType implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null) return true;
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must be an integer.";
    }
}

class NumericType implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        return $value === null || is_numeric($value);
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must be numeric.";
    }
}

class BooleanType implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null) return true;
        $acceptable = [true, false, 1, 0, '1', '0', 'true', 'false', 'on', 'off', 'yes', 'no'];
        return in_array($value, $acceptable, true);
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must be a boolean.";
    }
}

class ArrayType implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        return $value === null || is_array($value);
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must be an array.";
    }
}

class ObjectType implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        return $value === null || is_object($value) || is_array($value);
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must be an object.";
    }
}

class Url implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null || $value === '') return true;
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must be a valid URL.";
    }
}

class Uuid implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null || $value === '') return true;
        return is_string($value) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value) === 1;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must be a valid UUID.";
    }
}

class Date implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null || $value === '') return true;
        if ($value instanceof \DateTimeInterface) return true;
        return is_string($value) && strtotime($value) !== false;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field is not a valid date.";
    }
}

class DateTimeRule implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null || $value === '') return true;
        if ($value instanceof \DateTimeInterface) return true;
        return is_string($value) && \DateTime::createFromFormat('Y-m-d H:i:s', $value) !== false;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field does not match format Y-m-d H:i:s.";
    }
}

class DateFormat implements RuleInterface
{
    public function __construct(protected string $format) {}
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null || $value === '') return true;
        if (!is_string($value)) return false;
        $d = \DateTime::createFromFormat($this->format, $value);
        return $d && $d->format($this->format) === $value;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field does not match format {$this->format}.";
    }
}

class Length implements RuleInterface
{
    public function __construct(protected int $length) {}
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null) return true;
        if (is_string($value)) return mb_strlen($value) === $this->length;
        if (is_array($value)) return count($value) === $this->length;
        return false;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must be exactly {$this->length} characters.";
    }
}

class MinLength implements RuleInterface
{
    public function __construct(protected int $min) {}
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null) return true;
        if (is_string($value)) return mb_strlen($value) >= $this->min;
        if (is_array($value)) return count($value) >= $this->min;
        return false;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must be at least {$this->min} characters.";
    }
}

class MaxLength implements RuleInterface
{
    public function __construct(protected int $max) {}
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null) return true;
        if (is_string($value)) return mb_strlen($value) <= $this->max;
        if (is_array($value)) return count($value) <= $this->max;
        return false;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must not exceed {$this->max} characters.";
    }
}

class Between implements RuleInterface
{
    public function __construct(protected float $min, protected float $max) {}
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null) return true;
        if (is_numeric($value)) {
            $num = (float) $value;
            return $num >= $this->min && $num <= $this->max;
        }
        if (is_string($value)) {
            $len = mb_strlen($value);
            return $len >= $this->min && $len <= $this->max;
        }
        if (is_array($value)) {
            $cnt = count($value);
            return $cnt >= $this->min && $cnt <= $this->max;
        }
        return false;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must be between {$this->min} and {$this->max}.";
    }
}

class Regex implements RuleInterface
{
    public function __construct(protected string $pattern) {}
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null || $value === '') return true;
        return is_string($value) && preg_match($this->pattern, $value) === 1;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field format is invalid.";
    }
}

class InRule implements RuleInterface
{
    public function __construct(protected array $values) {}
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null) return true;
        return in_array((string)$value, array_map('strval', $this->values), true);
    }
    public function message(string $attribute): string
    {
        return "The selected {$attribute} is invalid.";
    }
}

class NotInRule implements RuleInterface
{
    public function __construct(protected array $values) {}
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null) return true;
        return !in_array((string)$value, array_map('strval', $this->values), true);
    }
    public function message(string $attribute): string
    {
        return "The selected {$attribute} is invalid.";
    }
}

class Same implements RuleInterface
{
    public function __construct(protected string $otherField) {}
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        $otherVal = $context instanceof ValidationContext ? $context->getValue($this->otherField) : ($context[$this->otherField] ?? null);
        return $value === $otherVal;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must match {$this->otherField}.";
    }
}

class Different implements RuleInterface
{
    public function __construct(protected string $otherField) {}
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        $otherVal = $context instanceof ValidationContext ? $context->getValue($this->otherField) : ($context[$this->otherField] ?? null);
        return $value !== $otherVal;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must be different from {$this->otherField}.";
    }
}

class Confirmed implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        $confirmationKey = $attribute . '_confirmation';
        $otherVal = $context instanceof ValidationContext ? $context->getValue($confirmationKey) : ($context[$confirmationKey] ?? null);
        return $value === $otherVal;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} confirmation does not match.";
    }
}

class RequiredIf implements RuleInterface
{
    public function __construct(protected string $otherField, protected mixed $otherValue) {}
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        $val = $context instanceof ValidationContext ? $context->getValue($this->otherField) : ($context[$this->otherField] ?? null);
        if ((string)$val === (string)$this->otherValue) {
            return (new Required())->passes($attribute, $value, $context);
        }
        return true;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field is required when {$this->otherField} is {$this->otherValue}.";
    }
}

class RequiredUnless implements RuleInterface
{
    public function __construct(protected string $otherField, protected mixed $otherValue) {}
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        $val = $context instanceof ValidationContext ? $context->getValue($this->otherField) : ($context[$this->otherField] ?? null);
        if ((string)$val !== (string)$this->otherValue) {
            return (new Required())->passes($attribute, $value, $context);
        }
        return true;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field is required unless {$this->otherField} is {$this->otherValue}.";
    }
}

class RequiredWith implements RuleInterface
{
    public function __construct(protected array $fields) {}
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        foreach ($this->fields as $field) {
            $val = $context instanceof ValidationContext ? $context->getValue($field) : ($context[$field] ?? null);
            if ($val !== null && $val !== '') {
                return (new Required())->passes($attribute, $value, $context);
            }
        }
        return true;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field is required when " . implode('/', $this->fields) . " is present.";
    }
}

class RequiredWithout implements RuleInterface
{
    public function __construct(protected array $fields) {}
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        foreach ($this->fields as $field) {
            $val = $context instanceof ValidationContext ? $context->getValue($field) : ($context[$field] ?? null);
            if ($val === null || $val === '') {
                return (new Required())->passes($attribute, $value, $context);
            }
        }
        return true;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field is required when " . implode('/', $this->fields) . " is missing.";
    }
}

class Accepted implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        $acceptable = ['yes', 'on', '1', 1, true, 'true'];
        return in_array($value, $acceptable, true);
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must be accepted.";
    }
}

class Ip implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null || $value === '') return true;
        return is_string($value) && filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must be a valid IP address.";
    }
}

class Ipv4 implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null || $value === '') return true;
        return is_string($value) && filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must be a valid IPv4 address.";
    }
}

class Ipv6 implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null || $value === '') return true;
        return is_string($value) && filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must be a valid IPv6 address.";
    }
}

class JsonRule implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null || $value === '') return true;
        if (!is_string($value)) return false;
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }
    public function message(string $attribute): string
    {
        return "The {$attribute} field must be a valid JSON string.";
    }
}

class Sometimes implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        return true;
    }
    public function message(string $attribute): string
    {
        return '';
    }
}

class Bail implements RuleInterface
{
    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        return true;
    }
    public function message(string $attribute): string
    {
        return '';
    }
}
