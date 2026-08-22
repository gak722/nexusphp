<?php
declare(strict_types=1);

namespace Nexus\Http;

/**
 * Standard HTTP Response
 */
class Response
{
    public function __construct(
        protected string $content = '',
        protected int $statusCode = 200,
        protected array $headers = []
    ) {}

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }
        echo $this->content;
    }

    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        $nameLower = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $nameLower) {
                return $value;
            }
        }
        return $default;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
