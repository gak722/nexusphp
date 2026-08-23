<?php
declare(strict_types=1);

namespace Nexus\Validation;

use Nexus\Database\Connection;
use Nexus\Support\Arr;

/**
 * Main Rule Parsing & Nested Dot-Notation Validation Engine
 */
class Validator
{
    protected ValidationErrors $errors;
    protected array $customMessages = [];
    protected array $customAttributes = [];
    protected ?object $targetModel = null;
    protected ?Connection $dbConnection = null;

    public function __construct(
        protected array $data,
        protected array $rules,
        array $messages = [],
        array $customAttributes = []
    ) {
        $this->errors = new ValidationErrors();
        $this->customMessages = $messages;
        $this->customAttributes = $customAttributes;
    }

    public static function make(array $data, array $rules, array $messages = [], array $attributes = []): static
    {
        return new static($data, $rules, $messages, $attributes);
    }

    public function setTargetModel(?object $model): static
    {
        $this->targetModel = $model;
        return $this;
    }

    public function setDbConnection(?Connection $connection): static
    {
        $this->dbConnection = $connection;
        return $this;
    }

    public static function extend(string $name, string|callable|RuleInterface $rule): void
    {
        RuleRegistry::extend($name, $rule);
    }

    public function validate(): array
    {
        if ($this->fails()) {
            throw new ValidationException($this->errors);
        }
        return $this->validated();
    }

    public function validated(): array
    {
        $result = [];
        foreach (array_keys($this->rules) as $path) {
            if (str_contains($path, '*')) {
                // Wildcard extraction
                $matchedPaths = $this->expandWildcardPath($path);
                foreach ($matchedPaths as $expandedPath) {
                    if (Arr::has($this->data, $expandedPath)) {
                        Arr::set($result, $expandedPath, Arr::get($this->data, $expandedPath));
                    }
                }
            } else {
                if (Arr::has($this->data, $path)) {
                    Arr::set($result, $path, Arr::get($this->data, $path));
                }
            }
        }
        return $result;
    }

    public function fails(): bool
    {
        $this->errors = new ValidationErrors();

        foreach ($this->rules as $attributePath => $ruleset) {
            $rulesList = is_string($ruleset) ? explode('|', $ruleset) : $ruleset;

            if (str_contains($attributePath, '*')) {
                $expandedPaths = $this->expandWildcardPath($attributePath);
                foreach ($expandedPaths as $expandedPath) {
                    $this->validateAttributePath($expandedPath, $rulesList);
                }
            } else {
                $this->validateAttributePath($attributePath, $rulesList);
            }
        }

        return !$this->errors->isEmpty();
    }

    protected function validateAttributePath(string $path, array $rulesList): void
    {
        $hasValue = Arr::has($this->data, $path);
        $value = Arr::get($this->data, $path);

        $hasSometimes = false;
        $hasBail = false;
        $hasNullable = false;

        // Pre-scan rules
        foreach ($rulesList as $r) {
            $rName = is_string($r) ? explode(':', $r, 2)[0] : '';
            if (strtolower($rName) === 'sometimes') $hasSometimes = true;
            if (strtolower($rName) === 'bail') $hasBail = true;
            if (strtolower($rName) === 'nullable') $hasNullable = true;
        }

        if ($hasSometimes && !$hasValue) {
            return;
        }

        if ($hasNullable && ($value === null || $value === '')) {
            return;
        }

        foreach ($rulesList as $rule) {
            $ruleInstance = null;

            if ($rule instanceof RuleInterface) {
                $ruleInstance = $rule;
            } elseif (is_string($rule)) {
                $ruleInstance = RuleRegistry::resolve($rule, $this->dbConnection);
            }

            if ($ruleInstance === null) {
                continue;
            }

            $context = new ValidationContext(
                attribute: $path,
                value: $value,
                data: $this->data,
                targetModel: $this->targetModel,
                dbConnection: $this->dbConnection
            );

            if (!$ruleInstance->passes($path, $value, $context)) {
                $msg = $this->formatMessage($path, $ruleInstance, $rule);
                $this->errors->add($path, $msg);

                if ($hasBail) {
                    break;
                }
            }
        }
    }

    protected function formatMessage(string $path, RuleInterface $ruleInstance, mixed $ruleRaw): string
    {
        $ruleName = is_string($ruleRaw) ? strtolower(explode(':', $ruleRaw, 2)[0]) : strtolower(basename(str_replace('\\', '/', $ruleInstance::class)));
        
        $customKey = "{$path}.{$ruleName}";
        if (isset($this->customMessages[$customKey])) {
            return $this->customMessages[$customKey];
        }

        if (isset($this->customMessages[$path])) {
            return $this->customMessages[$path];
        }

        $displayName = $this->customAttributes[$path] ?? str_replace('_', ' ', basename(str_replace('.', ' ', $path)));
        $msg = $ruleInstance->message($displayName);

        return $msg;
    }

    protected function expandWildcardPath(string $pattern): array
    {
        $segments = explode('.', $pattern);
        return $this->expandSegments($this->data, $segments, '');
    }

    protected function expandSegments(mixed $currentData, array $segments, string $prefix): array
    {
        if (empty($segments)) {
            return [$prefix];
        }

        $segment = array_shift($segments);
        $results = [];

        if ($segment === '*') {
            if (is_array($currentData)) {
                foreach (array_keys($currentData) as $key) {
                    $newPrefix = $prefix === '' ? (string)$key : "{$prefix}.{$key}";
                    $results = array_merge($results, $this->expandSegments($currentData[$key], $segments, $newPrefix));
                }
            }
        } else {
            $newPrefix = $prefix === '' ? $segment : "{$prefix}.{$segment}";
            $nextData = is_array($currentData) && array_key_exists($segment, $currentData) ? $currentData[$segment] : null;
            $results = array_merge($results, $this->expandSegments($nextData, $segments, $newPrefix));
        }

        return $results;
    }

    public function errors(): ValidationErrors
    {
        return $this->errors;
    }
}
