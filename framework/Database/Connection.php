<?php
declare(strict_types=1);

namespace Nexus\Database;

/**
 * PDO Connection Abstraction Wrapper
 */
class Connection
{
    protected \PDO $pdo;

    public function __construct(array|\PDO $config)
    {
        if ($config instanceof \PDO) {
            $this->pdo = $config;
            return;
        }

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

    protected static array $queryListeners = [];

    public static function listen(callable $callback): void
    {
        static::$queryListeners[] = $callback;
    }

    protected function logQuery(string $query, array $bindings, float $duration): void
    {
        foreach (static::$queryListeners as $listener) {
            $listener($query, $bindings, $duration);
        }
    }

    public function select(string $query, array $bindings = []): array
    {
        $start = microtime(true);
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($bindings);
            $result = $stmt->fetchAll();
            $this->logQuery($query, $bindings, (microtime(true) - $start) * 1000);
            return $result;
        } catch (\PDOException $e) {
            throw new \Nexus\Database\Exceptions\QueryException(
                "Database Query Error: {$e->getMessage()}\nSQL: {$query}\nBindings: " . json_encode($bindings),
                (int) $e->getCode(),
                $e
            );
        }
    }

    public function statement(string $query, array $bindings = []): bool
    {
        $start = microtime(true);
        try {
            $stmt = $this->pdo->prepare($query);
            $result = $stmt->execute($bindings);
            $this->logQuery($query, $bindings, (microtime(true) - $start) * 1000);
            return $result;
        } catch (\PDOException $e) {
            throw new \Nexus\Database\Exceptions\QueryException(
                "Database Execution Error: {$e->getMessage()}\nSQL: {$query}\nBindings: " . json_encode($bindings),
                (int) $e->getCode(),
                $e
            );
        }
    }

    public function affectingStatement(string $query, array $bindings = []): int
    {
        $start = microtime(true);
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($bindings);
            $count = $stmt->rowCount();
            $this->logQuery($query, $bindings, (microtime(true) - $start) * 1000);
            return $count;
        } catch (\PDOException $e) {
            throw new \Nexus\Database\Exceptions\QueryException(
                "Database Execution Error: {$e->getMessage()}\nSQL: {$query}\nBindings: " . json_encode($bindings),
                (int) $e->getCode(),
                $e
            );
        }
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }
}
