<?php
declare(strict_types=1);

namespace Nexus\Database;

/**
 * Programmatic Schema Management Facade
 */
class Schema
{
    protected static ?Connection $connection = null;

    public static function setConnection(Connection $connection): void
    {
        static::$connection = $connection;
    }

    public static function getConnection(): Connection
    {
        if (static::$connection === null) {
            $resolver = Model::getConnectionResolver();
            if ($resolver !== null) {
                static::$connection = $resolver;
            } else {
                throw new \RuntimeException("Schema Connection has not been initialized.");
            }
        }
        return static::$connection;
    }

    public static function create(string $table, \Closure $callback): void
    {
        $conn = static::getConnection();
        $driver = $conn->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $blueprint = new Blueprint($table, (string)$driver);
        $callback($blueprint);
        $conn->statement($blueprint->toSql());
    }

    public static function dropIfExists(string $table): void
    {
        $conn = static::getConnection();
        $conn->statement("DROP TABLE IF EXISTS {$table}");
    }
}
