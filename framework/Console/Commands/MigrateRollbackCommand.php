<?php
declare(strict_types=1);

namespace Nexus\Console\Commands;

use Nexus\Console\Command;
use Nexus\Database\Connection;
use Nexus\Database\Migrations\MigrationRunner;

class MigrateRollbackCommand extends Command
{
    protected string $name = 'migrate:rollback';
    protected string $description = 'Rollback database migrations';

    public function execute(array $args): int
    {
        try {
            $connection = $this->app->make(Connection::class);
            $migrationsPath = $this->app->basePath('database/migrations');
            $runner = new MigrationRunner($connection, $migrationsPath);

            $step = 0;
            foreach ($args as $arg) {
                if (str_starts_with($arg, '--step=')) {
                    $step = (int) substr($arg, 7);
                }
            }

            $rolledBack = $runner->rollback($step);

            if (empty($rolledBack)) {
                $this->info("Nothing to rollback.");
                return 0;
            }

            foreach ($rolledBack as $m) {
                $this->warning("  ✔ Rolled back: " . $m);
            }

            $this->success("\n" . count($rolledBack) . " migration(s) rolled back successfully.");
            return 0;
        } catch (\Throwable $e) {
            $this->error("\nRollback failed:\nReason: " . $e->getMessage());
            return 1;
        }
    }
}
