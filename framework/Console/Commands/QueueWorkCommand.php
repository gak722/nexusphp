<?php
declare(strict_types=1);

namespace Nexus\Console\Commands;

use Nexus\Console\Command;
use Nexus\Queue\QueueManager;
use Nexus\Queue\Worker;

class QueueWorkCommand extends Command
{
    protected string $name = 'queue:work';
    protected string $description = 'Start processing jobs on the queue worker';

    public function execute(array $args): int
    {
        $queueName = $args[0] ?? 'default';

        try {
            $queueManager = new QueueManager($this->app);
            $queue = $queueManager->connection();

            $this->info("Nexus worker starting on queue [{$queueName}]...");

            $worker = new Worker($queue);
            $worker->work($queueName, 2);

            return 0;
        } catch (\Throwable $e) {
            $this->error("Worker execution failed: " . $e->getMessage());
            return 1;
        }
    }
}
