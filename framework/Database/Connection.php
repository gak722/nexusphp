<?php
declare(strict_types=1);

namespace Nexus\Database;

/**
 * PDO Connection Abstraction Wrapper
 */
class Connection
{
    protected \PDO $pdo;

    public function __construct(array $config)
    {
        $driver = $config['driver'] ?? 'mysql';
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $db = $config['database'] ?? '';
        $user = $config['username'] ?? '';
        $pass = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        if ($driver === 'sqlite') {
            $dsn = "sqlite:" . $db;
        } elseif ($driver === 'pgsql') {
            $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
        } else {
            $dsn = "{$driver}:host={$host};port={$port};dbname={$db};charset={$charset}";
        }

        try {
            $this->pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database Connection Error: " . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    public function select(string $query, array $bindings = []): array
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($bindings);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database Query Error: " . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function statement(string $query, array $bindings = []): bool
    {
        try {
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($bindings);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database Execution Error: " . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function affectingStatement(string $query, array $bindings = []): int
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($bindings);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \RuntimeException("Database Execution Error: " . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
