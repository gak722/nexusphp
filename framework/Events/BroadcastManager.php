<?php
declare(strict_types=1);

namespace Nexus\Events;

use Nexus\Foundation\Application;
use Nexus\Foundation\Config;

/**
 * Redis Pub/Sub Broadcast Manager
 */
class BroadcastManager
{
    protected ?\Redis $redis = null;

    public function __construct(?string $host = null, ?int $port = null)
    {
        $config = $this->config();
        $host = $host ?? $config->get('broadcasting.connections.redis.host', env('REDIS_HOST', '127.0.0.1'));
        $port = $port ?? (int) $config->get('broadcasting.connections.redis.port', env('REDIS_PORT', 6379));

        if (class_exists('\Redis')) {
            try {
                $this->redis = new \Redis();
                @$this->redis->connect($host, $port, 1.5);
            } catch (\Throwable $e) {
                $this->redis = null;
            }
        }
    }

    public function broadcast(string $channel, mixed $message): bool
    {
        if ($this->redis === null) {
            return false;
        }

        $payload = is_string($message) ? $message : json_encode($message);
        return $this->redis->publish($channel, $payload) > 0;
    }

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
