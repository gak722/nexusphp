<?php
declare(strict_types=1);

namespace Nexus\Tests\Feature;

use Nexus\Queue\RedisQueue;
use Nexus\Queue\Job;
use PHPUnit\Framework\TestCase;

class SimpleTestJob extends Job
{
    public string $payload = '';
    public function handle(): void
    {
        if ($this->payload === 'fail') {
            throw new \RuntimeException('Processing failed');
        }
    }
}

class RedisQueueIntegrationTest extends TestCase
{
    public function testDelayedReleaseAndPop(): void
    {
        if (getenv('CI_REDIS') !== 'true' || !class_exists('\Redis')) {
            $this->assertTrue(true);
            return;
        }
        $queue = new RedisQueue();
        // flush test keys
        $r = new \Redis();
        $r->connect('127.0.0.1', 6379);
        $r->del('queues:default');
        $r->del('queues:delayed:default');

        $job = new SimpleTestJob();
        $job->payload = 'ok';

        $queue->release($job, 1, 'default');

        // immediate pop should return null
        $p1 = $queue->pop('default');
        $this->assertNull($p1);

        // wait for delay
        sleep(2);
        $p2 = $queue->pop('default');
        $this->assertInstanceOf(SimpleTestJob::class, $p2);
    }

    public function testFailStoresStructuredPayload(): void
    {
        if (getenv('CI_REDIS') !== 'true' || !class_exists('\Redis')) {
            $this->assertTrue(true);
            return;
        }
        $queue = new RedisQueue();
        $r = new \Redis();
        $r->connect('127.0.0.1', 6379);
        $r->del('queues:failed:default');

        $job = new SimpleTestJob();
        $job->payload = 'fail';

        $ex = new \RuntimeException('boom');
        $queue->fail($job, $ex, 'default');

        $item = $r->lPop('queues:failed:default');
        $this->assertNotFalse($item);
        $decoded = json_decode($item, true);
        $this->assertArrayHasKey('exception', $decoded);
        $this->assertArrayHasKey('trace', $decoded['exception']);
    }
}
