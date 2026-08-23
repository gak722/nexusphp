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
        $cleanName = preg_replace('/[\r\n]/', '', $name);
        $cleanValue = preg_replace('/[\r\n]/', '', $value);
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
        $headerVal = sprintf(
            '%s=%s; path=%s%s%s%s; SameSite=%s',
            rawurlencode($name),
            rawurlencode($value),
            $path,
            $expire > 0 ? '; expires=' . gmdate('D, d M Y H:i:s T', $expire) : '',
            $domain !== '' ? '; domain=' . $domain : '',
            $secure ? '; secure' : '',
            $httponly ? '; HttpOnly' : '',
            $samesite
        );

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
