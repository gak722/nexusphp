# Copilot Spec: Phase 4 — Connection Pool, Query Builder & ActiveRecord ORM

## Objective
Implement PDO connection abstraction, method-chained SQL Query Builder with 100% parameter binding, ActiveRecord `Model` base class, and relationship loaders (`BelongsTo`, `HasMany`, `HasOne`, `BelongsToMany`).

## Target Files to Create / Modify
- `framework/Database/Connection.php`
- `framework/Database/QueryBuilder.php`
- `framework/Database/Model.php`
- `framework/Database/Relations/Relation.php`
- `framework/Database/Relations/BelongsTo.php`
- `framework/Database/Relations/HasMany.php`
- `framework/Database/Relations/HasOne.php`
- `framework/Database/Relations/BelongsToMany.php`

---

## Detailed Specifications

### 1. `framework/Database/Connection.php`
- Enforces `PDO::ERRMODE_EXCEPTION` and `PDO::FETCH_ASSOC`.
- `select(string $query, array $bindings = []): array`
- `statement(string $query, array $bindings = []): bool`

### 2. `framework/Database/QueryBuilder.php`
- Methods: `table()`, `select()`, `where()`, `orWhere()`, `orderBy()`, `limit()`, `offset()`, `get()`, `first()`, `insert()`, `update()`, `delete()`.
- **Constraint:** All raw user inputs MUST be bound using `?` positional parameters. Zero string concatenation of raw input allowed in SQL generation.

### 3. `framework/Database/Model.php`
- Implements `\JsonSerializable`.
- Attributes stored in `$attributes` array; dynamic getters/setters map properties to `$attributes`.
- Methods: `find(mixed $id)`, `all()`, `save()`, `delete()`, `belongsTo()`, `hasMany()`.

---

## Copilot Validation Rules
- [ ] Prepared statement bindings are strictly mandatory for all query operations.
- [ ] Model `jsonSerialize()` MUST return raw `$attributes` array.
