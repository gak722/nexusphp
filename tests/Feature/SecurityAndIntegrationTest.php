<?php
declare(strict_types=1);

namespace Nexus\Tests\Feature;

use Nexus\Cache\FileCache;
use Nexus\Cache\RedisCache;
use Nexus\Http\Middleware\SecurityHeadersMiddleware;
use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Queue\Job;
use Nexus\Queue\QueueInterface;
use Nexus\Queue\RedisQueue;
use Nexus\Queue\Worker;
use Nexus\Security\Jwt;
use PHPUnit\Framework\TestCase;

class TestQueueJob extends Job
{
    public string $payloadData = '';

    public function handle(): void
    {
        if ($this->payloadData === 'fail') {
            throw new \RuntimeException('Processing failed');
        }
    }
}

class SecurityAndIntegrationTest extends TestCase
{
    /**
     * SCA & Code Security Scan: Ensures no dangerous functions exist in framework codebase
     */
    public function testStaticCodeAnalysisAndSca(): void
    {
        $frameworkDir = dirname(__DIR__, 2) . '/framework';
        $directory = new \RecursiveDirectoryIterator($frameworkDir);
        $iterator = new \RecursiveIteratorIterator($directory);
        $regex = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        $forbiddenPatterns = [
            '/(?<!->)\beval\s*\(/i' => 'eval() usage detected',
            '/\bexec\s*\(/i' => 'exec() usage detected',
            '/\bshell_exec\s*\(/i' => 'shell_exec() usage detected',
            '/\bpassthru\s*\(/i' => 'passthru() usage detected',
            '/\bsystem\s*\(/i' => 'system() usage detected',
            '/\bproc_open\s*\(/i' => 'proc_open() usage detected',
        ];

        $scannedCount = 0;

        foreach ($regex as $file) {
            $path = $file[0];
            $scannedCount++;
            $content = file_get_contents($path);

            // Exclude CLI ServeCommand and Schedule which legitimately use exec/passthru for CLI dev server and task scheduling
            if (str_contains($path, 'ServeCommand.php') || str_contains($path, 'Schedule.php')) {
                continue;
            }

            foreach ($forbiddenPatterns as $pattern => $message) {
                $this->assertSame(0, preg_match($pattern, $content), "{$message} in file {$path}");
            }

            // Ensure unserialize only occurs with allowed_classes = false
            if (preg_match_all('/\bunserialize\s*\(([^)]+)\)/i', $content, $matches)) {
                foreach ($matches[1] as $argString) {
                    $this->assertStringContainsString('allowed_classes', $argString, "Unsafe unserialize() without allowed_classes parameter in {$path}");
                }
            }
        }

        $this->assertGreaterThan(10, $scannedCount, "Framework PHP files scanned");
    }

    /**
     * JWT Encoding, Decoding, Claims, and Leeway
     */
    public function testJwtEncodingDecodingAndLeeway(): void
    {
        $secret = 'super-secret-key-12345';
        $payload = [
            'sub' => 'user_123',
            'role' => 'admin',
            'iss' => 'nexus-app',
            'aud' => 'nexus-api',
        ];

        // 1. Basic Encode & Decode
        $jwt = Jwt::encode($payload, $secret, 3600);
        $decoded = Jwt::decode($jwt, $secret, [
            'issuer' => 'nexus-app',
            'audience' => 'nexus-api',
        ]);
        $this->assertNotNull($decoded);
        $this->assertSame('user_123', $decoded['sub']);

        // 2. Secret Mismatch
        $this->assertNull(Jwt::decode($jwt, 'wrong-secret'));

        // 3. Issuer & Audience Mismatch
        $this->assertNull(Jwt::decode($jwt, $secret, ['issuer' => 'invalid-issuer']));
        $this->assertNull(Jwt::decode($jwt, $secret, ['audience' => 'invalid-aud']));

        // 4. Leeway handling for future iat (Issued At)
        $now = time();
        $futureIatPayload = [
            'sub' => 'user_future',
            'iat' => $now + 5, // 5 seconds in future
            'exp' => $now + 3600,
        ];
        $futureJwt = Jwt::encode($futureIatPayload, $secret);

        // Without leeway -> rejected
        $this->assertNull(Jwt::decode($futureJwt, $secret, ['leeway' => 0]));
        // With 10s leeway -> accepted
        $decodedLeeway = Jwt::decode($futureJwt, $secret, ['leeway' => 10]);
        $this->assertNotNull($decodedLeeway);
        $this->assertSame('user_future', $decodedLeeway['sub']);

        // 5. Expired token with leeway
        $expiredPayload = [
            'sub' => 'user_expired',
            'iat' => $now - 100,
            'exp' => $now - 5, // expired 5 seconds ago
        ];
        $expiredJwt = Jwt::encode($expiredPayload, $secret);
        $this->assertNull(Jwt::decode($expiredJwt, $secret, ['leeway' => 0]));
        $decodedExpiredLeeway = Jwt::decode($expiredJwt, $secret, ['leeway' => 10]);
        $this->assertNotNull($decodedExpiredLeeway);
        $this->assertSame('user_expired', $decodedExpiredLeeway['sub']);
    }

