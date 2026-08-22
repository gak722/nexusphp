<?php
declare(strict_types=1);

namespace Nexus\Queue;

/**
 * High-Performance Redis Queue Driver
 */
class RedisQueue implements QueueInterface
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

    public function push(Job $job, string $queue = 'default'): bool
    {
        if ($this->redis === null) {
            return false;
        }
        $payload = serialize($job);
        return $this->redis->rPush("queues:{$queue}", $payload) !== false;
    }

    public function pop(string $queue = 'default'): ?Job
    {
        if ($this->redis === null) {
            return null;
        }
        $payload = $this->redis->lPop("queues:{$queue}");
        if ($payload === false || !$payload) {
            return null;
        }

        $job = @unserialize($payload);
        return $job instanceof Job ? $job : null;
    }

    public function delete(Job $job): bool
    {
        // Redis lPop automatically removes items from the queue list
        return true;
    }
}
