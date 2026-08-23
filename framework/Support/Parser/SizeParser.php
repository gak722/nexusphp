<?php
declare(strict_types=1);

namespace Nexus\Support\Parser;

use Nexus\Support\Result\ParseResult;

class SizeParser implements ParserInterface
{
    protected static array $units = [
        'B' => 1,
        'KB' => 1024,
        'MB' => 1048576,
        'GB' => 1073741824,
        'TB' => 1099511627776,
        'PB' => 1125899906842624,
    ];

    public function parse(mixed $value): ParseResult
    {
        if (is_int($value) || is_float($value)) {
            return ParseResult::ok((int) $value, $value);
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if (preg_match('/^(\d+(?:\.\d+)?)\s*([a-zA-Z]+)?$/', $trimmed, $matches)) {
                $num = (float) $matches[1];
                $unit = strtoupper($matches[2] ?? 'B');
                if (isset(static::$units[$unit])) {
                    return ParseResult::ok((int) round($num * static::$units[$unit]), $value);
                }
                return ParseResult::fail("Unknown size unit '{$unit}' in '{$value}'.", $value);
            }
            return ParseResult::fail("String '{$value}' is not a valid human size format.", $value);
        }

        return ParseResult::fail("Cannot parse size from " . gettype($value) . ".", $value);
    }
}
