<?php
declare(strict_types=1);

namespace Nexus\Queue;

use Nexus\Database\Connection;

/**
 * Database Backed Queue Driver
 */
class DatabaseQueue implements QueueInterface
{
    public function __construct(protected Connection $connection, protected string $table = 'jobs')
    {
        $this->ensureJobsTable();
    }

    protected function ensureJobsTable(): void
    {
        $driver = $this->connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                queue VARCHAR(255) NOT NULL,
                payload TEXT NOT NULL,
                attempts INT NOT NULL DEFAULT 0,
                reserved_at INT DEFAULT NULL,
                created_at INT NOT NULL
            );";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                queue VARCHAR(255) NOT NULL,
                payload LONGTEXT NOT NULL,
                attempts INT NOT NULL DEFAULT 0,
                reserved_at INT DEFAULT NULL,
                created_at INT NOT NULL
            );";
        }
        $this->connection->statement($sql);
    }

    public function push(Job $job, string $queue = 'default'): bool
    {
        $payload = json_encode([
            'class' => get_class($job),
            'properties' => get_object_vars($job)
        ]);
        return $this->connection->statement(
            "INSERT INTO {$this->table} (queue, payload, attempts, reserved_at, created_at) VALUES (?, ?, 0, NULL, ?)",
            [$queue, $payload, time()]
        );
    }

    public function pop(string $queue = 'default'): ?Job
    {
        $jobRecords = $this->connection->select(
            "SELECT * FROM {$this->table} WHERE queue = ? AND reserved_at IS NULL ORDER BY id ASC LIMIT 1",
            [$queue]
        );

        if (empty($jobRecords)) {
            return null;
        }

        $record = $jobRecords[0];
        $affected = $this->connection->affectingStatement(
            "UPDATE {$this->table} SET reserved_at = ?, attempts = attempts + 1 WHERE id = ? AND reserved_at IS NULL",
            [time(), $record['id']]
        );

        if ($affected === 0) {
            // Concurrent worker reserved job first
            return null;
        }

        $decoded = json_decode($record['payload'], true);
        if (is_array($decoded) && isset($decoded['class']) && class_exists($decoded['class'])) {
            $className = $decoded['class'];
            $reflector = new \ReflectionClass($className);
            if ($reflector->implementsInterface(Job::class)) {
                $job = $reflector->newInstanceWithoutConstructor();
                foreach ($decoded['properties'] ?? [] as $prop => $value) {
                    if ($reflector->hasProperty($prop)) {
                        $job->$prop = $value;
                    }
                }
                $job->id = $record['id'];
                $job->attempts = ((int) $record['attempts']) + 1;
                return $job;
            }
        }

        // Fallback for legacy serialized jobs
        $job = @unserialize($record['payload']);
        if ($job instanceof Job) {
            $job->id = $record['id'];
            $job->attempts = ((int) $record['attempts']) + 1;
            return $job;
        }

        return null;
    }

    public function delete(Job $job): bool
    {
        if ($job->id === null) {
            return false;
        }
        return $this->connection->statement("DELETE FROM {$this->table} WHERE id = ?", [$job->id]);
    }
}
