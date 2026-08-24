<?php
declare(strict_types=1);

use Nexus\Foundation\Application;
use Nexus\Foundation\Config;
use Nexus\Http\Middleware\CorsMiddleware;
use Nexus\Http\Request;
use Nexus\Http\Response;

use PHPUnit\Framework\TestCase;

class CorsTest extends TestCase
{
    public function testDefaultCorsHeadersAreApplied(): void
    {
        $response = $this->runThroughMiddleware();

        if ($response->getHeader('Access-Control-Allow-Origin') !== '*') {
            throw new \RuntimeException("Default Access-Control-Allow-Origin header missing or incorrect.");
        }

        if ($response->getHeader('Access-Control-Allow-Methods') !== 'GET, POST, PUT, PATCH, DELETE, OPTIONS') {
            throw new \RuntimeException("Default Access-Control-Allow-Methods header missing or incorrect.");
        }

        if ($response->getHeader('Access-Control-Allow-Headers') !== 'Content-Type, Authorization, X-Requested-With') {
            throw new \RuntimeException("Default Access-Control-Allow-Headers header missing or incorrect.");
        }
    }

    public function testCorsOptionsCanBeOverriddenViaConfig(): void
    {
        $this->withConfig(['cors' => [
            'allowed_origins' => ['https://app.example.com'],
            'allowed_methods' => ['GET', 'POST'],
            'allowed_headers' => ['Content-Type', 'X-Custom-Header'],
            'exposed_headers' => ['X-Trace-Id'],
            'supports_credentials' => true,
            'max_age' => 86400,
        ]], function (): void {
            $request = new Request('GET', '/', ['Origin' => 'https://app.example.com'], [], [], [], [], '');
            $response = $this->runThroughMiddleware($request);

            if ($response->getHeader('Access-Control-Allow-Origin') !== 'https://app.example.com') {
                throw new \RuntimeException("Allowed origin config override failed.");
            }

            if ($response->getHeader('Access-Control-Allow-Methods') !== 'GET, POST') {
                throw new \RuntimeException("Allowed methods config override failed.");
            }

            if ($response->getHeader('Access-Control-Allow-Headers') !== 'Content-Type, X-Custom-Header') {
                throw new \RuntimeException("Allowed headers config override failed.");
            }

            if ($response->getHeader('Access-Control-Expose-Headers') !== 'X-Trace-Id') {
                throw new \RuntimeException("Exposed headers config override failed.");
            }

            if ($response->getHeader('Access-Control-Allow-Credentials') !== 'true') {
                throw new \RuntimeException("Credentials config override failed.");
            }

            if ($response->getHeader('Access-Control-Max-Age') !== '86400') {
                throw new \RuntimeException("Max age config override failed.");
            }
        });
    }

    public function testWildcardWithCredentialsReturnsNullOrigin(): void
    {
        $this->withConfig(['cors' => [
            'allowed_origins' => ['*'],
            'supports_credentials' => true,
        ]], function (): void {
            $request = new Request('GET', '/', ['Origin' => 'https://malicious.example.com'], [], [], [], [], '');
            $response = $this->runThroughMiddleware($request);

            if ($response->getHeader('Access-Control-Allow-Origin') !== 'null') {
                throw new \RuntimeException("Wildcard CORS with credentials must return 'null' for origin.");
            }
        });
    }

    public function testOptionsPreflightResponse(): void
    {
        $request = new Request('OPTIONS', '/', [], [], [], [], [], '');
        $response = $this->runThroughMiddleware($request);

        if ($response->getStatusCode() !== 204) {
            throw new \RuntimeException("Preflight response code should be 204.");
        }
    }

    protected function runThroughMiddleware(?Request $request = null): Response
    {
        $middleware = new CorsMiddleware();
        $request = $request ?? new Request('GET', '/', [], [], [], [], [], '');

        return $middleware->handle($request, fn () => new Response('ok'));
    }

    protected function withConfig(array $items, callable $callback): void
    {
        $config = Application::getInstance()->make(Config::class);
        $original = $config->get('cors');

        foreach ($items as $key => $value) {
            $config->set($key, $value);
        }

        try {
            $callback();
        } finally {
            if ($original === null) {
                $config->set('cors', []);
            } else {
                $config->set('cors', $original);
            }
        }
    }
}
