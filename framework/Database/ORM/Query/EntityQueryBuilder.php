<?php
declare(strict_types=1);

namespace Nexus\Database\ORM\Query;

use Nexus\Database\Connection;
use Nexus\Database\Grammar\GrammarInterface;
use Nexus\Database\Grammar\MySqlGrammar;
use Nexus\Database\Grammar\PostgreSqlGrammar;
use Nexus\Database\Grammar\SqliteGrammar;
use Nexus\Database\ORM\DbContext;
use Nexus\Database\ORM\Mapping\MetadataFactory;
use Nexus\Database\ORM\Tracking\EntityState;

class EntityQueryBuilder
{
    protected string $entityClass;
    protected Connection $connection;
    protected GrammarInterface $grammar;
    protected ?DbContext $context;

    protected array $columns = ['*'];
    protected bool $distinct = false;
    protected array $wheres = [];
    protected array $bindings = [];
    protected array $joins = [];
    protected array $orders = [];
    protected array $groupBys = [];
    protected ?string $having = null;
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected array $eagerLoads = [];
    protected bool $withTrashed = false;
    protected bool $onlyTrashed = false;

    public function __construct(string $entityClass, Connection $connection, ?DbContext $context = null)
    {
        $this->entityClass = $entityClass;
        $this->connection = $connection;
        $this->context = $context;
        $this->grammar = $this->resolveGrammar($connection);
    }

    protected function resolveGrammar(Connection $connection): GrammarInterface
    {
        $driver = $connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        return match ($driver) {
            'pgsql' => new PostgreSqlGrammar(),
            'sqlite' => new SqliteGrammar(),
            default => new MySqlGrammar(),
        };
    }

