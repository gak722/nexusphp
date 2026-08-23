<?php
declare(strict_types=1);

namespace Nexus\Console\Commands;

use Nexus\Console\Command;
use Nexus\Database\Connection;
use Nexus\Database\Migrations\MigrationRunner;

class MigrateCommand extends Command
{
    protected string $name = 'migrate';
    protected string $description = 'Run pending database migrations';

    public function execute(array $args): int
    {
        try {
            $connection = $this->app->make(Connection::class);
            $migrationsPath = $this->app->basePath('database/migrations');
            $runner = new MigrationRunner($connection, $migrationsPath);

            $dryRun = in_array('--dry-run', $args, true);

            $this->info("Migrating database...");
            $executed = $runner->run($dryRun);

            if (empty($executed)) {
                $this->info("Nothing to migrate.");
                return 0;
            }

            foreach ($executed as $m) {
                $this->success("  ✔ " . str_pad($m, 50, '.') . " DONE");
            }

            $this->success("\n" . count($executed) . " migration(s) executed successfully.");
            return 0;
        } catch (\Throwable $e) {
            $this->error("\nMigration failed:\nReason: " . $e->getMessage());
            return 1;
        }
    }
}
