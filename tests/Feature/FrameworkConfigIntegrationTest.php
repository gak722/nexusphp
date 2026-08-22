<?php
declare(strict_types=1);

use Nexus\Cache\CacheManager;
use Nexus\Database\Connection;
use Nexus\Events\BroadcastManager;
use Nexus\Foundation\Application;
use Nexus\Foundation\Config;
use Nexus\Http\Middleware\ExceptionHandlerMiddleware;
use Nexus\Http\Request;
use Nexus\Queue\QueueManager;
use Nexus\Security\Auth;
use Nexus\Security\Encryptor;

class FrameworkConfigIntegrationTest
{
    public function testAppConfigOverrides(): void
    {
        $app = Application::getInstance();
        $config = $app->make(Config::class);

        $config->set('app.key', 'test_custom_app_secret_key_32_bytes!');
        $encryptor = new Encryptor();
        $encrypted = $encryptor->encrypt('hello');
        if ($encryptor->decrypt($encrypted) !== 'hello') {
            throw new \RuntimeException("Encryptor failed with custom app.key.");
        }

        $config->set('app.debug', false);
        $middleware = new ExceptionHandlerMiddleware();
        $request = new Request('GET', '/', [], [], [], [], [], '');
        $response = $middleware->handle($request, function () {
            throw new \RuntimeException('Test error');
        });

        if (str_contains($response->getContent(), 'Stack Trace:')) {
            throw new \RuntimeException("ExceptionHandlerMiddleware did not respect app.debug config.");
        }
    }

    public function testCacheAndQueueConfigOverrides(): void
    {
        $app = Application::getInstance();
        $config = $app->make(Config::class);

        if (!$app->has(Connection::class)) {
            $app->singleton(Connection::class, fn () => new Connection(['driver' => 'sqlite', 'database' => ':memory:']));
        }

        $config->set('cache.default', 'file');
        $config->set('cache.stores.file.path', $app->storagePath('test_cache'));

        $cacheManager = new CacheManager($app);
        $driver = $cacheManager->driver();
        if (!$driver instanceof \Nexus\Cache\FileCache) {
            throw new \RuntimeException("CacheManager default driver resolution failed.");
        }

        $config->set('queue.default', 'database');
        $queueManager = new QueueManager($app);
        $connection = $queueManager->connection();
        if (!$connection instanceof \Nexus\Queue\DatabaseQueue) {
            throw new \RuntimeException("QueueManager default connection resolution failed.");
        }

        $broadcastManager = new BroadcastManager();
        if (!$broadcastManager instanceof BroadcastManager) {
            throw new \RuntimeException("BroadcastManager resolution failed.");
        }
    }

    public function testConfigFetchExceptionSafety(): void
    {
        $config = new Config();
        
        // Ensure fetching non-existent nested keys returns default without throwing
        $val = $config->get('non_existent.deeply.nested.key', 'fallback');
        if ($val !== 'fallback') {
            throw new \RuntimeException("Config::get exception safety test failed.");
        }

        if ($config->has('invalid.key')) {
            throw new \RuntimeException("Config::has returned true for invalid key.");
        }
    }
}
