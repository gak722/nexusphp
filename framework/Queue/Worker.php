<?php
declare(strict_types=1);

namespace Nexus\Queue;

/**
 * Long-Running Queue Worker Process
 */
class Worker
{
    public function __construct(protected QueueInterface $queue) {}

    public function work(string $queueName = 'default', int $sleep = 2, bool $once = false): void
    {
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            $running = true;
            pcntl_signal(SIGTERM, function () use (&$running) { $running = false; });
            pcntl_signal(SIGINT, function () use (&$running) { $running = false; });
        } else {
            $running = true;
        }

        while ($running) {
            try {
                $job = $this->queue->pop($queueName);
            } catch (\Throwable $e) {
                $this->logError("Queue pop failed: " . $e->getMessage());
                if ($once) {
                    break;
                }
                sleep($sleep);
                continue;
            }

            if ($job === null) {
                if ($once) {
                    break;
                }
                sleep($sleep);
                continue;
            }

            try {
                $job->handle();
                $this->queue->delete($job);
            } catch (\Throwable $e) {
                $this->logError(sprintf(
                    "Job %s (Attempts: %d/%d) failed: %s in %s:%d\nStack trace:\n%s",
                    get_class($job),
                    $job->attempts,
                    $job->maxTries,
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString()
                ));
            }

            if ($once) {
                break;
            }
        }
    }

    protected function logError(string $message): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        @file_put_contents(
            $logDir . '/queue_failed.log',
            sprintf("[%s] %s\n\n", date('Y-m-d H:i:s'), $message),
            FILE_APPEND | LOCK_EX
        );
    }
}
