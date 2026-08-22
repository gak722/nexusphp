<?php
declare(strict_types=1);

namespace Nexus\Database;

/**
 * Fluent Parameter-Bound SQL Query Builder
 */
class QueryBuilder
{
    protected string $table = '';
    protected array $columns = ['*'];
    protected array $wheres = [];
    protected array $bindings = [];
    protected array $orders = [];
    protected ?int $limit = null;
    protected ?int $offset = null;

    public function __construct(protected Connection $connection) {}

    public function table(string $table): static
    {
        $this->table = $this->sanitizeIdentifier($table);
        return $this;
    }

    public function select(array $columns = ['*']): static
    {
        $this->columns = array_map(fn($col) => $col === '*' ? '*' : $this->sanitizeIdentifier($col), $columns);
        return $this;
    }

    public function where(string $column, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $column = $this->sanitizeIdentifier($column);
        $operator = $this->sanitizeOperator((string) $operator);
        $boolean = empty($this->wheres) ? 'WHERE' : 'AND';
        $this->wheres[] = "{$boolean} {$column} {$operator} ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function orWhere(string $column, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $column = $this->sanitizeIdentifier($column);
        $operator = $this->sanitizeOperator((string) $operator);
        $boolean = empty($this->wheres) ? 'WHERE' : 'OR';
        $this->wheres[] = "{$boolean} {$column} {$operator} ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $column = $this->sanitizeIdentifier($column);
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = "{$column} {$direction}";
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = max(0, $limit);
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = max(0, $offset);
        return $this;
    }

    public function toSql(): string
    {
        $table = !empty($this->table) ? $this->table : 'dual';
        $sql = "SELECT " . implode(', ', $this->columns) . " FROM {$table}";

        if (!empty($this->wheres)) {
            $sql .= " " . implode(' ', $this->wheres);
        }

        if (!empty($this->orders)) {
            $sql .= " ORDER BY " . implode(', ', $this->orders);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    public function get(): array
    {
        return $this->connection->select($this->toSql(), $this->bindings);
    }

    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    public function insert(array $values): bool
    {
        $sanitizedCols = array_map([$this, 'sanitizeIdentifier'], array_keys($values));
        $columns = implode(', ', $sanitizedCols);
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        return $this->connection->statement($sql, array_values($values));
    }

    public function update(array $values, array $whereBindings = []): int
    {
        $sets = [];
        $bindings = [];
        foreach ($values as $col => $val) {
            $col = $this->sanitizeIdentifier($col);
            $sets[] = "{$col} = ?";
            $bindings[] = $val;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);

        if (!empty($this->wheres)) {
            $sql .= " " . implode(' ', $this->wheres);
            $bindings = array_merge($bindings, $this->bindings);
        }

        return $this->connection->affectingStatement($sql, $bindings);
    }

    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= " " . implode(' ', $this->wheres);
        }

        return $this->connection->affectingStatement($sql, $this->bindings);
    }

    protected function sanitizeIdentifier(string $identifier): string
    {
        if ($identifier === '*') {
            return '*';
        }

        // Allow alphanumeric, underscores, and dots (table.column)
        $clean = preg_replace('/[^a-zA-Z0-9_\.]/', '', $identifier);
        return $clean ?: '`unknown`';
    }

    protected function sanitizeOperator(string $operator): string
    {
        $allowed = ['=', '!=', '<>', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN'];
        $upper = strtoupper(trim($operator));
        return in_array($upper, $allowed, true) ? $upper : '=';
    }
}
