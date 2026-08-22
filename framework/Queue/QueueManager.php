<?php
declare(strict_types=1);

namespace Nexus\Queue;

use Nexus\Database\Connection;
use Nexus\Foundation\Application;

/**
 * Queue Driver Factory & Manager
 */
class QueueManager
{
    protected array $connections = [];

    public function __construct(protected Application $app) {}

    public function connection(?string $name = null): QueueInterface
    {
        $name = $name ?? env('QUEUE_CONNECTION', 'database');

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }

        return $this->connections[$name];
    }

    protected function createConnection(string $name): QueueInterface
    {
        return match ($name) {
            'database' => new DatabaseQueue($this->app->make(Connection::class)),
            'redis' => class_exists('\Redis')
                ? new RedisQueue(env('REDIS_HOST', '127.0.0.1'), (int) env('REDIS_PORT', 6379))
                : new DatabaseQueue($this->app->make(Connection::class)),
            default => new DatabaseQueue($this->app->make(Connection::class)),
        };
    }
}
