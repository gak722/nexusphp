<?php
declare(strict_types=1);

namespace Nexus\Database\Grammar;

class MySqlGrammar extends BaseGrammar
{
    protected string $wrapper = '`';

    protected function compileColumn(array $col): string
    {
        $name = $this->wrapIdentifier($col['name']);
        $type = strtoupper($col['type']);
        
        if ($col['autoIncrement'] ?? false) {
            return "{$name} INT AUTO_INCREMENT PRIMARY KEY";
        }

        $typeSql = match ($type) {
            'STRING', 'VARCHAR' => "VARCHAR(" . ($col['length'] ?? 255) . ")",
            'INTEGER', 'INT' => "INT",
            'BIGINT' => "BIGINT",
            'TEXT' => "TEXT",
            'BOOLEAN', 'BOOL' => "TINYINT(1)",
            'DATETIME', 'TIMESTAMP' => "DATETIME",
            'DECIMAL' => "DECIMAL(" . ($col['precision'] ?? 10) . ", " . ($col['scale'] ?? 2) . ")",
            'JSON' => "JSON",
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
        $onDelete = !empty($fk['onDelete']) ? " ON DELETE " . strtoupper($fk['onDelete']) : '';
        $onUpdate = !empty($fk['onUpdate']) ? " ON UPDATE " . strtoupper($fk['onUpdate']) : '';

        return "FOREIGN KEY ({$column}) REFERENCES {$foreignTable}({$foreignColumn}){$onDelete}{$onUpdate}";
    }

    protected function quoteValue(mixed $val): string
    {
        if (is_numeric($val)) return (string)$val;
        if (is_bool($val)) return $val ? '1' : '0';
        return "'" . addslashes((string)$val) . "'";
    }
}
