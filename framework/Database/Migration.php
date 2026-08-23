<?php
declare(strict_types=1);

namespace Nexus\Database;

use Nexus\Database\Grammar\GrammarInterface;
use Nexus\Database\Grammar\MySqlGrammar;
use Nexus\Database\Grammar\PostgreSqlGrammar;
use Nexus\Database\Grammar\SqliteGrammar;
use Nexus\Database\Schema\TableBuilder;

abstract class Migration
{
    protected ?GrammarInterface $grammar = null;

    public function setGrammar(GrammarInterface $grammar): void
    {
        $this->grammar = $grammar;
    }

    public function createTable(string $table, \Closure $callback): void
    {
        $conn = Schema::getConnection();
        $driver = $conn->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $grammar = $this->grammar ?? match ($driver) {
            'pgsql' => new PostgreSqlGrammar(),
            'sqlite' => new SqliteGrammar(),
            default => new MySqlGrammar(),
        };

        $builder = new TableBuilder($table, $grammar);
        $callback($builder);

        $sql = $grammar->compileCreateTable($builder->toBlueprintArray());
        $conn->statement($sql);
    }

    public function dropTable(string $table): void
    {
        $conn = Schema::getConnection();
        $driver = $conn->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $grammar = $this->grammar ?? match ($driver) {
            'pgsql' => new PostgreSqlGrammar(),
            'sqlite' => new SqliteGrammar(),
            default => new MySqlGrammar(),
        };

        $sql = $grammar->compileDropTable($table, true);
        $conn->statement($sql);
    }

    abstract public function up(): void;
    abstract public function down(): void;
}
