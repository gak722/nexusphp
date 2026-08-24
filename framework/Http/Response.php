<?php
declare(strict_types=1);

namespace Nexus\Http;

/**
 * Standard HTTP Response
 */
class Response
{
    protected bool $sent = false;

    public function __construct(
        protected string $content = '',
        protected int $statusCode = 200,
        protected array $headers = []
    ) {}

    public function send(): void
    {
        if ($this->sent) {
            return;
        }
        $this->sent = true;

        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                if (is_array($value)) {
                    foreach ($value as $val) {
                        header("{$name}: {$val}", false);
                    }
                } else {
                    header("{$name}: {$value}");
                }
            }
        }
        echo $this->content;
    }

    public function isSent(): bool
    {
        return $this->sent;
    }

    public function setHeader(string $name, string $value): static
    {
        return $this->header($name, $value);
    }

    public function header(string $name, string $value): static
    {
        // Validate header name per RFC token (simplified)
        $cleanName = trim($name);
        if (!preg_match('/^[A-Za-z0-9\-]+$/', $cleanName)) {
            throw new \InvalidArgumentException('Invalid header name');
        }

        // Reject CR or LF anywhere in header value
        if (preg_match('/[\r\n]/', $value)) {
            throw new \InvalidArgumentException('Invalid header value');
        }

        // Normalize whitespace and enforce length limit
        $cleanValue = preg_replace('/[ \t]+/', ' ', trim($value));
        $max = (int) (getenv('HTTP_HEADER_MAX_LENGTH') ?: 4096);
        if (strlen($cleanValue) > $max) {
            throw new \InvalidArgumentException('Header value too long');
        }

        $this->headers[$cleanName] = $cleanValue;
        return $this;
    }

    public function withCookie(
        string $name,
        string $value = '',
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = true,
        string $samesite = 'Lax'
    ): static {
        // Sanitize cookie attributes to avoid header injection
        if (preg_match('/[\r\n=;\s]/', $name) || preg_match('/[\r\n]/', $value)) {
            throw new \InvalidArgumentException('Invalid cookie name or value');
        }
        $parts = [];
        $parts[] = rawurlencode($name) . '=' . rawurlencode($value);
        $parts[] = 'path=' . ($path === '' ? '/' : $path);
        if ($expire > 0) {
            $parts[] = 'expires=' . gmdate('D, d M Y H:i:s T', $expire);
        }
        if ($domain !== '') {
            $parts[] = 'domain=' . $domain;
        }
        if ($secure) {
            $parts[] = 'secure';
        }
        if ($httponly) {
            $parts[] = 'HttpOnly';
        }
        $parts[] = 'SameSite=' . $samesite;
        $headerVal = implode('; ', $parts);

        if (!isset($this->headers['Set-Cookie'])) {
            $this->headers['Set-Cookie'] = [];
        } elseif (is_string($this->headers['Set-Cookie'])) {
            $this->headers['Set-Cookie'] = [$this->headers['Set-Cookie']];
        }

        $this->headers['Set-Cookie'][] = $headerVal;
        return $this;
    }

    public function status(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    public function content(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        $nameLower = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $nameLower) {
                return is_array($value) ? implode('; ', $value) : $value;
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
