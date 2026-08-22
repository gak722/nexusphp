<?php
declare(strict_types=1);

namespace Nexus\Http;

/**
 * HTTP Redirect Response Subclass with Open-Redirect Protection
 */
class RedirectResponse extends Response
{
    public function __construct(string $url, int $status = 302, array $headers = [])
    {
        $safeUrl = $this->sanitizeUrl($url);
        $headers['Location'] = $safeUrl;
        parent::__construct('', $status, $headers);
    }

    protected function sanitizeUrl(string $url): string
    {
        $url = trim($url);

        // Disallow scheme-relative protocol links (e.g. //evil.com) and javascript pseudo-protocols
        if (str_starts_with($url, '//') || preg_match('/^(javascript|data|vbscript):/i', $url)) {
            return '/';
        }

        return $url;
    }
}
