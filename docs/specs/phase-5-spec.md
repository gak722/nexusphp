# Copilot Spec: Phase 5 — Schema DDL Builder & Versioned Migrations

## Objective
Implement programmatic database schema definition (`Blueprint`), table management facade (`Schema`), abstract `Migration` base class, and version-tracked CLI `Migrator`.

## Target Files to Create / Modify
- `framework/Database/Blueprint.php`
- `framework/Database/Schema.php`
- `framework/Database/Migration.php`
- `framework/Database/Migrator.php`
- `framework/Database/Seeder.php`

---

## Detailed Specifications

### 1. `framework/Database/Blueprint.php`
- Column builders: `id()`, `string()`, `text()`, `integer()`, `bigInteger()`, `boolean()`, `timestamps()`, `foreignId()`.
- Generates standard SQL DDL string (`CREATE TABLE ...`).

### 2. `framework/Database/Schema.php`
- `create(string $table, \Closure $callback): void`
- `dropIfExists(string $table): void`

### 3. `framework/Database/Migrator.php`
- Automatically creates `migrations` metadata table if not present (`id`, `migration`, `batch`, `created_at`).
- `run(): array` scans migration directory, filters applied migrations, increments batch number, and calls `up()`.

---

## Copilot Validation Rules
- [ ] Migrations MUST run in chronological order based on filename timestamps.
- [ ] Schema operations MUST execute within PDO transactions where supported by database driver.
