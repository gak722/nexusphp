# Query Builder

The NexusPHP Query Builder provides a fluent, secure, and intuitive interface for creating and executing database queries. It uses a Medoo-inspired approach to abstract complex SQL syntax into simple, chainable methods. Most importantly, it exclusively relies on PDO prepared statements, ensuring that all data binding is secure and immune to SQL injection.

> [!IMPORTANT]
> This documentation strictly reflects the capabilities of `Nexus\Database\QueryBuilder`. NexusPHP's query builder is highly focused and deliberately omits massive, complex query compilation features (like Common Table Expressions) in favor of blazing speed and simplicity.

---

## Getting Started

### Accessing the Query Builder

You can obtain an instance of the Query Builder by injecting the database `Connection` and instantiating the builder directly, or by using the static `query()` method on any Eloquent-style `Model`.

```php
use Nexus\Database\QueryBuilder;
use Nexus\Database\Connection;

// 1. Instantiating manually using the container's Connection
$connection = app(Connection::class);
$builder = new QueryBuilder($connection);

// 2. Or using a Model (which pre-configures the table)
$builder = User::query();
```

> [!NOTE]
> There is no global `DB` facade for the query builder in NexusPHP. 

### Basic Query Example

A standard query involves defining the table, adding conditions, and fetching results.

```php
$users = $builder->table('users')
    ->where('active', 1)
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();
```

---

## Building SELECT Queries

### Specifying the Table

Use the `table()` method to define the target table for your query.

```php
$builder->table('users');
```

### Selecting Columns

By default, the builder selects all columns (`*`). You can specify exactly which columns to retrieve by passing an array to the `select()` method.

```php
$builder->table('users')->select(['id', 'email', 'name']);
```

> [!NOTE]
> Advanced select features like distinct selects or column aliases rely strictly on standard string declarations or internal aggregation methods, as the builder strips non-alphanumeric characters for security.

### WHERE Clauses

#### Basic WHERE

The `where()` method accepts a column name, an operator, and a value. If you omit the operator, it defaults to `=`.

```php
// SELECT * FROM users WHERE active = 1
$builder->where('active', 1);

// SELECT * FROM users WHERE age >= 18
$builder->where('age', '>=', 18);
```

#### WHERE Multiple Conditions

You can chain `where()` methods together. By default, chaining creates an `AND` condition. To create an `OR` condition, use the `orWhere()` method.

```php
$builder->where('role', 'admin')
        ->orWhere('role', 'editor');
```

> [!NOTE]
> Nested WHERE groups via closures (e.g., `AND (a = 1 OR b = 2)`) are not explicitly supported by the basic Query Builder to prioritize simplicity.

#### WHERE Special Methods

NexusPHP provides dedicated methods for common WHERE filtering requirements:

```php
// WHERE IN
$builder->whereIn('status', ['active', 'pending']);

// WHERE NOT IN
$builder->whereNotIn('id', [1, 2, 3]);

// WHERE NULL
$builder->whereNull('deleted_at');

// WHERE NOT NULL
$builder->whereNotNull('email_verified_at');
```

> [!NOTE]
> Dedicated methods like `whereBetween()` or `whereLike()` are omitted; you must use the standard `where()` method (e.g., `where('name', 'LIKE', '%John%')`).

### JOIN Clauses

You can join other tables using the `join()` and `leftJoin()` methods.

```php
// INNER JOIN (default)
$builder->join('posts', 'users.id', '=', 'posts.user_id');

// LEFT JOIN (shorthand)
$builder->leftJoin('profiles', 'users.id', '=', 'profiles.user_id');
```

You can specify other join types manually by passing a fifth argument to `join()` (e.g., `'RIGHT'` or `'CROSS'`).

```php
$builder->join('roles', 'users.role_id', '=', 'roles.id', 'RIGHT');
```

### Grouping and Aggregating

> [!NOTE]
> Complex grouping (`groupBy()`) and having (`having()`) clauses are intentionally omitted from this lightweight builder abstraction.

### Ordering and Limiting

#### ORDER BY

