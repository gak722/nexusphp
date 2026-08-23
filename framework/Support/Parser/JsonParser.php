<?php
declare(strict_types=1);

namespace Nexus\Support\Parser;

use Nexus\Support\Result\ParseResult;

class JsonParser implements ParserInterface
{
    public function parse(mixed $value): ParseResult
    {
        if (!is_string($value)) {
            return ParseResult::fail("Input must be a string for JSON parsing.", $value);
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return ParseResult::fail("Empty string is not valid JSON.", $value);
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            return ParseResult::ok($decoded, $value);
        } catch (\JsonException $e) {
            return ParseResult::fail("JSON decode error: " . $e->getMessage(), $value);
        }
    }
}
