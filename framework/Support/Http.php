<?php
declare(strict_types=1);

namespace Nexus\Support;

use Nexus\Http\Client\FakeHttpTransport;
use Nexus\Http\Client\HttpClient;
use Nexus\Http\Client\HttpResponse;

/**
 * Facade and helper entry point for HttpClient.
 */
class Http
{
    protected static ?HttpClient $fakeClient = null;

    /**
     * Swap the client implementation with a FakeHttpTransport.
     */
    public static function fake(array $stubs = []): FakeHttpTransport
    {
        $fakeTransport = new FakeHttpTransport($stubs);
        static::$fakeClient = new HttpClient($fakeTransport);
        return $fakeTransport;
    }

    /**
     * Reset any fakes.
     */
    public static function restore(): void
    {
        static::$fakeClient = null;
    }

    protected static function client(): HttpClient
    {
        if (static::$fakeClient !== null) {
            return static::$fakeClient;
        }

        if (\Nexus\Foundation\Application::getInstance()->has(HttpClient::class)) {
            return \Nexus\Foundation\Application::getInstance()->make(HttpClient::class);
        }

        return new HttpClient();
    }

    public static function __callStatic(string $method, array $args): mixed
    {
        return static::client()->$method(...$args);
    }
}
