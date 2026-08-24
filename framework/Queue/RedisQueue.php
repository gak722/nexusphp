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
        $payload = json_encode([
            'class' => get_class($job),
            'properties' => get_object_vars($job)
        ]);
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

        $decoded = json_decode($payload, true);
        if (is_array($decoded) && isset($decoded['class']) && class_exists($decoded['class'])) {
            $className = $decoded['class'];
            $reflector = new \ReflectionClass($className);
            if ($reflector->isSubclassOf(Job::class) || $reflector->getName() === Job::class) {
                $job = $reflector->newInstanceWithoutConstructor();
                foreach ($decoded['properties'] ?? [] as $prop => $value) {
                    if ($reflector->hasProperty($prop)) {
                        $job->$prop = $value;
                    }
                }
                return $job;
            }
        }

        return null;
    }

    public function release(Job $job, int $delay = 0, string $queue = 'default'): bool
    {
        return $this->push($job, $queue);
    }

    public function fail(Job $job, \Throwable $e, string $queue = 'default'): bool
    {
        if ($this->redis === null) {
            return false;
        }
        $payload = json_encode([
            'class' => get_class($job),
            'properties' => get_object_vars($job),
            'exception' => sprintf("%s: %s in %s:%d", get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()),
            'failed_at' => time()
        ]);
        return $this->redis->rPush("queues:failed", $payload) !== false;
    }

    public function delete(Job $job): bool
    {
        return true;
    }
}
