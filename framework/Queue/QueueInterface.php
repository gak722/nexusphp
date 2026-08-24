<?php
declare(strict_types=1);

namespace Nexus\Queue;

/**
 * Queue Driver Interface Contract
 */
interface QueueInterface
{
    public function push(Job $job, string $queue = 'default'): bool;
    public function pop(string $queue = 'default'): ?Job;
    public function delete(Job $job): bool;
    public function release(Job $job, int $delay = 0, string $queue = 'default'): bool;
    public function fail(Job $job, \Throwable $e, string $queue = 'default'): bool;
}
