<?php
declare(strict_types=1);

namespace Nexus\Queue;

use Nexus\Database\Connection;
use Nexus\Foundation\Application;
use Nexus\Foundation\Config;

/**
 * Queue Driver Factory & Manager
 */
class QueueManager
{
    protected array $connections = [];

    public function __construct(protected Application $app) {}

    public function connection(?string $name = null): QueueInterface
    {
        $name = $name ?? $this->config()->get('queue.default', env('QUEUE_CONNECTION', 'database'));

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }

        return $this->connections[$name];
    }

    protected function createConnection(string $name): QueueInterface
    {
        $config = $this->config();
        $connConfig = $config->get("queue.connections.{$name}", []);

        $redisHost = $connConfig['host'] ?? env('REDIS_HOST', '127.0.0.1');
        $redisPort = (int) ($connConfig['port'] ?? env('REDIS_PORT', 6379));

        return match ($name) {
            'database' => new DatabaseQueue($this->app->make(Connection::class)),
            'redis' => class_exists('\Redis')
                ? new RedisQueue($redisHost, $redisPort)
                : new DatabaseQueue($this->app->make(Connection::class)),
            default => new DatabaseQueue($this->app->make(Connection::class)),
        };
    }

    protected function config(): Config
    {
        try {
            if ($this->app->has(Config::class)) {
                $config = $this->app->make(Config::class);

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
