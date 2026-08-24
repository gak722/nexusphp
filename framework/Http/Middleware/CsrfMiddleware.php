<?php
declare(strict_types=1);

namespace Nexus\Http\Middleware;

use Nexus\Http\JsonResponse;
use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Security\Csrf;

/**
 * CSRF Protection Middleware
 */
class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * URIs that should be excluded from CSRF verification (e.g. webhook routes, API endpoints)
     *
     * @var array<string>
     */
    protected array $except = [
        'api/*',
    ];

    public function handle(Request $request, \Closure $next): Response
    {
        if ($this->isReading($request) || $this->inExceptArray($request) || Csrf::validate($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return new JsonResponse(['message' => 'CSRF token mismatch.'], 419);
        }

        return new Response('419 CSRF Token Mismatch', 419, ['Content-Type' => 'text/plain']);
    }

    protected function isReading(Request $request): bool
    {
        return in_array(strtoupper($request->method), ['GET', 'HEAD', 'OPTIONS'], true);
    }

    protected function inExceptArray(Request $request): bool
    {
        $uri = trim($request->uri, '/');
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }
            if ($except === $uri) {
                return true;
            }
            if (str_ends_with($except, '*')) {
                $prefix = rtrim($except, '*');
                if (str_starts_with($uri, $prefix)) {
                    return true;
                }
            }
        }
        return false;
    }
}
