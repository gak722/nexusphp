<?php
declare(strict_types=1);

namespace Nexus\Http\Client;

class HttpRequest
{
    public function __construct(
        public string $method,
        public string $url,
        public array $headers = [],
        public mixed $body = null,
        public array $options = []
    ) {}
}
