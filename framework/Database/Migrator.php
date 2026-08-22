<?php
declare(strict_types=1);

namespace Nexus\Database;

/**
 * Migration Runner Subsystem
 */
class Migrator
{
    public function __construct(protected Connection $connection, protected string $migrationPath)
    {
        Schema::setConnection($this->connection);
        $this->ensureMigrationTable();
    }

    protected function ensureMigrationTable(): void
    {
        $driver = $this->connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );";
        }
        $this->connection->statement($sql);
    }

    public function run(): array
    {
        $ran = $this->getRan();
        $files = glob($this->migrationPath . '/*.php');
        if ($files === false) {
            return [];
        }

        sort($files); // Sort chronologically by filename

        $batch = $this->getNextBatchNumber();
        $executed = [];

        foreach ($files as $file) {
            $migrationName = basename($file, '.php');
            if (in_array($migrationName, $ran, true)) {
                continue;
            }

            require_once $file;
            $className = $this->resolveClassName($migrationName);
            
            /** @var Migration $migration */
            $migration = new $className();
            $migration->up();

            $this->log($migrationName, $batch);
            $executed[] = $migrationName;
        }

        return $executed;
    }

    public function rollback(): array
    {
        $lastBatch = $this->getLastBatchNumber();
        if ($lastBatch === 0) {
            return [];
        }

        $rows = $this->connection->select("SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC", [$lastBatch]);
        $rolledBack = [];

        foreach ($rows as $row) {
            $migrationName = $row['migration'];
            $file = $this->migrationPath . '/' . $migrationName . '.php';

            if (file_exists($file)) {
                require_once $file;
                $className = $this->resolveClassName($migrationName);
                
                /** @var Migration $migration */
                $migration = new $className();
                $migration->down();
            }

            $this->connection->statement("DELETE FROM migrations WHERE migration = ?", [$migrationName]);
            $rolledBack[] = $migrationName;
        }

        return $rolledBack;
    }

    protected function getRan(): array
    {
        $rows = $this->connection->select("SELECT migration FROM migrations");
        return array_column($rows, 'migration');
    }

    protected function getNextBatchNumber(): int
    {
        $row = $this->connection->select("SELECT MAX(batch) as max_batch FROM migrations");
        return ((int) ($row[0]['max_batch'] ?? 0)) + 1;
    }

    protected function getLastBatchNumber(): int
    {
        $row = $this->connection->select("SELECT MAX(batch) as max_batch FROM migrations");
        return (int) ($row[0]['max_batch'] ?? 0);
    }

    protected function log(string $migration, int $batch): void
    {
        $this->connection->statement("INSERT INTO migrations (migration, batch) VALUES (?, ?)", [$migration, $batch]);
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
