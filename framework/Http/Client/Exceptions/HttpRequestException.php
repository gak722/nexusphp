<?php
declare(strict_types=1);

namespace Nexus\Http\Client\Exceptions;

class HttpRequestException extends HttpException
{
    public function __construct(
        string $message,
        protected readonly \Nexus\Http\Client\HttpResponse $response
    ) {
        parent::__construct($message, $response->status());
    }

    public function getResponse(): \Nexus\Http\Client\HttpResponse
    {
        return $this->response;
    }
}
