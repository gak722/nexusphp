<?php
declare(strict_types=1);

namespace Nexus\Http\Middleware;

use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\Response;

/**
 * Cross-Origin Resource Sharing Middleware
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): Response
    {
        if ($request->method === 'OPTIONS') {
            $response = new Response('', 204);
        } else {
            $response = $next($request);
        }

        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        return $response;
    }
}
