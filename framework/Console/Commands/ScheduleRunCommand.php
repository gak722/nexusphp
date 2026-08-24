<?php
declare(strict_types=1);

namespace Nexus\Console\Commands;

use Nexus\Console\Command;
use Nexus\Scheduling\Schedule;

class ScheduleRunCommand extends Command
{
    protected string $name = 'schedule:run';
    protected string $description = 'Run the scheduled tasks';

    public function execute(array $args): int
    {
        $schedule = new Schedule();

        $kernelFile = $this->app->basePath('app/Console/Kernel.php');
        if (file_exists($kernelFile)) {
            require_once $kernelFile;
            if (class_exists('App\\Console\\Kernel')) {
                $kernel = new \App\Console\Kernel();
                if (method_exists($kernel, 'schedule')) {
                    $kernel->schedule($schedule);
                }
            }
        }

        $events = $schedule->getDueEvents();
        if (empty($events)) {
            $this->info("No scheduled commands are due to run.");
            return 0;
        }

        $this->info("Running scheduled tasks...");

        foreach ($events as $event) {
            try {
                $event->run();
                $this->success("Ran scheduled task successfully.");
            } catch (\Throwable $e) {
                $this->error("Task execution failed: " . $e->getMessage());
            }
        }

        return 0;
    }
}
