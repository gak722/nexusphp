<?php
declare(strict_types=1);

namespace Nexus\Validation;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Validate
{
    public array $rules;

    public function __construct(string|array|RuleInterface ...$rules)
    {
        $parsed = [];
        foreach ($rules as $rule) {
            if (is_array($rule)) {
                $parsed = array_merge($parsed, $rule);
            } else {
                $parsed[] = $rule;
            }
        }
        $this->rules = $parsed;
    }
}
