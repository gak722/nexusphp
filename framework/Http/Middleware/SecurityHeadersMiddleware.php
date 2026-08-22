<?php
declare(strict_types=1);

namespace Nexus\Http\Middleware;

use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\Response;

/**
 * Security Headers Middleware (OWASP recommended headers)
 */
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): Response
    {
        $response = $next($request);

        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('X-XSS-Protection', '1; mode=block');
        $response->setHeader('Referrer-Policy', 'no-referrer-when-downgrade');
        $response->setHeader('Content-Security-Policy', "default-src 'self'");

        return $response;
    }
}