Use `orderBy()` to sort the query results. The default direction is `ASC`.

```php
$builder->orderBy('created_at', 'DESC');
```
*(You can chain multiple `orderBy()` calls to sort by multiple columns).*

#### LIMIT and OFFSET

To restrict the number of results returned or to skip a certain number of records, use `limit()` and `offset()`.

```php
// Retrieve 10 records, skipping the first 20
$builder->limit(10)->offset(20);
```

---

## Aggregate Queries

NexusPHP provides specialized methods to compute aggregate values directly:

```php
// Count all matching records
$total = $builder->table('users')->where('active', 1)->count();

// Sum a specific column
$revenue = $builder->table('orders')->where('status', 'paid')->sum('amount');
```

> [!NOTE]
> Aggregates beyond `count()` and `sum()` (such as `avg()`, `max()`, and `min()`) must be queried manually via raw statements.

---

## Data Manipulation (Insert, Update, Delete)

The Query Builder also handles DML (Data Manipulation Language) operations fluently.

### INSERT

To insert a record, pass an associative array to the `insert()` method. The keys must match the table columns.

```php
$success = $builder->table('users')->insert([
    'name' => 'Alice',
    'email' => 'alice@example.com'
]);
```

> [!NOTE]
> The `insert()` method currently expects a single associative array for a single record. To retrieve the last inserted ID, use `PDO::lastInsertId()` directly on the `Connection`.

### UPDATE

To update existing records, use the `update()` method alongside your `where()` conditions. It returns the number of affected rows.

```php
$affectedRows = $builder->table('users')
    ->where('id', 1)
    ->update(['status' => 'inactive']);
```

### DELETE

To delete records, apply your constraints and call the `delete()` method. It returns the number of affected rows.

```php
$deletedRows = $builder->table('users')
    ->where('last_login', '<', '2023-01-01')
    ->delete();
```

---

## Advanced Query Features

> [!NOTE]
> Subqueries, Unions, and Raw Expressions (like `DB::raw()`) are not supported natively by the Query Builder. You should execute these using raw SQL via the `Connection` directly.

---

## Parameter Binding and Security

Security is deeply ingrained in the NexusPHP query builder.

All parameters passed into methods like `where()`, `whereIn()`, `insert()`, and `update()` are automatically stored in an internal `$bindings` array. When the query is compiled and sent to the database connection, NexusPHP executes it entirely via **PDO positional placeholders (`?`)**. 

Because parameters are strictly separated from the SQL compilation step and sent over native prepared statements, it is virtually impossible to execute SQL injection attacks through standard query builder usage.

---

## Advanced Execution Methods

### Getting the SQL

If you want to debug or inspect the query string before executing it against the database, use the `toSql()` method:

```php
$sql = $builder->table('users')->where('id', 1)->toSql();
// Outputs: "SELECT * FROM users WHERE id = ?"
```

### Fetching Results

- **`get()`**: Executes the `SELECT` query and returns an array of associative arrays representing all matched rows.
- **`first()`**: Automatically limits the query to 1 record and returns the single associative array, or `null` if no record was found.

> [!NOTE]
> Advanced dataset chunking (`chunk()`) and pagination (`paginate()`) are not provided natively by the query builder; they must be implemented manually using `limit()` and `offset()`.

---

## Best Practices

1. **Never Concatenate Data into SQL strings**: Always rely on the builder's methods to handle user input. The builder safely binds all values dynamically.
2. **Use Eloquent for Logic**: While the raw Query Builder is extremely fast for simple reads, consider using NexusPHP Models (`User::query()`) to take advantage of casting, dirty-state tracking, and automated validation.
3. **Limit Query Sizes**: Use `limit()` and `offset()` deliberately on large datasets to avoid memory exhaustion, as all records from `get()` are loaded into memory simultaneously.

---

## Next Steps

With your knowledge of the Query Builder, you're ready to explore:

- [Database Connections](database.md): Learn about connection configuration and manual query execution.
- [Models & Relationships](orm.md): Dive into the Laravel-style active record implementation.
- [Migrations](migrations.md): Manage your database schema programmatically.
