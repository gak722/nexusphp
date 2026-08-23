<?php
declare(strict_types=1);

namespace Nexus\Support\Parser;

use Nexus\Support\Result\ParseResult;

class FloatParser implements ParserInterface
{
    public function parse(mixed $value): ParseResult
    {
        if (is_float($value)) {
            return ParseResult::ok($value, $value);
        }

        if (is_int($value)) {
            return ParseResult::ok((float) $value, $value);
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if (filter_var($trimmed, FILTER_VALIDATE_FLOAT) !== false) {
                return ParseResult::ok((float) $trimmed, $value);
            }
            return ParseResult::fail("String '{$value}' is not a valid float.", $value);
        }

        return ParseResult::fail("Cannot parse " . gettype($value) . " as float.", $value);
    }
}
