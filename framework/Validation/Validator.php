<?php
declare(strict_types=1);

namespace Nexus\Validation;

use Nexus\Validation\Rules\Email;
use Nexus\Validation\Rules\Max;
use Nexus\Validation\Rules\Min;
use Nexus\Validation\Rules\Required;
use Nexus\Validation\Rules\Unique;

/**
 * Main Rule Parsing Validation Runner
 */
class Validator
{
    protected array $errors = [];

    public function __construct(
        protected array $data,
        protected array $rules
    ) {}

    public static function make(array $data, array $rules): static
    {
        return new static($data, $rules);
    }

    public function validate(): array
    {
        if ($this->fails()) {
            throw new ValidationException($this->errors);
        }
        return array_intersect_key($this->data, $this->rules);
    }

    public function fails(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleset) {
            $rulesList = is_string($ruleset) ? explode('|', $ruleset) : $ruleset;
            $value = $this->data[$field] ?? null;

            foreach ($rulesList as $rule) {
                if ($rule instanceof RuleInterface) {
                    if (!$rule->passes($field, $value, $this->data)) {
                        $this->errors[$field][] = $rule->message($field);
                    }
                    continue;
                }

                if (is_string($rule)) {
                    $this->validateStringRule($field, $value, $rule);
                }
            }
        }

        return !empty($this->errors);
    }

    protected function validateStringRule(string $field, mixed $value, string $rule): void
    {
        $params = [];
        if (str_contains($rule, ':')) {
            [$ruleName, $paramStr] = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        } else {
            $ruleName = $rule;
        }

        $ruleInstance = match ($ruleName) {
            'required' => new Required(),
            'email' => new Email(),
            'min' => new Min((int) ($params[0] ?? 0)),
            'max' => new Max((int) ($params[0] ?? 0)),
            'unique' => new Unique($params[0] ?? '', $params[1] ?? null, $params[2] ?? null),
            'confirmed' => new class($field) implements RuleInterface {
                public function __construct(protected string $field) {}
                public function passes(string $attribute, mixed $value, array $data = []): bool {
                    $confirmationKey = $attribute . '_confirmation';
                    return isset($data[$confirmationKey]) && $data[$confirmationKey] === $value;
                }
                public function message(string $attribute): string {
                    return "The {$attribute} confirmation does not match.";
                }
            },
            default => null,
        };

        if ($ruleInstance instanceof RuleInterface) {
            if (!$ruleInstance->passes($field, $value, $this->data)) {
                $this->errors[$field][] = $ruleInstance->message($field);
            }
        }
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
