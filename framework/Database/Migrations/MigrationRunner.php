<?php
declare(strict_types=1);

namespace Nexus\Database\Migrations;

use Nexus\Database\Connection;
use Nexus\Database\Exceptions\MigrationException;
use Nexus\Database\Migration;
use Nexus\Database\Schema;

class MigrationRunner
{
    protected MigrationRepository $repository;
    protected MigrationLock $lock;

    public function __construct(protected Connection $connection, protected string $migrationPath)
    {
        Schema::setConnection($this->connection);
        $this->repository = new MigrationRepository($this->connection);
        $this->repository->ensureRepository();
        $this->lock = new MigrationLock($this->connection);
    }

    public function run(bool $dryRun = false): array
    {
        $this->lock->acquire();
        try {
            $ran = $this->repository->getRan();
            $files = glob($this->migrationPath . '/*.php') ?: [];
            sort($files);

            $batch = $this->repository->getNextBatchNumber();
            $executed = [];

            foreach ($files as $file) {
                $migrationName = basename($file, '.php');
                if (in_array($migrationName, $ran, true)) {
                    continue;
                }

                $checksum = md5_file($file) ?: null;

                if ($dryRun) {
                    $executed[] = "[DRY-RUN] " . $migrationName;
                    continue;
                }

                require_once $file;
                $className = $this->resolveClassName($migrationName);

                /** @var Migration $migration */
                $migration = new $className();

                $this->connection->transaction(function () use ($migration, $migrationName, $batch, $checksum) {
                    $migration->up();
                    $this->repository->log($migrationName, $batch, $checksum);
                });

                $executed[] = $migrationName;
            }

            return $executed;
        } finally {
            $this->lock->release();
        }
    }

    public function rollback(int $steps = 0, bool $dryRun = false): array
    {
        $this->lock->acquire();
        try {
            $lastBatch = $this->repository->getLastBatchNumber();
            if ($lastBatch === 0) return [];

            $sql = ($steps > 0)
                ? "SELECT migration FROM migrations ORDER BY id DESC LIMIT {$steps}"
                : "SELECT migration FROM migrations WHERE batch = {$lastBatch} ORDER BY id DESC";

            $rows = $this->connection->select($sql);
            $rolledBack = [];

            foreach ($rows as $row) {
                $migrationName = $row['migration'];
                $file = $this->migrationPath . '/' . $migrationName . '.php';

                if ($dryRun) {
                    $rolledBack[] = "[DRY-RUN ROLLBACK] " . $migrationName;
                    continue;
                }

                if (file_exists($file)) {
                    require_once $file;
                    $className = $this->resolveClassName($migrationName);
                    
                    /** @var Migration $migration */
                    $migration = new $className();
                    
                    $this->connection->transaction(function () use ($migration, $migrationName) {
                        $migration->down();
                        $this->repository->delete($migrationName);
                    });
                } else {
                    $this->repository->delete($migrationName);
                }

                $rolledBack[] = $migrationName;
            }

            return $rolledBack;
        } finally {
            $this->lock->release();
        }
    }

    public function status(): array
    {
        $ranMap = $this->repository->getRanWithChecksums();
        $files = glob($this->migrationPath . '/*.php') ?: [];
        sort($files);

        $status = [];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            $isRan = isset($ranMap[$name]);
            $currentChecksum = md5_file($file) ?: null;
            $checksumMatch = $isRan ? ($ranMap[$name] === $currentChecksum) : true;

            $status[] = [
                'migration' => $name,
                'status' => $isRan ? 'Ran' : 'Pending',
                'checksum_valid' => $checksumMatch,
            ];
        }

        return $status;
    }

    public function fresh(): array
    {
        $this->wipe();
        return $this->run();
    }

    public function wipe(): void
    {
        $driver = $this->connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $tables = $this->connection->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            foreach ($tables as $t) {
                $this->connection->statement("DROP TABLE IF EXISTS " . $t['name']);
            }
        } elseif ($driver === 'mysql') {
            $this->connection->statement("SET FOREIGN_KEY_CHECKS = 0;");
            $tables = $this->connection->select("SHOW TABLES");
            foreach ($tables as $t) {
                $tableName = current($t);
                $this->connection->statement("DROP TABLE IF EXISTS `{$tableName}`");
            }
            $this->connection->statement("SET FOREIGN_KEY_CHECKS = 1;");
        } elseif ($driver === 'pgsql') {
            $tables = $this->connection->select("SELECT tablename FROM pg_tables WHERE schemaname='public'");
            foreach ($tables as $t) {
                $this->connection->statement("DROP TABLE IF EXISTS \"" . $t['tablename'] . "\" CASCADE");
            }
        }
    }

    protected function resolveClassName(string $filename): string
    {
        $parts = explode('_', $filename);
        while (!empty($parts) && is_numeric($parts[0])) {
            array_shift($parts);
        }
        $name = implode('_', $parts);
        return \Nexus\Support\Str::studly($name);
    }
}
