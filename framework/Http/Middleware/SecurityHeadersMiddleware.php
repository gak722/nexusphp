<?php
declare(strict_types=1);

namespace Nexus\Http\Middleware;

use Nexus\Foundation\Application;
use Nexus\Foundation\Config;
use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\Response;

/**
 * Security Headers Middleware (OWASP recommended headers)
 *
 * Header values are read from the application configuration under
 * "security.headers" (config/security.php) so they can be customized
 * without modifying framework core. Framework defaults are merged in,
 * so a partial override only needs to list the headers being changed.
 *
 * Setting a header's value to null prevents it from being sent.
 */
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /**
     * Default OWASP recommended headers, used when not overridden by config.
     */
    protected const DEFAULT_HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        'Content-Security-Policy' => "default-src 'self'",
    ];

    public function handle(Request $request, \Closure $next): Response
    {
        $response = $next($request);

        foreach ($this->resolveHeaders() as $header => $value) {
            // A null value explicitly disables the header.
            if ($value === null || $value === '') {
                continue;
            }

            $response->setHeader($header, $this->compileValue($value));
        }

        return $response;
    }
}
