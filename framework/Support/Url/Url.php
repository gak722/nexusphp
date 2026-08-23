<?php
declare(strict_types=1);

namespace Nexus\Support\Url;

class Url
{
    protected array $parts = [];

    public function __construct(string $url)
    {
        $parsed = parse_url($url);
        if ($parsed === false) {
            throw new \InvalidArgumentException("Malformed URL: '{$url}'");
        }
        $this->parts = $parsed;
    }

    public static function parse(string $url): static
    {
        return new static($url);
    }

    public function scheme(): ?string
    {
        return $this->parts['scheme'] ?? null;
    }

    public function host(): ?string
    {
        return $this->parts['host'] ?? null;
    }

    public function port(): ?int
    {
        return isset($this->parts['port']) ? (int) $this->parts['port'] : null;
    }

    public function path(): string
    {
        return $this->parts['path'] ?? '/';
    }

    public function query(): array
    {
        if (!isset($this->parts['query'])) {
            return [];
        }
        parse_str($this->parts['query'], $query);
        return $query;
    }

    public function withQuery(array $params): static
    {
        $clone = clone $this;
        $clone->parts['query'] = http_build_query($params);
        return $clone;
    }

    public function addQuery(array $params): static
    {
        $existing = $this->query();
        return $this->withQuery(array_merge($existing, $params));
    }

    public function removeQuery(string|array $keys): static
    {
        $existing = $this->query();
        $keys = (array) $keys;
        foreach ($keys as $k) {
            unset($existing[$k]);
        }
        return $this->withQuery($existing);
    }

    public function isSafe(): bool
    {
        $scheme = strtolower($this->scheme() ?? '');
        return in_array($scheme, ['http', 'https'], true);
    }

    public function toString(): string
    {
        $scheme = isset($this->parts['scheme']) ? $this->parts['scheme'] . '://' : '';
        $user = $this->parts['user'] ?? '';
        $pass = isset($this->parts['pass']) ? ':' . $this->parts['pass'] : '';
        $auth = ($user || $pass) ? "$user$pass@" : '';
        $host = $this->parts['host'] ?? '';
        $port = isset($this->parts['port']) ? ':' . $this->parts['port'] : '';
        $path = $this->parts['path'] ?? '';
        $query = isset($this->parts['query']) && $this->parts['query'] !== '' ? '?' . $this->parts['query'] : '';
        $fragment = isset($this->parts['fragment']) ? '#' . $this->parts['fragment'] : '';

        return "{$scheme}{$auth}{$host}{$port}{$path}{$query}{$fragment}";
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
