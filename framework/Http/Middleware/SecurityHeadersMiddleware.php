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

        // Load custom security headers from config or use defaults
        $headers = config('security.headers', [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'no-referrer-when-downgrade',
        ]);

        foreach ($headers as $header => $value) {
            $response->setHeader($header, $value);
        }

        // Build Content-Security-Policy from config
        if (config('security.csp.enabled', true)) {
            $directives = config('security.csp.directives', [
                'default-src' => ["'self'"],
            ]);

            $cspString = [];
            foreach ($directives as $directive => $sources) {
                if (is_array($sources)) {
                    $cspString[] = $directive . ' ' . implode(' ', $sources);
                } elseif (is_string($sources)) {
                    $cspString[] = $directive . ' ' . $sources;
                }
            }

            if (!empty($cspString)) {
                $response->setHeader('Content-Security-Policy', implode('; ', $cspString) . ';');
            }
        }

        return $response;
    }
}


