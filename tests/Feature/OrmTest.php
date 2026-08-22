<?php
declare(strict_types=1);

use Nexus\Database\Connection;
use Nexus\Database\QueryBuilder;

class OrmTest
{
    public function testDatabaseQueryBuilding(): void
    {
        $conn = new Connection(['driver' => 'sqlite', 'database' => ':memory:']);
        $conn->statement("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT);");
        $conn->statement("INSERT INTO users (name) VALUES ('Alice'), ('Bob');");

        $builder = new QueryBuilder($conn);
        $results = $builder->table('users')->where('name', '=', 'Alice')->get();

        if (count($results) !== 1) {
            throw new \RuntimeException("ORM query builder failed to fetch single row.");
        }

        if ($results[0]['name'] !== 'Alice') {
            throw new \RuntimeException("ORM query builder returned wrong row content.");
        }
    }
}
