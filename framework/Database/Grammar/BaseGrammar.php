<?php
declare(strict_types=1);

namespace Nexus\Database\Grammar;

abstract class BaseGrammar implements GrammarInterface
{
    protected string $wrapper = '"';

    public function wrapIdentifier(string $value): string
    {
        if ($value === '*') {
            return '*';
        }
        if (str_contains($value, '.')) {
            $segments = explode('.', $value);
            return implode('.', array_map([$this, 'wrapIdentifier'], $segments));
        }
        return $this->wrapper . str_replace($this->wrapper, $this->wrapper . $this->wrapper, $value) . $this->wrapper;
    }

    public function compileSelect(array $queryParts): string
    {
        $sql = [];
        $sql[] = "SELECT " . ($queryParts['distinct'] ? 'DISTINCT ' : '') . implode(', ', $queryParts['columns']);
        $sql[] = "FROM " . $this->wrapIdentifier($queryParts['table']);

        if (!empty($queryParts['joins'])) {
            foreach ($queryParts['joins'] as $join) {
                $sql[] = strtoupper($join['type']) . " JOIN " . $this->wrapIdentifier($join['table'])
                    . " ON " . $this->wrapIdentifier($join['first']) . " " . $join['operator'] . " " . $this->wrapIdentifier($join['second']);
            }
        }

        if (!empty($queryParts['where'])) {
            $sql[] = "WHERE " . $queryParts['where'];
        }

        if (!empty($queryParts['groupBy'])) {
            $sql[] = "GROUP BY " . implode(', ', array_map([$this, 'wrapIdentifier'], $queryParts['groupBy']));
        }

        if (!empty($queryParts['having'])) {
            $sql[] = "HAVING " . $queryParts['having'];
        }

        if (!empty($queryParts['orderBy'])) {
            $orders = [];
            foreach ($queryParts['orderBy'] as $order) {
                $orders[] = $this->wrapIdentifier($order['column']) . " " . strtoupper($order['direction']);
            }
            $sql[] = "ORDER BY " . implode(', ', $orders);
        }

        if ($queryParts['limit'] !== null) {
            $sql[] = "LIMIT " . (int) $queryParts['limit'];
        }

        if ($queryParts['offset'] !== null) {
            $sql[] = "OFFSET " . (int) $queryParts['offset'];
        }

        return implode(' ', $sql);
    }

    public function compileInsert(string $table, array $columns): string
    {
        $wrappedTable = $this->wrapIdentifier($table);
        $wrappedColumns = implode(', ', array_map([$this, 'wrapIdentifier'], $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        return "INSERT INTO {$wrappedTable} ({$wrappedColumns}) VALUES ({$placeholders})";
    }

    public function compileUpdate(string $table, array $columns, string $whereSql): string
    {
        $wrappedTable = $this->wrapIdentifier($table);
        $sets = [];
        foreach ($columns as $col) {
            $sets[] = $this->wrapIdentifier($col) . " = ?";
        }
        $setSql = implode(', ', $sets);
        $whereClause = !empty($whereSql) ? " WHERE {$whereSql}" : '';

        return "UPDATE {$wrappedTable} SET {$setSql}{$whereClause}";
    }

    public function compileDelete(string $table, string $whereSql): string
    {
        $wrappedTable = $this->wrapIdentifier($table);
        $whereClause = !empty($whereSql) ? " WHERE {$whereSql}" : '';

        return "DELETE FROM {$wrappedTable}{$whereClause}";
    }

    public function compileCreateTable(array $tableBlueprint): string
    {
        $tableName = $this->wrapIdentifier($tableBlueprint['name']);
        $columnDefs = [];

        foreach ($tableBlueprint['columns'] as $col) {
            $columnDefs[] = $this->compileColumn($col);
        }

        if (!empty($tableBlueprint['primaryKeys'])) {
            $pkCols = implode(', ', array_map([$this, 'wrapIdentifier'], $tableBlueprint['primaryKeys']));
            $columnDefs[] = "PRIMARY KEY ({$pkCols})";
        }

        if (!empty($tableBlueprint['indexes'])) {
            foreach ($tableBlueprint['indexes'] as $idx) {
                $type = $idx['type'] === 'UNIQUE' ? 'UNIQUE ' : '';
                $idxCols = implode(', ', array_map([$this, 'wrapIdentifier'], $idx['columns']));
                $idxName = $idx['name'] ? $this->wrapIdentifier($idx['name']) . ' ' : '';
                $columnDefs[] = "{$type}INDEX {$idxName}({$idxCols})";
            }
        }

        foreach ($tableBlueprint['foreignKeys'] as $fk) {
            $columnDefs[] = $this->compileForeignKey($fk);
        }

        $body = implode(",\n  ", $columnDefs);
        $ifNotExists = ($tableBlueprint['ifNotExists'] ?? true) ? 'IF NOT EXISTS ' : '';

        return "CREATE TABLE {$ifNotExists}{$tableName} (\n  {$body}\n)";
    }

    public function compileDropTable(string $table, bool $ifExists = true): string
    {
        $wrapped = $this->wrapIdentifier($table);
        $clause = $ifExists ? 'IF EXISTS ' : '';
        return "DROP TABLE {$clause}{$wrapped}";
    }

    abstract protected function compileColumn(array $column): string;
    abstract protected function compileForeignKey(array $fk): string;

    public function supportsSavepoints(): bool
    {
        return true;
    }

    public function supportsTransactionalDdl(): bool
    {
        return false;
    }
}
