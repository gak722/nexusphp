<?php
declare(strict_types=1);

namespace Nexus\Http;

/**
 * Immutable HTTP Request Representation
 */
class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly array $headers,
        public readonly array $query,
        public readonly array $post,
        public readonly array $files,
        public readonly array $cookies,
        public readonly string $rawBody
    ) {}

    public static function createFromGlobals(): static
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$headerName] = (string)$value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                $headers[$headerName] = (string)$value;
            }
        }

        $rawBody = file_get_contents('php://input') ?: '';

        return new static(
            strtoupper($method),
            $uri,
            $headers,
            $_GET,
            $_POST,
            $_FILES,
            $_COOKIE,
            $rawBody
        );
    }

    public function header(string $key, ?string $default = null): ?string
    {
        $key = strtolower($key);
        foreach ($this->headers as $k => $v) {
            if (strtolower($k) === $key) {
                return $v;
            }
        }
        return $default;
    }

    public function isJson(): bool
    {
        return str_contains($this->header('Content-Type', ''), 'application/json');
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        $data = json_decode($this->rawBody, true);
        if (!is_array($data)) {
            $data = [];
        }

        if ($key === null) {
            return $data;
        }

        return \Nexus\Support\Arr::get($data, $key, $default);
    }
}
