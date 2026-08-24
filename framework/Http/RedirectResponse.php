<?php
declare(strict_types=1);

namespace Nexus\Http;

/**
 * HTTP Redirect Response Subclass with Open-Redirect Protection
 */
class RedirectResponse extends Response
{
    public function __construct(string $url, int $status = 302, array $headers = [], bool $allowExternal = false)
    {
        $safeUrl = $this->sanitizeUrl($url, $allowExternal);
        $headers['Location'] = $safeUrl;
        parent::__construct('', $status, $headers);
    }

    public static function away(string $url, int $status = 302, array $headers = []): static
    {
        return new static($url, $status, $headers, allowExternal: true);
    }

    protected function sanitizeUrl(string $url, bool $allowExternal = false): string
    {
        $url = trim($url);

        // Disallow scheme-relative protocol links (e.g. //evil.com) and javascript pseudo-protocols
        if (str_starts_with($url, '//') || preg_match('/^(javascript|data|vbscript):/i', $url)) {
            return '/';
        }

        if (!$allowExternal && preg_match('#^https?://#i', $url)) {
            $parsed = parse_url($url);
            $appHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? null;
            if ($appHost && isset($parsed['host'])) {
                $hostWithPort = explode(':', $appHost)[0];
                if (strtolower($parsed['host']) !== strtolower($hostWithPort)) {
                    return '/';
                }
            } else {
                return '/';
            }
        }

        return $url;
    }
}
