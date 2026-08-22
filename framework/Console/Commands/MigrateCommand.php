<?php
declare(strict_types=1);

namespace Nexus\Console\Commands;

use Nexus\Console\Command;
use Nexus\Database\Connection;
use Nexus\Database\Migrator;

class MigrateCommand extends Command
{
    protected string $name = 'migrate';
    protected string $description = 'Run pending database migrations';

    public function execute(array $args): int
    {
        try {
            $connection = $this->app->make(Connection::class);
            $migrationsPath = $this->app->basePath('database/migrations');

            $migrator = new Migrator($connection, $migrationsPath);
            $migrator->run();

            $this->success("Database migrations executed successfully.");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Migration execution failed: " . $e->getMessage());
            return 1;
        }
    }
}
