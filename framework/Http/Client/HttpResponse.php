<?php
declare(strict_types=1);

namespace Nexus\Http\Client;

use Nexus\Http\Client\Exceptions\HttpRequestException;

/**
 * HTTP Response abstraction for external client requests.
 */
class HttpResponse
{
    protected ?array $decodedJson = null;

    public function __construct(
        protected int $statusCode,
        protected array $headers = [],
        protected string $body = ''
    ) {}

    public function status(): int
    {
        return $this->statusCode;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function ok(): bool
    {
        return $this->statusCode === 200;
    }

    public function successful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function redirect(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    public function failed(): bool
    {
        return $this->serverError() || $this->clientError();
    }

    public function clientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    public function serverError(): bool
    {
        return $this->statusCode >= 500;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $nameLower = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower((string) $key) === $nameLower) {
                return is_array($value) ? implode('; ', $value) : (string) $value;
            }
        }
        return $default;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function text(): string
    {
        return $this->body;
    }

    /**
     * Decode JSON body into an associative array or access nested data via dot notation.
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        if ($this->decodedJson === null) {
            $decoded = json_decode($this->body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->decodedJson = [];
            } else {
                $this->decodedJson = is_array($decoded) ? $decoded : [];
            }
        }

        if ($key === null) {
            return $this->decodedJson;
        }

        return \Nexus\Support\Arr::get($this->decodedJson, $key, $default);
    }

    /**
     * Throw an exception if the response represents a client or server error.
     */
    public function throw(): static
    {
        if ($this->failed()) {
            throw new HttpRequestException("HTTP request failed with status {$this->statusCode}", $this);
        }

        return $this;
    }
}
