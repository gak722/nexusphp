<?php
declare(strict_types=1);

namespace Nexus\Console\Commands;

use Nexus\Console\Command;
use Nexus\Database\Connection;
use Nexus\Database\Migrations\MigrationRunner;

class MigrateStatusCommand extends Command
{
    protected string $name = 'migrate:status';
    protected string $description = 'Show the status of each migration';

    public function execute(array $args): int
    {
        try {
            $connection = $this->app->make(Connection::class);
            $migrationsPath = $this->app->basePath('database/migrations');
            $runner = new MigrationRunner($connection, $migrationsPath);

            $status = $runner->status();

            if (empty($status)) {
                $this->info("No migrations found.");
                return 0;
            }

            $this->info(sprintf("%-60s | %-10s | %-10s", "Migration", "Status", "Integrity"));
            $this->info(str_repeat("-", 86));

            foreach ($status as $item) {
                $statusStr = $item['status'] === 'Ran' ? "\033[32mRan\033[0m" : "\033[33mPending\033[0m";
                $integrityStr = $item['checksum_valid'] ? "\033[32mValid\033[0m" : "\033[31mModified!\033[0m";
                echo sprintf("%-60s | %-20s | %-20s\n", $item['migration'], $statusStr, $integrityStr);
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error("Failed to retrieve migration status: " . $e->getMessage());
            return 1;
        }
    }
}
