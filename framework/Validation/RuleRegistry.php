<?php
declare(strict_types=1);

namespace Nexus\Validation;

use Nexus\Database\Connection;
use Nexus\Foundation\Application;

/**
 * Registry for validating rules, resolving dynamic rule parameters, and supporting extension.
 */
class RuleRegistry
{
    protected static array $customRules = [];

    public static function extend(string $name, string|callable|RuleInterface $rule): void
    {
        static::$customRules[strtolower($name)] = $rule;
    }

    public static function resolve(string $ruleString, ?Connection $dbResolver = null): ?RuleInterface
    {
        $params = [];
        if (str_contains($ruleString, ':')) {
            [$ruleName, $paramStr] = explode(':', $ruleString, 2);
            $params = str_getcsv($paramStr);
        } else {
            $ruleName = $ruleString;
        }

        $ruleNameLower = strtolower($ruleName);

        if (isset(static::$customRules[$ruleNameLower])) {
            $custom = static::$customRules[$ruleNameLower];
            if (is_string($custom) && class_exists($custom)) {
                return new $custom(...$params);
            }
            if (is_callable($custom)) {
                return new class($custom, $ruleName) implements RuleInterface {
                    public function __construct(protected $callback, protected string $name) {}
                    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool {
                        return (bool) ($this->callback)($attribute, $value, $context);
                    }
                    public function message(string $attribute): string {
                        return "The {$attribute} field is invalid.";
                    }
                };
            }
            if ($custom instanceof RuleInterface) {
                return $custom;
            }
        }

        return match ($ruleNameLower) {
            'required' => new Rules\Required(),
            'nullable' => new Rules\Nullable(),
            'string' => new Rules\StringType(),
            'integer', 'int' => new Rules\IntegerType(),
            'numeric' => new Rules\NumericType(),
            'boolean', 'bool' => new Rules\BooleanType(),
            'array' => new Rules\ArrayType(),
            'object' => new Rules\ObjectType(),
            'email' => new Rules\Email(),
            'url' => new Rules\Url(),
            'uuid' => new Rules\Uuid(),
            'date' => new Rules\Date(),
            'datetime' => new Rules\DateTimeRule(),
            'date_format' => new Rules\DateFormat($params[0] ?? 'Y-m-d'),
            'min' => new Rules\Min(is_numeric($params[0] ?? null) ? (float) $params[0] : 0),
            'max' => new Rules\Max(is_numeric($params[0] ?? null) ? (float) $params[0] : 0),
            'length' => new Rules\Length((int) ($params[0] ?? 0)),
            'min_length' => new Rules\MinLength((int) ($params[0] ?? 0)),
            'max_length' => new Rules\MaxLength((int) ($params[0] ?? 0)),
            'between' => new Rules\Between((float) ($params[0] ?? 0), (float) ($params[1] ?? 0)),
            'regex' => new Rules\Regex($params[0] ?? '//'),
            'in' => new Rules\InRule($params),
            'not_in' => new Rules\NotInRule($params),
            'same' => new Rules\Same($params[0] ?? ''),
            'different' => new Rules\Different($params[0] ?? ''),
            'confirmed' => new Rules\Confirmed(),
            'required_if' => new Rules\RequiredIf($params[0] ?? '', $params[1] ?? null),
            'required_unless' => new Rules\RequiredUnless($params[0] ?? '', $params[1] ?? null),
            'required_with' => new Rules\RequiredWith($params),
            'required_without' => new Rules\RequiredWithout($params),
            'accepted' => new Rules\Accepted(),
            'ip' => new Rules\Ip(),
            'ipv4' => new Rules\Ipv4(),
            'ipv6' => new Rules\Ipv6(),
            'json' => new Rules\JsonRule(),
            'unique' => new Rules\Unique($params[0] ?? '', $params[1] ?? null, $params[2] ?? null, $params[3] ?? 'id', $dbResolver),
            'exists' => new Rules\Exists($params[0] ?? '', $params[1] ?? null, $dbResolver),
            'sometimes' => new Rules\Sometimes(),
            'bail' => new Rules\Bail(),
            default => null,
        };
    }
}
