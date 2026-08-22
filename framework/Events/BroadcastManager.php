<?php
declare(strict_types=1);

namespace Nexus\Events;

/**
 * Redis Pub/Sub Broadcast Manager
 */
class BroadcastManager
{
    protected ?\Redis $redis = null;

    public function __construct(string $host = '127.0.0.1', int $port = 6379)
    {
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
}
