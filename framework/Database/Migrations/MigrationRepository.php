<?php
declare(strict_types=1);

namespace Nexus\Database\Migrations;

use Nexus\Database\Connection;
use Nexus\Database\Exceptions\MigrationException;
use Nexus\Database\Exceptions\MigrationLockException;

class MigrationRepository
{
    public function __construct(protected Connection $connection, protected string $table = 'migrations') {}

    public function ensureRepository(): void
    {
        $driver = $this->connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                checksum VARCHAR(64) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                checksum VARCHAR(64) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );";
        }
        $this->connection->statement($sql);
    }

    public function getRan(): array
    {
        $rows = $this->connection->select("SELECT migration FROM {$this->table} ORDER BY batch ASC, migration ASC");
        return array_column($rows, 'migration');
    }

    public function getRanWithChecksums(): array
    {
        $rows = $this->connection->select("SELECT migration, checksum FROM {$this->table}");
        $map = [];
        foreach ($rows as $r) {
            $map[$r['migration']] = $r['checksum'];
        }
        return $map;
    }

    public function getNextBatchNumber(): int
    {
        $row = $this->connection->select("SELECT MAX(batch) as max_batch FROM {$this->table}");
        return ((int) ($row[0]['max_batch'] ?? 0)) + 1;
    }

    public function getLastBatchNumber(): int
    {
        $row = $this->connection->select("SELECT MAX(batch) as max_batch FROM {$this->table}");
        return (int) ($row[0]['max_batch'] ?? 0);
    }

    public function log(string $migration, int $batch, ?string $checksum = null): void
    {
        $this->connection->statement(
            "INSERT INTO {$this->table} (migration, batch, checksum) VALUES (?, ?, ?)",
            [$migration, $batch, $checksum]
        );
    }

    public function delete(string $migration): void
    {
        $this->connection->statement("DELETE FROM {$this->table} WHERE migration = ?", [$migration]);
    }
}

class MigrationLock
{
    public function __construct(protected Connection $connection, protected string $lockTable = 'migration_locks') {}

    public function acquire(): bool
    {
        $driver = $this->connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $sql = match ($driver) {
            'sqlite' => "CREATE TABLE IF NOT EXISTS {$this->lockTable} (id INTEGER PRIMARY KEY, locked_at DATETIME);",
            default => "CREATE TABLE IF NOT EXISTS {$this->lockTable} (id INT PRIMARY KEY, locked_at TIMESTAMP);",
        };
        $this->connection->statement($sql);

        $existing = $this->connection->select("SELECT locked_at FROM {$this->lockTable} WHERE id = 1");
        if (!empty($existing)) {
            throw new MigrationLockException("Another migration execution is currently in progress.");
        }

        return $this->connection->statement("INSERT INTO {$this->lockTable} (id, locked_at) VALUES (1, ?)", [date('Y-m-d H:i:s')]);
    }

    public function release(): void
    {
        $this->connection->statement("DELETE FROM {$this->lockTable} WHERE id = 1");
    }
}
