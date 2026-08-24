<?php
declare(strict_types=1);

namespace Nexus\Database;

/**
 * Medoo-Inspired Fluent Parameter-Bound SQL Query Builder Engine
 */
class QueryBuilder
{
    protected string $table = '';
    protected array $columns = ['*'];
    protected array $wheres = [];
    protected array $bindings = [];
    protected array $orders = [];
    protected array $joins = [];
    protected array $eagerLoad = [];
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

    public function whereIn(string $column, array $values): static
    {
        if (empty($values)) {
            $boolean = empty($this->wheres) ? 'WHERE' : 'AND';
            $this->wheres[] = "{$boolean} 0 = 1";
            return $this;
        }

        $column = $this->sanitizeIdentifier($column);
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $boolean = empty($this->wheres) ? 'WHERE' : 'AND';
        
        $this->wheres[] = "{$boolean} {$column} IN ({$placeholders})";
        foreach ($values as $val) {
            $this->bindings[] = $val;
        }
        return $this;
    }

    public function whereNotIn(string $column, array $values): static
    {
        if (empty($values)) {
            return $this;
        }

        $column = $this->sanitizeIdentifier($column);
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $boolean = empty($this->wheres) ? 'WHERE' : 'AND';

        $this->wheres[] = "{$boolean} {$column} NOT IN ({$placeholders})";
        foreach ($values as $val) {
            $this->bindings[] = $val;
        }
        return $this;
    }

    public function whereNull(string $column): static
    {
        $column = $this->sanitizeIdentifier($column);
        $boolean = empty($this->wheres) ? 'WHERE' : 'AND';
        $this->wheres[] = "{$boolean} {$column} IS NULL";
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $column = $this->sanitizeIdentifier($column);
        $boolean = empty($this->wheres) ? 'WHERE' : 'AND';
        $this->wheres[] = "{$boolean} {$column} IS NOT NULL";
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): static
    {
        $table = $this->sanitizeIdentifier($table);
        $first = $this->sanitizeIdentifier($first);
        $second = $this->sanitizeIdentifier($second);
        $operator = $this->sanitizeOperator($operator);
        $type = strtoupper($type);
        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT', 'CROSS'], true)) {
            $type = 'INNER';
        }

        $this->joins[] = "{$type} JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
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

    public function with(array|string $relations): static
    {
        $relations = is_string($relations) ? [$relations] : $relations;
        $this->eagerLoad = array_merge($this->eagerLoad, $relations);
        return $this;
    }

    public function getEagerLoads(): array
    {
        return $this->eagerLoad;
    }

    public function toSql(): string
    {
        $table = !empty($this->table) ? $this->table : 'dual';
        $sql = "SELECT " . implode(', ', $this->columns) . " FROM {$table}";

        if (!empty($this->joins)) {
            $sql .= " " . implode(' ', $this->joins);
        }

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

    public function count(string $column = '*'): int
    {
        $col = $column === '*' ? '*' : $this->sanitizeIdentifier($column);
        $savedCols = $this->columns;
        $this->columns = ["COUNT({$col}) as aggregate"];
        $results = $this->get();
        $this->columns = $savedCols;

        return (int) ($results[0]['aggregate'] ?? 0);
    }

    public function sum(string $column): float|int
    {
        $col = $this->sanitizeIdentifier($column);
        $savedCols = $this->columns;
        $this->columns = ["SUM({$col}) as aggregate"];
        $results = $this->get();
        $this->columns = $savedCols;

        return $results[0]['aggregate'] !== null ? (float) $results[0]['aggregate'] : 0;
    }

    public function avg(string $column): float
    {
        $col = $this->sanitizeIdentifier($column);
        $savedCols = $this->columns;
        $this->columns = ["AVG({$col}) as aggregate"];
        $results = $this->get();
        $this->columns = $savedCols;

        return $results[0]['aggregate'] !== null ? (float) $results[0]['aggregate'] : 0.0;
    }

    public function min(string $column): mixed
    {
        $col = $this->sanitizeIdentifier($column);
        $savedCols = $this->columns;
        $this->columns = ["MIN({$col}) as aggregate"];
        $results = $this->get();
        $this->columns = $savedCols;

        $val = $results[0]['aggregate'] ?? null;
        if ($val === null) {
            return null;
        }
        if (is_numeric($val)) {
            return $val + 0; // cast to int/float appropriately
        }
        return $val;
    }

    public function max(string $column): mixed
    {
        $col = $this->sanitizeIdentifier($column);
        $savedCols = $this->columns;
        $this->columns = ["MAX({$col}) as aggregate"];
        $results = $this->get();
        $this->columns = $savedCols;

        $val = $results[0]['aggregate'] ?? null;
        if ($val === null) {
            return null;
        }
        if (is_numeric($val)) {
            return $val + 0;
        }
        return $val;
    }

    public function insert(array $values): bool
    {
        $sanitizedCols = array_map([$this, 'sanitizeIdentifier'], array_keys($values));
        $columns = implode(', ', $sanitizedCols);
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        return $this->connection->statement($sql, array_values($values));
    }

    public function update(array $values): int
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
