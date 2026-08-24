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
        // Move any ready delayed jobs into the ready queue (atomic-ish via Lua)
        $now = time();
        $script = <<<'LUA'
local delayed = KEYS[1]
local ready = KEYS[2]
local now = tonumber(ARGV[1])
local moved = 0
local items = redis.call('zrangebyscore', delayed, '-inf', now, 'LIMIT', 0, 50)
for i, v in ipairs(items) do
  if redis.call('zrem', delayed, v) == 1 then
    redis.call('rpush', ready, v)
    moved = moved + 1
  end
end
return moved
LUA;
        try {
            $this->redis->eval($script, ["queues:delayed:{$queue}", "queues:{$queue}", (string)$now], 2);
        } catch (\Throwable $e) {
            // best-effort fallback: non-atomic move
            $items = $this->redis->zRangeByScore("queues:delayed:{$queue}", '-inf', (string)$now, ['limit' => [0, 50]]);
            if (is_array($items) && !empty($items)) {
                foreach ($items as $item) {
                    if ($this->redis->zRem("queues:delayed:{$queue}", $item)) {
                        $this->redis->rPush("queues:{$queue}", $item);
                    }
                }
            }
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
        $payload = json_encode([
            'class' => get_class($job),
            'properties' => get_object_vars($job)
        ]);
        if ($this->redis === null) {
            return false;
        }
        if ($delay > 0) {
            return $this->redis->zAdd("queues:delayed:{$queue}", time() + $delay, $payload) !== false;
        }
        return $this->redis->rPush("queues:{$queue}", $payload) !== false;
    }

    public function fail(Job $job, \Throwable $e, string $queue = 'default'): bool
    {
        if ($this->redis === null) {
            return false;
        }
        $failedPayload = json_encode([
            'job' => [
                'class' => get_class($job),
                'properties' => get_object_vars($job),
            ],
            'exception' => [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString()),
            ],
            'meta' => [
                'queue' => $queue,
                'attempts' => $job->attempts ?? 0,
                'failed_at' => time(),
            ],
        ]);
        return $this->redis->rPush("queues:failed:{$queue}", $failedPayload) !== false;
    }

    public function delete(Job $job): bool
    {
        return true;
    }
}
