<?php
declare(strict_types=1);

namespace Nexus\Support\Parser;

use Nexus\Support\Result\ParseResult;

class BooleanParser implements ParserInterface
{
    protected static array $truthy = ['true', 'yes', '1', 'on', 't', 'y', 'enabled'];
    protected static array $falsy = ['false', 'no', '0', 'off', 'f', 'n', 'disabled'];

    public function parse(mixed $value): ParseResult
    {
        if (is_bool($value)) {
            return ParseResult::ok($value, $value);
        }

        if (is_int($value) || is_float($value)) {
            if ($value === 1 || $value === 1.0) {
                return ParseResult::ok(true, $value);
            }
            if ($value === 0 || $value === 0.0) {
                return ParseResult::ok(false, $value);
            }
            return ParseResult::fail("Numeric value '{$value}' is ambiguous for boolean parsing.", $value);
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, static::$truthy, true)) {
                return ParseResult::ok(true, $value);
            }
            if (in_array($normalized, static::$falsy, true)) {
                return ParseResult::ok(false, $value);
            }
            return ParseResult::fail("String '{$value}' is not a valid boolean representation.", $value);
        }

        return ParseResult::fail("Type " . gettype($value) . " cannot be parsed as boolean.", $value);
    }
}
