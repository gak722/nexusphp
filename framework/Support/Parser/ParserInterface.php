<?php
declare(strict_types=1);

namespace Nexus\Support\Parser;

use Nexus\Support\Result\ParseResult;

interface ParserInterface
{
    /**
     * @param mixed $value
     * @return ParseResult<mixed>
     */
    public function parse(mixed $value): ParseResult;
}
