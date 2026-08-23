<?php
declare(strict_types=1);

namespace Nexus\Database\Grammar;

class SqliteGrammar extends BaseGrammar
{
    protected string $wrapper = '"';

    public function supportsTransactionalDdl(): bool
    {
        return true;
    }

    protected function compileColumn(array $col): string
    {
        $name = $this->wrapIdentifier($col['name']);
        $type = strtoupper($col['type']);

        if ($col['autoIncrement'] ?? false) {
            return "{$name} INTEGER PRIMARY KEY AUTOINCREMENT";
        }

        $typeSql = match ($type) {
            'STRING', 'VARCHAR', 'TEXT' => "TEXT",
            'INTEGER', 'INT', 'BIGINT', 'BOOLEAN', 'BOOL' => "INTEGER",
            'DATETIME', 'TIMESTAMP' => "TEXT",
            'DECIMAL' => "REAL",
            'JSON' => "TEXT",
            default => $type,
        };

        $nullSql = ($col['nullable'] ?? false) ? 'NULL' : 'NOT NULL';
        $defaultSql = isset($col['default']) ? "DEFAULT " . $this->quoteValue($col['default']) : '';
        $uniqueSql = ($col['unique'] ?? false) ? 'UNIQUE' : '';

        return trim("{$name} {$typeSql} {$nullSql} {$defaultSql} {$uniqueSql}");
    }

    protected function compileForeignKey(array $fk): string
    {
        $column = $this->wrapIdentifier($fk['column']);
        $foreignTable = $this->wrapIdentifier($fk['on']);
        $foreignColumn = $this->wrapIdentifier($fk['references']);
        $onDelete = isset($fk['onDelete']) ? " ON DELETE " . strtoupper($fk['onDelete']) : '';

        return "FOREIGN KEY ({$column}) REFERENCES {$foreignTable}({$foreignColumn}){$onDelete}";
    }

    protected function quoteValue(mixed $val): string
    {
        if (is_numeric($val)) return (string)$val;
        if (is_bool($val)) return $val ? '1' : '0';
        return "'" . addslashes((string)$val) . "'";
    }
}
