# Phase 5: Schema Builder & Migration Runner

**Duration:** Week 7

---

## 1. What to Build

Phase 5 creates the database DDL schema building engine and version-controlled migration management system. It allows developers to programmatically create, alter, and drop tables via fluent blueprints without raw SQL strings.

### Core Deliverables:

- **`framework/Database/Blueprint.php`** — Fluent programmatic representation of database table schema DDL (columns, data types, indexes, foreign keys).
- **`framework/Database/Schema.php`** — Static facade for table management (`create()`, `drop()`, `dropIfExists()`, `table()`).
- **`framework/Database/Migration.php`** — Abstract base class defining `up()` and `down()` migration lifecycle methods.
- **`framework/Database/Migrator.php`** — CLI migration runner tracking applied migrations in a `migrations` database table with batch tracking and rollback capability.
- **`framework/Database/Seeder.php`** — Base class for database seeders.

---

## 2. How Current Implementation Fits with Previous Phase Implementation

- **Database Connection Integration:** `Schema` and `Migrator` utilize Phase 4's `Connection` to execute raw DDL statements and manage migration state tables.
- **CLI Integration:** Commands are executed via the Nexus CLI runner established in later phases or directly through bootstrap scripts.

---

## 3. How to Build

### Step-by-Step Implementation:

1. **`framework/Database/Blueprint.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Database;

   class Blueprint
   {
       protected array $columns = [];
       protected array $commands = [];

       public function __construct(public readonly string $table) {}

       public function id(string $name = 'id'): static
       {
           $this->columns[] = "{$name} BIGINT AUTO_INCREMENT PRIMARY KEY";
           return $this;
       }

       public function string(string $name, int $length = 255): static
       {
           $this->columns[] = "{$name} VARCHAR({$length}) NOT NULL";
           return $this;
       }

       public function text(string $name): static
       {
           $this->columns[] = "{$name} TEXT NOT NULL";
           return $this;
       }

       public function integer(string $name): static
       {
           $this->columns[] = "{$name} INT NOT NULL";
           return $this;
       }

       public function timestamps(): static
       {
           $this->columns[] = "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
           $this->columns[] = "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
           return $this;
       }

       public function toSql(): string
       {
           $cols = implode(", ", $this->columns);
           return "CREATE TABLE {$this->table} ({$cols}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
       }
   }
   ```

2. **`framework/Database/Schema.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Database;

   class Schema
   {
       protected static ?Connection $connection = null;

       public static function setConnection(Connection $connection): void
       {
           static::$connection = $connection;
       }

       public static function create(string $table, \Closure $callback): void
       {
           $blueprint = new Blueprint($table);
           $callback($blueprint);
           static::$connection->statement($blueprint->toSql());
       }

       public static function dropIfExists(string $table): void
       {
           static::$connection->statement("DROP TABLE IF EXISTS {$table}");
       }
   }
   ```

3. **`framework/Database/Migration.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Database;

   abstract class Migration
   {
       abstract public function up(): void;
       abstract public function down(): void;
   }
   ```

4. **`framework/Database/Migrator.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Database;

   class Migrator
   {
       public function __construct(protected Connection $connection, protected string $migrationPath)
       {
           $this->ensureMigrationTable();
       }

       protected function ensureMigrationTable(): void
       {
           $sql = "CREATE TABLE IF NOT EXISTS migrations (
               id INT AUTO_INCREMENT PRIMARY KEY,
               migration VARCHAR(255) NOT NULL,
               batch INT NOT NULL,
               created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
           );";
           $this->connection->statement($sql);
       }

       public function run(): array
       {
           $ran = $this->getRan();
           $files = glob($this->migrationPath . '/*.php');
           $batch = $this->getNextBatchNumber();
           $executed = [];

           foreach ($files as $file) {
               $migrationName = basename($file, '.php');
               if (in_array($migrationName, $ran)) continue;

               require_once $file;
               $className = $this->resolveClassName($migrationName);
               $migration = new $className();
               $migration->up();

               $this->log($migrationName, $batch);
               $executed[] = $migrationName;
           }

           return $executed;
       }

       protected function getRan(): array
       {
           $rows = $this->connection->select("SELECT migration FROM migrations");
           return array_column($rows, 'migration');
       }

       protected function getNextBatchNumber(): int
       {
           $row = $this->connection->select("SELECT MAX(batch) as max_batch FROM migrations");
           return ((int) ($row[0]['max_batch'] ?? 0)) + 1;
       }

       protected function log(string $migration, int $batch): void
       {
           $this->connection->statement("INSERT INTO migrations (migration, batch) VALUES (?, ?)", [$migration, $batch]);
       }

       protected function resolveClassName(string $filename): string
       {
           $parts = explode('_', $filename);
           array_shift($parts); // remove timestamp index if present
           $name = implode('_', $parts);
           return \Nexus\Support\Str::studly($name);
       }
   }
   ```

---

## 4. Success Criteria

- [ ] Blueprint builds syntactically valid DDL for MySQL, PostgreSQL, and SQLite.
- [ ] `migrations` table is automatically initialized if absent.
- [ ] `Migrator::run()` tracks batch numbers and prevents double-running migrations.
- [ ] Migration rollback properly drops tables or reverts DDL changes in reverse order.
