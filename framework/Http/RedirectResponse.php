<?php
declare(strict_types=1);

namespace Nexus\Http;

/**
 * HTTP Redirect Response Subclass
 */
class RedirectResponse extends Response
{
    public function __construct(string $url, int $status = 302, array $headers = [])
    {
        $headers['Location'] = $url;
        parent::__construct('', $status, $headers);
    }
}