    /**
     * Header Injection & Security Header Protections
     */
    public function testHeaderInjectionAndCookieProtections(): void
    {
        $response = new Response();
        $thrown = false;
        try {
            $response->header("X-Custom\r\nHeader", "value");
        } catch (\InvalidArgumentException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown, 'CRLF in header name should throw InvalidArgumentException');
    }

    public function testHeaderValueCrLfInjectionThrowsException(): void
    {
        $response = new Response();
        $thrown = false;
        try {
            $response->header("X-Custom", "value\r\nSet-Cookie: evil=1");
        } catch (\InvalidArgumentException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown, 'CRLF in header value should throw InvalidArgumentException');
    }

    public function testCookieCrLfInjectionThrowsException(): void
    {
        $response = new Response();
        $thrown = false;
        try {
            $response->withCookie("session\r\nid", "abc");
        } catch (\InvalidArgumentException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown, 'CRLF in cookie name should throw InvalidArgumentException');
    }

    public function testTlsAndSecurityHeadersMiddleware(): void
    {
        $middleware = new SecurityHeadersMiddleware();

        // HTTP request (HSTS should be omitted)
        $httpRequest = new Request('GET', 'http://example.com', [], [], [], [], [], '');
        $httpResponse = $middleware->handle($httpRequest, fn() => new Response('OK'));
        $this->assertNull($httpResponse->getHeader('Strict-Transport-Security'));

        // HTTPS request (HSTS should be present)
        $httpsRequest = new Request('GET', 'https://example.com', [], [], [], [], ['HTTPS' => 'on'], '');
        $httpsResponse = $middleware->handle($httpsRequest, fn() => new Response('OK'));
        $this->assertNotNull($httpsResponse->getHeader('Strict-Transport-Security'));
        $this->assertStringContainsString('max-age=', $httpsResponse->getHeader('Strict-Transport-Security'));
        $this->assertSame('nosniff', $httpsResponse->getHeader('X-Content-Type-Options'));
    }

    /**
     * Cache Envelope Round-Trips (Scalar, Array, TTL Expiry)
     */
    public function testCacheEnvelopeRoundTrips(): void
    {
        $storageDir = dirname(__DIR__, 2) . '/storage/framework/cache_test';
        $cache = new FileCache($storageDir);
        $cache->clear();

        // 1. Array payload
        $arrayData = ['user' => 'Alice', 'roles' => ['admin', 'editor'], 'active' => true];
        $cache->set('user_envelope', $arrayData, 3600);
        $this->assertSame($arrayData, $cache->get('user_envelope'));

        // 2. Scalar int & string
        $cache->set('count', 42, 3600);
        $this->assertSame(42, $cache->get('count'));

        // 3. Expiry validation
        $cache->set('temp_key', 'transient', 1);
        $this->assertSame('transient', $cache->get('temp_key'));
        sleep(2);
        $this->assertNull($cache->get('temp_key'));

        // 4. Increment with envelope
        $cache->set('counter', 10, 3600);
        $newVal = $cache->increment('counter', 5, 3600);
        $this->assertSame(15, $newVal);

        $cache->clear();
    }

