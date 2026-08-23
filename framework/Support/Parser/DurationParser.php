<?php
declare(strict_types=1);

namespace Nexus\Support\Parser;

use Nexus\Support\Result\ParseResult;

class DurationParser implements ParserInterface
{
    public function parse(mixed $value): ParseResult
    {
        if (is_int($value) || is_float($value)) {
            return ParseResult::ok((int) $value, $value); // Seconds
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if (preg_match_all('/(\d+(?:\.\d+)?)\s*([a-zA-Z]+)/', $trimmed, $matches, PREG_SET_ORDER)) {
                $totalSeconds = 0.0;
                foreach ($matches as $match) {
                    $amount = (float) $match[1];
                    $unit = strtolower($match[2]);
                    $sec = match ($unit) {
                        's', 'sec', 'secs', 'second', 'seconds' => $amount,
                        'm', 'min', 'mins', 'minute', 'minutes' => $amount * 60,
                        'h', 'hr', 'hrs', 'hour', 'hours' => $amount * 3600,
                        'd', 'day', 'days' => $amount * 86400,
                        'w', 'wk', 'wks', 'week', 'weeks' => $amount * 604800,
                        default => null,
                    };

                    if ($sec === null) {
                        return ParseResult::fail("Unknown duration unit '{$unit}' in '{$value}'.", $value);
                    }
                    $totalSeconds += $sec;
                }
                return ParseResult::ok((int) round($totalSeconds), $value);
            }
            return ParseResult::fail("String '{$value}' is not a valid duration format.", $value);
        }

        return ParseResult::fail("Cannot parse duration from " . gettype($value) . ".", $value);
    }
}
