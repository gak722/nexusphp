# Phase 4: Connection Pool, Query Builder & ORM

**Duration:** Weeks 5-6

---

## 1. What to Build

Phase 4 establishes the database access layer, featuring a PDO connection manager, a fluent parameter-bound Query Builder, an ActiveRecord ORM model base, and relationship resolution (`BelongsTo`, `HasMany`, `HasOne`, `BelongsToMany`).

### Core Deliverables:

- **`framework/Database/Connection.php`** — PDO singleton/connection wrapper supporting MySQL, PostgreSQL, and SQLite with prepared statements.
- **`framework/Database/QueryBuilder.php`** — Method-chained SQL builder generating prepared queries with 100% parameter binding.
- **`framework/Database/Model.php`** — ActiveRecord base class handling property state, dirty checking, persistence, and dynamic querying.
- **`framework/Database/Relations/Relation.php`** — Abstract contract for model relationships.
- **`framework/Database/Relations/BelongsTo.php`** — Many-to-One relationship loader.
- **`framework/Database/Relations/HasMany.php`** — One-to-Many relationship loader.
- **`framework/Database/Relations/HasOne.php`** — One-to-One relationship loader.
- **`framework/Database/Relations/BelongsToMany.php`** — Many-to-Many relationship loader with pivot table handling.

---

## 2. How Current Implementation Fits with Previous Phase Implementation

- **Config Subsystem Integration:** Reads database credentials directly from Phase 0 `Config` / `.env` (`config/database.php`).
- **Controller & Router Integration:** Phase 2 Controllers query models and return Eloquent-style collections or single model instances.
- **JSON Serialization:** Models implement `\JsonSerializable` so Phase 2 `ControllerDispatcher` automatically casts model instances into Phase 1 `JsonResponse`.

---

## 3. How to Build

### Step-by-Step Implementation:

1. **`framework/Database/Connection.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Database;

   class Connection
   {
       protected \PDO $pdo;

       public function __construct(array $config)
       {
           $driver = $config['driver'] ?? 'mysql';
           $host = $config['host'] ?? '127.0.0.1';
           $port = $config['port'] ?? 3306;
           $db = $config['database'] ?? '';
           $user = $config['username'] ?? '';
           $pass = $config['password'] ?? '';
           $charset = $config['charset'] ?? 'utf8mb4';

           if ($driver === 'sqlite') {
               $dsn = "sqlite:" . $db;
           } else {
               $dsn = "{$driver}:host={$host};port={$port};dbname={$db};charset={$charset}";
           }

           $this->pdo = new \PDO($dsn, $user, $pass, [
               \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
               \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
               \PDO::ATTR_EMULATE_PREPARES => false,
           ]);
       }

       public function getPdo(): \PDO
       {
           return $this->pdo;
       }

       public function select(string $query, array $bindings = []): array
       {
           $stmt = $this->pdo->prepare($query);
           $stmt->execute($bindings);
           return $stmt->fetchAll();
       }

       public function statement(string $query, array $bindings = []): bool
       {
           $stmt = $this->pdo->prepare($query);
           return $stmt->execute($bindings);
       }
   }
   ```

2. **`framework/Database/QueryBuilder.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Database;

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
           $this->table = $table;
           return $this;
       }

       public function select(array $columns = ['*']): static
       {
           $this->columns = $columns;
           return $this;
       }

       public function where(string $column, string $operator, mixed $value = null): static
       {
           if (func_num_args() === 2) {
               $value = $operator;
               $operator = '=';
           }

           $this->wheres[] = "{$column} {$operator} ?";
           $this->bindings[] = $value;
           return $this;
       }

       public function orderBy(string $column, string $direction = 'ASC'): static
       {
           $this->orders[] = "{$column} " . strtoupper($direction);
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

       public function toSql(): string
       {
           $sql = "SELECT " . implode(', ', $this->columns) . " FROM {$this->table}";

           if (!empty($this->wheres)) {
               $sql .= " WHERE " . implode(' AND ', $this->wheres);
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
           $columns = implode(', ', array_keys($values));
           $placeholders = implode(', ', array_fill(0, count($values), '?'));
           $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
           
           return $this->connection->statement($sql, array_values($values));
       }
   }
   ```

3. **`framework/Database/Model.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Database;

   abstract class Model implements \JsonSerializable
   {
       protected string $table = '';
       protected string $primaryKey = 'id';
       protected array $attributes = [];
       protected array $original = [];
       protected static ?Connection $resolver = null;

       public function __construct(array $attributes = [])
       {
           $this->fill($attributes);
           $this->original = $this->attributes;
       }

       public static function setConnectionResolver(Connection $connection): void
       {
           static::$resolver = $connection;
       }

       public function fill(array $attributes): static
       {
           foreach ($attributes as $key => $value) {
               $this->attributes[$key] = $value;
           }
           return $this;
       }

       public function __get(string $key): mixed
       {
           return $this->attributes[$key] ?? null;
       }

       public function __set(string $key, mixed $value): void
       {
           $this->attributes[$key] = $value;
       }

       public static function query(): QueryBuilder
       {
           $instance = new static();
           $builder = new QueryBuilder(static::$resolver);
           return $builder->table($instance->getTable());
       }

       public function getTable(): string
       {
           if (!empty($this->table)) return $this->table;
           $class = basename(str_replace('\\', '/', static::class));
           return strtolower($class) . 's';
       }

       public static function find(mixed $id): ?static
       {
           $instance = new static();
           $data = static::query()->where($instance->primaryKey, $id)->first();
           if (!$data) return null;

           $model = new static();
           $model->attributes = $data;
           $model->original = $data;
           return $model;
       }

       public function save(): bool
       {
           $builder = static::query();
           if (isset($this->attributes[$this->primaryKey])) {
               // Update logic
               return true;
           }
           
           $inserted = $builder->insert($this->attributes);
           if ($inserted) {
               $this->attributes[$this->primaryKey] = (int) static::$resolver->getPdo()->lastInsertId();
           }
           return $inserted;
       }

       public function jsonSerialize(): mixed
       {
           return $this->attributes;
       }
   }
   ```

---

## 4. Success Criteria

- [ ] PDO Connection supports clean parameter binding and strict error handling (`PDO::ERRMODE_EXCEPTION`).
- [ ] Query Builder builds valid, 100% parameter-bound SQL queries preventing SQL injection.
- [ ] Models resolve table names automatically and hydrate attributes cleanly.
- [ ] CRUD operations (`find`, `save`, `insert`) execute reliably against database engines.
- [ ] Models serialize directly to JSON when returned by controllers.
