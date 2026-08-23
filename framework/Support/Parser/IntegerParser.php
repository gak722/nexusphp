<?php
declare(strict_types=1);

namespace Nexus\Support\Parser;

use Nexus\Support\Result\ParseResult;

class IntegerParser implements ParserInterface
{
    public function parse(mixed $value): ParseResult
    {
        if (is_int($value)) {
            return ParseResult::ok($value, $value);
        }

        if (is_float($value)) {
            if (floor($value) === $value && $value >= PHP_INT_MIN && $value <= PHP_INT_MAX) {
                return ParseResult::ok((int) $value, $value);
            }
            return ParseResult::fail("Float {$value} has decimal fraction or exceeds integer bounds.", $value);
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if (filter_var($trimmed, FILTER_VALIDATE_INT) !== false) {
                return ParseResult::ok((int) $trimmed, $value);
            }
            return ParseResult::fail("String '{$value}' is not a valid integer.", $value);
        }

        return ParseResult::fail("Cannot parse " . gettype($value) . " as integer.", $value);
    }
}
