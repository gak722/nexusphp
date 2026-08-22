<?php
declare(strict_types=1);

namespace Nexus\Http;

use Nexus\Validation\Validator;

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

    public function host(): string
    {
        $host = $this->header('Host') ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        // Strip port number if present
        $host = explode(':', $host)[0];
        // Validate host format against Host Header Injection
        return preg_match('/^[a-zA-Z0-9\.\-]+$/', $host) ? $host : 'localhost';
    }

    public function header(string $key, ?string $default = null): ?string
    {
        $key = strtolower($key);
        foreach ($this->headers as $k => $v) {
            if (strtolower($k) === $key) {
                // Reject duplicate content-length/transfer-encoding smuggling attempts
                if (is_array($v)) {
                    $v = $v[0];
                }
                return (string) $v;
            }
        }
        return $default;
    }

    public function validateFiles(array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'doc', 'docx']): bool
    {
        foreach ($this->files as $file) {
            if (!isset($file['name']) || empty($file['name'])) {
                continue;
            }
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'cgi'], true) || !in_array($ext, $allowedExtensions, true)) {
                return false;
            }
        }
        return true;
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

    public function validate(array $rules): array
    {
        $inputData = array_merge($this->query, $this->post, $this->json() ?: []);
        return Validator::make($inputData, $rules)->validate();
    }
}
