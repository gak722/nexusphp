<?php
declare(strict_types=1);

namespace Nexus\Http\Middleware;

use Nexus\Foundation\Application;
use Nexus\Foundation\Config;
use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\Response;

/**
 * Cross-Origin Resource Sharing (CORS) Middleware
 *
 * Reads CORS headers and behavior from application configuration (`cors.php`).
 * Provides sensible defaults if configuration is not loaded or missing.
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * Framework default CORS settings.
     */
    protected const DEFAULT_CONFIG = [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'exposed_headers' => [],
        'max_age' => 0,
        'supports_credentials' => false,
    ];

    public function handle(Request $request, \Closure $next): Response
    {
        $corsConfig = $this->resolveConfig();

        if ($request->method === 'OPTIONS') {
            $response = new Response('', 204);
        } else {
            $response = $next($request);
        }

        $origin = $request->header('Origin') ?? '*';
        $allowedOrigins = $corsConfig['allowed_origins'] ?? ['*'];

        if (is_string($allowedOrigins)) {
            $allowedOrigins = array_map('trim', explode(',', $allowedOrigins));
        }

        $supportsCredentials = !empty($corsConfig['supports_credentials']);

        if (in_array('*', $allowedOrigins, true)) {
            if ($supportsCredentials && $origin !== '*') {
                $response->setHeader('Access-Control-Allow-Origin', $origin);
                $response->setHeader('Vary', 'Origin');
            } else {
                $response->setHeader('Access-Control-Allow-Origin', '*');
            }
        } elseif (is_array($allowedOrigins) && in_array($origin, $allowedOrigins, true)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->setHeader('Vary', 'Origin');
        }

        $allowedMethods = $corsConfig['allowed_methods'] ?? [];
        if (is_array($allowedMethods)) {
            $allowedMethods = implode(', ', $allowedMethods);
        }
        if (!empty($allowedMethods)) {
            $response->setHeader('Access-Control-Allow-Methods', $allowedMethods);
        }

        $allowedHeaders = $corsConfig['allowed_headers'] ?? [];
        if (is_array($allowedHeaders)) {
            $allowedHeaders = implode(', ', $allowedHeaders);
        }
        if (!empty($allowedHeaders)) {
            $response->setHeader('Access-Control-Allow-Headers', $allowedHeaders);
        }

        $exposedHeaders = $corsConfig['exposed_headers'] ?? [];
        if (is_array($exposedHeaders)) {
            $exposedHeaders = implode(', ', $exposedHeaders);
        }
        if (!empty($exposedHeaders)) {
            $response->setHeader('Access-Control-Expose-Headers', $exposedHeaders);
        }

        if ($supportsCredentials) {
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }

        if (!empty($corsConfig['max_age'])) {
            $response->setHeader('Access-Control-Max-Age', (string) $corsConfig['max_age']);
        }

        return $response;
    }

    /**
     * Resolve CORS settings merged over framework defaults.
     *
     * @return array<string, mixed>
     */
    protected function resolveConfig(): array
    {
        $userConfig = $this->config()->get('cors', []);
        return array_merge(static::DEFAULT_CONFIG, is_array($userConfig) ? $userConfig : []);
    }

    /**
     * Resolve the shared Config repository from the container.
     */
    protected function config(): Config
    {
        try {
            $app = Application::getInstance();

            if ($app->has(Config::class)) {
                $config = $app->make(Config::class);

                if ($config instanceof Config) {
                    return $config;
                }
            }
        } catch (\Throwable $e) {
            // Fallback to standalone default Config instance
        }

        return new Config();
    }
}
