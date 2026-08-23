<?php
declare(strict_types=1);

namespace Nexus\Http\Client\Exceptions;

class HttpException extends \RuntimeException
{
    protected ?array $responseBody = null;
    protected ?int $statusCode = null;

    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
