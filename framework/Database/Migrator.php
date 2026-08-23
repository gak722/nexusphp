<?php
declare(strict_types=1);

namespace Nexus\Database;

use Nexus\Database\Migrations\MigrationRunner;

/**
 * Migration Runner Subsystem (Backward Compatibility Wrapper around MigrationRunner)
 */
class Migrator
{
    protected MigrationRunner $runner;

    public function __construct(protected Connection $connection, protected string $migrationPath)
    {
        $this->runner = new MigrationRunner($connection, $migrationPath);
    }

    public function run(): array
    {
        return $this->runner->run();
    }

    public function rollback(): array
    {
        return $this->runner->rollback();
    }

    public function getRunner(): MigrationRunner
    {
        return $this->runner;
    }
}
