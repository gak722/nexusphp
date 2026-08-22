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
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'no-referrer-when-downgrade',
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

    /**
     * Merge configured security headers over the framework defaults.
     *
     * @return array<string, string|array|null>
     */
    protected function resolveHeaders(): array
    {
        return array_merge(
            static::DEFAULT_HEADERS,
            (array) $this->config()->get('security.headers', [])
        );
    }

    /**
     * Resolve the shared Config repository from the container.
     *
     * Falls back to an empty Config when the framework is used standalone
     * (e.g. in tests without a full bootstrap), which yields the defaults.
     */
    protected function config(): Config
    {
        $app = Application::getInstance();

        if ($app->has(Config::class)) {
            $config = $app->make(Config::class);

            if ($config instanceof Config) {
                return $config;
            }
        }

        return new Config();
    }

    /**
     * Compile a header value to its string form.
     *
     * Content-Security-Policy may be configured as an array of directives:
     * ['default-src' => "'self'", 'img-src' => ["'self'", 'data:']]
     * which compiles to: default-src 'self'; img-src 'self' data:
     */
    protected function compileValue(string|array $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        $directives = [];

        foreach ($value as $directive => $sources) {
            if (!is_string($directive)) {
                $directives[] = is_array($sources) ? implode(' ', $sources) : (string) $sources;
                continue;
            }

            $sourceList = is_array($sources) ? implode(' ', $sources) : (string) $sources;
            $directives[] = trim($directive . ' ' . $sourceList);
        }

        return implode('; ', $directives);
    }
}
