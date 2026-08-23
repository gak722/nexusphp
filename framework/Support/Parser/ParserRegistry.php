<?php
declare(strict_types=1);

namespace Nexus\Support\Parser;

use Nexus\Support\Result\ParseResult;

class ParserRegistry
{
    /** @var array<string, ParserInterface> */
    protected static array $parsers = [];

    public static function register(string $key, ParserInterface $parser): void
    {
        static::$parsers[$key] = $parser;
    }

    public static function get(string $key): ?ParserInterface
    {
        return static::$parsers[$key] ?? match ($key) {
            'bool', 'boolean' => new BooleanParser(),
            'int', 'integer' => new IntegerParser(),
            'float' => new FloatParser(),
            'size' => new SizeParser(),
            'duration' => new DurationParser(),
            'json' => new JsonParser(),
            default => null,
        };
    }
}