    /**
     * Queue Operations & Dead-Letter Payload Formatting
     */
    public function testQueueInterfaceAndDeadLetterPayload(): void
    {
        $queue = new class implements QueueInterface {
            public array $pushed = [];
            public array $delayed = [];
            public array $failed = [];

            public function push(Job $job, string $queue = 'default'): bool
            {
                $this->pushed[] = ['job' => $job, 'queue' => $queue];
                return true;
            }

            public function pop(string $queue = 'default'): ?Job
            {
                if (!empty($this->pushed)) {
                    $item = array_shift($this->pushed);
                    return $item['job'];
                }
                return null;
            }

            public function delete(Job $job): bool
            {
                return true;
            }

            public function release(Job $job, int $delay = 0, string $queue = 'default'): bool
            {
                $this->delayed[] = ['job' => $job, 'delay' => $delay, 'queue' => $queue];
                return true;
            }

            public function fail(Job $job, \Throwable $e, string $queue = 'default'): bool
            {
                $this->failed[] = [
                    'job' => $job,
                    'exception' => $e,
                    'queue' => $queue,
                ];
                return true;
            }
        };

        // 1. Successful job execution
        $successJob = new TestQueueJob();
        $successJob->payloadData = 'ok';
        $queue->push($successJob);

        $worker = new Worker($queue);
        $worker->work('default', 0, true);

        $this->assertCount(0, $queue->delayed);
        $this->assertCount(0, $queue->failed);

        // 2. Failed job execution & dead-letter queue routing
        $failingJob = new TestQueueJob();
        $failingJob->payloadData = 'fail';
        $failingJob->maxTries = 1; // Immediately fail and route to dead-letter queue
        $queue->push($failingJob);

        $worker->work('default', 0, true);

        $this->assertCount(1, $queue->failed);
        $failedEntry = $queue->failed[0];
        $this->assertInstanceOf(TestQueueJob::class, $failedEntry['job']);
        $this->assertInstanceOf(\RuntimeException::class, $failedEntry['exception']);
        $this->assertSame('Processing failed', $failedEntry['exception']->getMessage());
    }

    /**
     * Test that CACHE_LEGACY_UNSERIALIZE=true throws RuntimeException in production
     */
    public function testProductionLegacyCacheUnserializeProhibited(): void
    {
        $storageDir = dirname(__DIR__, 2) . '/storage/framework/cache_test_prod';
        $cache = new FileCache($storageDir);

        $_ENV['APP_ENV'] = 'production';
        $_SERVER['APP_ENV'] = 'production';
        putenv('APP_ENV=production');
        $_ENV['CACHE_LEGACY_UNSERIALIZE'] = 'true';
        $_SERVER['CACHE_LEGACY_UNSERIALIZE'] = 'true';
        putenv('CACHE_LEGACY_UNSERIALIZE=true');

        // Create a raw file without 'v' JSON envelope simulating legacy cache entry
        $filePath = $storageDir . '/' . md5('legacy_key') . '.cache';
        @mkdir($storageDir, 0755, true);
        file_put_contents($filePath, 'a:1:{s:12:"expires_at";i:0;}');

        $thrown = false;
        try {
            $cache->get('legacy_key');
        } catch (\RuntimeException $e) {
            $thrown = true;
            $this->assertStringContainsString('prohibited in production', $e->getMessage());
        } finally {
            $_ENV['APP_ENV'] = 'development';
            $_SERVER['APP_ENV'] = 'development';
            putenv('APP_ENV=development');
            $_ENV['CACHE_LEGACY_UNSERIALIZE'] = 'false';
            $_SERVER['CACHE_LEGACY_UNSERIALIZE'] = 'false';
            putenv('CACHE_LEGACY_UNSERIALIZE=false');
            $cache->clear();
        }

        $this->assertTrue($thrown, 'Legacy unserialize in production must throw RuntimeException');
    }
}