    public function select(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    public function where(string $column, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $metadata = MetadataFactory::getMetadata($this->entityClass);
        $colName = $metadata->properties[$column]->columnName ?? $column;

        $this->wheres[] = [
            'type' => 'basic',
            'sql' => $this->grammar->wrapIdentifier($colName) . " {$operator} ?",
            'boolean' => 'AND'
        ];
        $this->bindings[] = $value;
        return $this;
    }

    public function orWhere(string $column, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $metadata = MetadataFactory::getMetadata($this->entityClass);
        $colName = $metadata->properties[$column]->columnName ?? $column;

        $this->wheres[] = [
            'type' => 'basic',
            'sql' => $this->grammar->wrapIdentifier($colName) . " {$operator} ?",
            'boolean' => 'OR'
        ];
        $this->bindings[] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        if (empty($values)) {
            $this->wheres[] = ['type' => 'raw', 'sql' => '1 = 0', 'boolean' => 'AND'];
            return $this;
        }

        $metadata = MetadataFactory::getMetadata($this->entityClass);
        $colName = $metadata->properties[$column]->columnName ?? $column;
        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        $this->wheres[] = [
            'type' => 'basic',
            'sql' => $this->grammar->wrapIdentifier($colName) . " IN ({$placeholders})",
            'boolean' => 'AND'
        ];
        foreach ($values as $val) {
            $this->bindings[] = $val;
        }
        return $this;
    }

    public function whereNull(string $column): static
    {
        $metadata = MetadataFactory::getMetadata($this->entityClass);
        $colName = $metadata->properties[$column]->columnName ?? $column;
        $this->wheres[] = ['type' => 'raw', 'sql' => $this->grammar->wrapIdentifier($colName) . ' IS NULL', 'boolean' => 'AND'];
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $metadata = MetadataFactory::getMetadata($this->entityClass);
        $colName = $metadata->properties[$column]->columnName ?? $column;
        $this->wheres[] = ['type' => 'raw', 'sql' => $this->grammar->wrapIdentifier($colName) . ' IS NOT NULL', 'boolean' => 'AND'];
        return $this;
    }

    public function whereRaw(string $sql, array $bindings = []): static
    {
        $this->wheres[] = ['type' => 'raw', 'sql' => $sql, 'boolean' => 'AND'];
        foreach ($bindings as $b) {
            $this->bindings[] = $b;
        }
        return $this;
    }

    public function with(string|array $relations): static
    {
        $rels = is_array($relations) ? $relations : func_get_args();
        foreach ($rels as $rel) {
            $this->eagerLoads[] = $rel;
        }
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $metadata = MetadataFactory::getMetadata($this->entityClass);
        $colName = $metadata->properties[$column]->columnName ?? $column;
        $this->orders[] = ['column' => $colName, 'direction' => $direction];
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'inner'): static
    {
        $this->joins[] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => $type,
        ];
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    public function whereBetween(string $column, array $values): static
    {
        $metadata = MetadataFactory::getMetadata($this->entityClass);
        $colName = $metadata->properties[$column]->columnName ?? $column;
        $this->wheres[] = [
            'type' => 'basic',
            'sql' => $this->grammar->wrapIdentifier($colName) . " BETWEEN ? AND ?",
            'boolean' => 'AND'
        ];
        $this->bindings[] = $values[0];
        $this->bindings[] = $values[1];
        return $this;
    }

    public function groupBy(string|array $groups): static
    {
        $cols = is_array($groups) ? $groups : func_get_args();
        foreach ($cols as $c) {
            $this->groupBys[] = $c;
        }
        return $this;
    }

    public function having(string $sql, array $bindings = []): static
    {
        $this->having = $sql;
        foreach ($bindings as $b) {
            $this->bindings[] = $b;
        }
        return $this;
    }

    public function chunk(int $count, callable $callback): bool
    {
        $page = 1;
        do {
            $results = (clone $this)->offset(($page - 1) * $count)->limit($count)->get();
            $countResults = count($results);
            if ($countResults === 0) {
                break;
            }
            if ($callback($results, $page) === false) {
                return false;
            }
            $page++;
        } while ($countResults === $count);

        return true;
    }

    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $total = (clone $this)->count();
        $results = (clone $this)->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return [
            'data' => $results,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    public function withTrashed(): static
    {
        $this->withTrashed = true;
        return $this;
    }

    public function onlyTrashed(): static
    {
        $this->onlyTrashed = true;
        $this->withTrashed = true;
        return $this;
    }

    public function getSql(): string
    {
        $metadata = MetadataFactory::getMetadata($this->entityClass);
        $whereParts = [];

        if ($metadata->softDeleteColumn !== null && !$this->withTrashed) {
            $whereParts[] = $this->grammar->wrapIdentifier($metadata->softDeleteColumn) . ' IS NULL';
        } elseif ($metadata->softDeleteColumn !== null && $this->onlyTrashed) {
            $whereParts[] = $this->grammar->wrapIdentifier($metadata->softDeleteColumn) . ' IS NOT NULL';
        }

        foreach ($this->wheres as $i => $w) {
            $prefix = ($i === 0 && empty($whereParts)) ? '' : $w['boolean'] . ' ';
            $whereParts[] = $prefix . $w['sql'];
        }

        $whereSql = implode(' ', $whereParts);

        return $this->grammar->compileSelect([
            'distinct' => $this->distinct,
            'columns' => $this->columns,
            'table' => $metadata->tableName,
            'joins' => $this->joins,
            'where' => $whereSql,
            'groupBy' => $this->groupBys,
            'having' => $this->having,
            'orderBy' => $this->orders,
            'limit' => $this->limit,
            'offset' => $this->offset,
        ]);
    }

    public function get(): \Nexus\Support\Collection
    {
        $sql = $this->getSql();
        $rows = $this->connection->select($sql, $this->bindings);
        $entities = [];
        $metadata = MetadataFactory::getMetadata($this->entityClass);

        foreach ($rows as $row) {
            $entity = $this->hydrate($row, $metadata);
            $entities[] = $entity;
        }

        if (!empty($this->eagerLoads) && !empty($entities)) {
            $this->loadRelations($entities);
        }

        return \Nexus\Support\Collection::make($entities);
    }

    public function first(): ?object
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    public function firstOrFail(): object
    {
        $result = $this->first();
        if (!$result) {
            throw new \Nexus\Database\Exceptions\ModelException("Entity {$this->entityClass} not found for query.");
        }
        return $result;
    }

    public function count(): int
    {
        $this->columns = ['COUNT(*) as aggregate_count'];
        $sql = $this->getSql();
        $row = $this->connection->select($sql, $this->bindings);
        return (int) ($row[0]['aggregate_count'] ?? 0);
    }

    protected function hydrate(array $row, $metadata): object
    {
        $pkCol = $metadata->primaryKeyColumn;
        $idVal = $row[$pkCol] ?? null;

        if ($idVal !== null && $this->context !== null) {
            $existing = $this->context->getChangeTracker()->findInIdentityMap($this->entityClass, $idVal);
            if ($existing) {
                return $existing;
            }
        }

        $ref = new \ReflectionClass($this->entityClass);
        $entity = $ref->newInstanceWithoutConstructor();

        foreach ($metadata->properties as $prop) {
            if (isset($row[$prop->columnName])) {
                $rawVal = $row[$prop->columnName];
                $typedVal = $this->castValue($rawVal, $prop->phpType);
                $reflectionProp = $ref->getProperty($prop->propertyName);
                $reflectionProp->setValue($entity, $typedVal);
            }
        }

        if ($idVal !== null && $this->context !== null) {
            $this->context->getChangeTracker()->registerInIdentityMap($this->entityClass, $idVal, $entity);
            $this->context->getChangeTracker()->track($entity, EntityState::Unchanged);
        }

        return $entity;
    }

    protected function castValue(mixed $val, ?string $type): mixed
    {
        if ($val === null || $type === null) return $val;
        return match ($type) {
            'int' => (int) $val,
            'float' => (float) $val,
            'bool' => (bool) $val,
            'string' => (string) $val,
            '\DateTimeImmutable', 'DateTimeImmutable' => new \DateTimeImmutable((string)$val),
            '\DateTime', 'DateTime' => new \DateTime((string)$val),
            default => $val,
        };
    }

    protected function loadRelations(array|\Nexus\Support\Collection $entities): void
    {
        $entitiesArray = $entities instanceof \Nexus\Support\Collection ? $entities->all() : $entities;
        $metadata = MetadataFactory::getMetadata($this->entityClass);
        $refClass = new \ReflectionClass($this->entityClass);

        foreach ($this->eagerLoads as $relationName) {
            $prop = $metadata->properties[$relationName] ?? null;
            $rel = $prop?->relation;

            if (!$rel && $refClass->hasProperty($relationName)) {
                $refProp = $refClass->getProperty($relationName);
                foreach ($refProp->getAttributes() as $attr) {
                    $inst = $attr->newInstance();
                    if ($inst instanceof \Nexus\Database\ORM\Attributes\HasMany) {
                        $rel = ['type' => 'hasMany', 'target' => $inst->targetEntity, 'foreignKey' => $inst->foreignKey ?? \Nexus\Support\Str::snake(\Nexus\Support\Str::classBasename($this->entityClass)) . '_id'];
                    } elseif ($inst instanceof \Nexus\Database\ORM\Attributes\BelongsTo) {
                        $rel = ['type' => 'belongsTo', 'target' => $inst->targetEntity, 'foreignKey' => $inst->foreignKey ?? \Nexus\Support\Str::snake(\Nexus\Support\Str::classBasename($inst->targetEntity)) . '_id'];
                    }
                }
            }

            if (!$rel) continue;
            $type = $rel['type'];
            $targetClass = $rel['target'];
            $targetMeta = MetadataFactory::getMetadata($targetClass);

            if ($type === 'hasMany') {
                $fk = $rel['foreignKey'];
                $keys = array_map(fn($e) => $e->{$metadata->primaryKeyProperty}, $entitiesArray);
                $relatedQuery = new EntityQueryBuilder($targetClass, $this->connection, $this->context);
                $relatedResults = $relatedQuery->whereIn($fk, $keys)->get();

                $grouped = [];
                foreach ($relatedResults as $r) {
                    $fkProp = $targetMeta->columnToProperty[$fk]->propertyName ?? $fk;
                    $grouped[$r->{$fkProp}][] = $r;
                }

                foreach ($entitiesArray as $e) {
                    $pkVal = $e->{$metadata->primaryKeyProperty};
                    $e->{$relationName} = $grouped[$pkVal] ?? [];
                }
            } elseif ($type === 'belongsTo') {
                $fkPropName = $metadata->columnToProperty[$rel['foreignKey']]->propertyName ?? $rel['foreignKey'];
                $keys = array_filter(array_map(fn($e) => $e->{$fkPropName} ?? null, $entitiesArray));
                
                if (!empty($keys)) {
                    $relatedQuery = new EntityQueryBuilder($targetClass, $this->connection, $this->context);
                    $relatedResults = $relatedQuery->whereIn($targetMeta->primaryKeyProperty, $keys)->get();

                    $keyed = [];
                    foreach ($relatedResults as $r) {
                        $keyed[$r->{$targetMeta->primaryKeyProperty}] = $r;
                    }

                    foreach ($entitiesArray as $e) {
                        $fkVal = $e->{$fkPropName} ?? null;
                        $e->{$relationName} = $keyed[$fkVal] ?? null;
                    }
                }
            }
        }
    }
}
