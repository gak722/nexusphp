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

    public function testDotNetStyleDependencyInjection(): void
    {
        $app = Application::getInstance();

        // 1. Transient
        $app->addTransient('dummy_transient', fn () => new \stdClass());
        $inst1 = $app->make('dummy_transient');
        $inst2 = $app->make('dummy_transient');
        if ($inst1 === $inst2) {
            throw new \RuntimeException("addTransient did not produce new instances.");
        }

        // 2. Singleton
        $app->addSingleton('dummy_singleton', fn () => new \stdClass());
        $s1 = $app->make('dummy_singleton');
        $s2 = $app->make('dummy_singleton');
        if ($s1 !== $s2) {
            throw new \RuntimeException("addSingleton did not return shared instance.");
        }

        // 3. Scoped
        $app->addScoped('dummy_scoped', fn () => new \stdClass());
        $sc1 = $app->make('dummy_scoped');
        $sc2 = $app->make('dummy_scoped');
        if ($sc1 !== $sc2) {
            throw new \RuntimeException("addScoped did not return request scope instance.");
        }
    }

    public function testConfigArrayBasedDependencyInjection(): void
    {
        $app = Application::getInstance();
        $config = $app->make(Config::class);

        $config->set('services', [
            'singletons' => [
                'config_singleton' => \stdClass::class,
            ],
            'transients' => [
                'config_transient' => \stdClass::class,
            ],
        ]);

        $app->registerConfiguredServices();

        $sing1 = $app->make('config_singleton');
        $sing2 = $app->make('config_singleton');
        if ($sing1 !== $sing2) {
            throw new \RuntimeException("Configured singleton auto-registration failed.");
        }

        $trans1 = $app->make('config_transient');
        $trans2 = $app->make('config_transient');
        if ($trans1 === $trans2) {
            throw new \RuntimeException("Configured transient auto-registration failed.");
        }
    }
}
