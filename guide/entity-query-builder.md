# Entity Query Builder

The NexusPHP `EntityQueryBuilder` is the advanced, object-oriented query compilation engine for the framework's DataMapper ORM. While the standard Query Builder returns flat arrays, the Entity Query Builder hydrates database rows directly into rich PHP objects, tracks their state via the `DbContext`, and seamlessly loads complex relationships.

> [!IMPORTANT]
> The `EntityQueryBuilder` is strictly for reading, filtering, and hydrating entities. Data manipulation (inserts, updates, deletes) is handled exclusively by the `DbContext`'s Unit of Work and Change Tracker.

---

## Getting Started

### Accessing the Entity Query Builder

The `EntityQueryBuilder` is intrinsically tied to a specific entity class and the `DbContext`. You obtain an instance by calling `query()` on the `DbContext` and passing the fully qualified class name of the entity.

```php
use Nexus\Database\ORM\DbContext;
use App\Domain\Entities\User;

$dbContext = app(DbContext::class);

// Returns an instance of EntityQueryBuilder bound to the User entity
$query = $dbContext->query(User::class);
```

---

## Building Queries

The `EntityQueryBuilder` provides a fluent interface similar to the base Query Builder but leverages database grammars (`MySqlGrammar`, `PostgreSqlGrammar`, `SqliteGrammar`) to safely compile complex SQL.

### Selects and Clauses

You can specify specific columns using `select()`, though it defaults to `['*']` to fully hydrate the entity.

```php
$query->select(['id', 'email', 'name']);
```

### WHERE Clauses

The builder supports a robust set of `where` conditions. The column names are automatically mapped from your Entity properties to their database columns via the `MetadataFactory`.

```php
// Basic Where
$query->where('status', 'active');
$query->where('age', '>=', 18);

// OR Where
$query->orWhere('role', 'admin');

// Where In
$query->whereIn('id', [1, 2, 3]);

// Null Checks
$query->whereNull('deleted_at');
$query->whereNotNull('email_verified_at');

// Where Between
$query->whereBetween('created_at', ['2023-01-01', '2023-12-31']);
```

### Raw Expressions

If you need a condition not covered by the fluent methods, use `whereRaw()`. Note that you must manually supply the parameterized bindings.

```php
$query->whereRaw('YEAR(created_at) = ?', [2023]);
```

### JOIN Clauses

Join syntax allows you to include related tables in the query structure.

```php
$query->join('user_profiles', 'users.id', '=', 'user_profiles.user_id');
$query->leftJoin('orders', 'users.id', '=', 'orders.user_id');
$query->rightJoin('roles', 'users.role_id', '=', 'roles.id');
```

### Grouping and Ordering

Unlike the base Query Builder, the `EntityQueryBuilder` explicitly supports `GROUP BY` and `HAVING` clauses, alongside robust ordering.

```php
$query->groupBy('status');
$query->having('COUNT(id) > ?', [5]);

$query->orderBy('created_at', 'desc');
```

### Limiting and Offsetting

```php
$query->limit(10)->offset(20);
```

---

## Soft Deletes

If your entity is configured with a `$softDeleteColumn` in its Metadata, the `EntityQueryBuilder` automatically scopes all queries to exclude deleted records (`deleted_at IS NULL`).

You can override this global scope natively:

```php
// Include trashed records in the results
$query->withTrashed()->get();

// Retrieve ONLY trashed records
$query->onlyTrashed()->get();
```

---

## Advanced Execution

### Fetching Results

Execution methods automatically compile the SQL via the Grammars and execute it using PDO positional placeholders.

- **`get()`**: Executes the query and returns a rich `Nexus\Support\Collection` of fully typed and hydrated entity objects.
- **`first()`**: Retrieves the first entity matching the query, or `null`.
- **`firstOrFail()`**: Retrieves the first entity, throwing a `ModelException` if none exists.
- **`count()`**: Returns the total number of records matching the query constraints as an integer.

```php
$users = $dbContext->query(User::class)->where('active', 1)->get(); // Nexus\Support\Collection

$firstUser = $dbContext->query(User::class)->orderBy('id', 'asc')->firstOrFail();
```

### Eager Loading Relationships

The `with()` method solves the N+1 query problem by eager loading related entities natively. The `EntityQueryBuilder` detects `#[HasMany]` and `#[BelongsTo]` PHP 8 attributes on your entity properties and hydrates them directly.

```php
// Load all active users and eager load their 'posts' and 'profile' relationships
$users = $dbContext->query(User::class)
    ->where('active', 1)
    ->with(['posts', 'profile'])
    ->get();

foreach ($users as $user) {
    // $user->posts is already loaded! No additional queries executed.
}
```

### Pagination and Chunking

For massive datasets, the `EntityQueryBuilder` provides elegant iteration and pagination natively.

#### Pagination

The `paginate()` method automatically executes a `COUNT` query followed by a limited `SELECT` query, returning a structured array containing the data and pagination metadata.

```php
$paginated = $dbContext->query(User::class)->paginate(perPage: 15, page: 2);

/*
Returns:
[
    'data' => Collection([...]),
    'total' => 150,
    'per_page' => 15,
    'current_page' => 2,
    'last_page' => 10
]
*/
```

#### Chunking

The `chunk()` method processes a massive result set in small, memory-efficient batches. It passes a `Collection` of entities to the closure for each chunk.

```php
$dbContext->query(User::class)->chunk(100, function ($users, $page) {
    foreach ($users as $user) {
        // Process each user safely
    }
    
    // Return false to stop chunking early
});
```

---

## Identity Map and Hydration

When the `EntityQueryBuilder` hydrates a row into an object:

1. It checks the `DbContext`'s Identity Map to see if the entity (by primary key) is already in memory. If so, it returns the existing reference, saving memory and ensuring strict object equality.
2. If the entity does not exist, it reflects the object without calling the constructor, casts all database values to strict PHP types (like `int`, `float`, `DateTimeImmutable`), and populates the properties.
3. It registers the new entity into the `DbContext` Identity Map as `EntityState::Unchanged`.

---

## Debugging

If you need to inspect the compiled SQL string before execution, use `getSql()`:

```php
$sql = $dbContext->query(User::class)->where('status', 'active')->getSql();
```

---

## Next Steps

Now that you understand how to fetch and hydrate entities, learn how to manage them:

- [Models & ORM](orm.md): Understand Entity Metadata, Change Tracking, and the Unit of Work.
- [Query Builder](query-builder.md): Explore the base query builder for raw array-based queries.
