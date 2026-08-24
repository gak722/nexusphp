<?php
declare(strict_types=1);

use Nexus\Foundation\Application;
use Nexus\Foundation\Config;
use Nexus\Http\Middleware\SecurityHeadersMiddleware;
use Nexus\Http\Request;
use Nexus\Http\Response;

use PHPUnit\Framework\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function testDefaultSecurityHeadersAreApplied(): void
    {
        $response = $this->runThroughMiddleware();

        if ($response->getHeader('Content-Security-Policy') !== "default-src 'self'") {
            throw new \RuntimeException("Default CSP header missing or incorrect.");
        }

        if ($response->getHeader('X-Frame-Options') !== 'DENY') {
            throw new \RuntimeException("Default X-Frame-Options header missing or incorrect.");
        }
    }

    public function testCspCanBeOverriddenViaConfig(): void
    {
        $this->withConfig(['security' => ['headers' => [
            'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'none'",
        ]]], function (): void {
            $response = $this->runThroughMiddleware();

            if ($response->getHeader('Content-Security-Policy') !== "default-src 'none'; frame-ancestors 'none'") {
                throw new \RuntimeException("CSP override via config failed.");
            }

            // Non-overridden defaults must still be merged in.
            if ($response->getHeader('X-Content-Type-Options') !== 'nosniff') {
                throw new \RuntimeException("Partial config override dropped default headers.");
            }
        });
    }

    public function testCspSupportsDirectiveArrayForm(): void
    {
        $this->withConfig(['security' => ['headers' => [
            'Content-Security-Policy' => [
                'default-src' => "'self'",
                'script-src' => ["'self'", 'cdn.example.com'],
                'img-src' => ["'self'", 'data:'],
            ],
        ]]], function (): void {
            $response = $this->runThroughMiddleware();

            $expected = "default-src 'self'; script-src 'self' cdn.example.com; img-src 'self' data:";
            if ($response->getHeader('Content-Security-Policy') !== $expected) {
                throw new \RuntimeException("CSP directive array compilation failed.");
            }
        });
    }

    public function testNullHeaderValueDisablesHeader(): void
    {
        $this->withConfig(['security' => ['headers' => [
            'X-XSS-Protection' => null,
        ]]], function (): void {
            $response = $this->runThroughMiddleware();

            if ($response->getHeader('X-XSS-Protection') !== null) {
                throw new \RuntimeException("Null config value should disable the header.");
            }
        });
    }

    public function testConfigRepositoryIsSharedAndLoadedFromBootstrap(): void
    {
        $app = Application::getInstance();
        $config = $app->make(Config::class);

        if (!$app->has(Config::class)) {
            throw new \RuntimeException("Config is not bound in the container.");
        }

        if ($app->make(Config::class) !== $config) {
            throw new \RuntimeException("Config should resolve as a shared singleton.");
        }

        // bootstrap/app.php loads config/security.php into the repository.
        if (!is_array($config->get('security.headers'))) {
            throw new \RuntimeException("config/security.php was not loaded into the Config repository.");
        }
    }

    /**
     * Run a request through the security headers middleware.
     */
    protected function runThroughMiddleware(): Response
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = new Request('GET', '/', [], [], [], [], [], '');

        return $middleware->handle($request, fn () => new Response('ok'));
    }

    /**
     * Temporarily apply config values, run the callback, then restore state.
     */
    protected function withConfig(array $items, callable $callback): void
    {
        $config = Application::getInstance()->make(Config::class);
        $original = $config->get('security');
        $merged = array_replace_recursive(is_array($original) ? $original : [], $items);

        foreach ($items as $key => $value) {
            $config->set($key, $value);
        }

        try {
            $callback();
        } finally {
            if ($original === null) {
                $config->set('security', []);
            } else {
                $config->set('security', $original);
            }
        }
    }
}
