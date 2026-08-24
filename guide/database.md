# Database Connections

Robust database connectivity is essential for modern web applications. The NexusPHP framework provides a lightweight, high-performance, and dependency-free database abstraction layer. Instead of relying on heavy third-party ORMs or generic connection managers, NexusPHP favors a clean wrapper around PHP's native PDO, providing exactly what you need to interact with your databases efficiently.

> [!IMPORTANT]
> NexusPHP does not use external libraries like Doctrine or Eloquent. Its database abstraction is custom-built, highly optimized, and tailored strictly to the framework's core philosophy. 

---

## Configuration

Database configuration in NexusPHP is centrally managed in the `config/database.php` file. The configuration returns an array defining the default connection and a list of all available connections.

### Configuration Structure

The configuration file requires two primary keys:
- `default`: The name of the default connection to use.
- `connections`: An array containing configurations for each specific database driver.

Here is the exact structure based on the framework's implementation:

```php
// config/database.php
return [
    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('DB_DATABASE', 'database.sqlite'),
        ],
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => (int) env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'nexus'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => (int) env('DB_PORT', 5432),
            'database' => env('DB_DATABASE', 'nexus'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
        ],
    ],
];
```

### Environment Configuration

As seen above, NexusPHP heavily utilizes the `env()` function to load configuration dynamically from your environment (typically a `.env` file). It is highly recommended to keep sensitive credentials out of version control and manage them exclusively through environment variables.

---

## Supported Database Drivers

NexusPHP natively supports three database drivers. The driver is specified using the `driver` key in your configuration.

1. **MySQL / MariaDB (`mysql`)**
   - **Requires**: `pdo_mysql` PHP extension.
   - **Configuration**: `host`, `port`, `database`, `username`, `password`, `charset`.

2. **PostgreSQL (`pgsql`)**
   - **Requires**: `pdo_pgsql` PHP extension.
   - **Configuration**: `host`, `port`, `database`, `username`, `password`, `charset`.

3. **SQLite (`sqlite`)**
   - **Requires**: `pdo_sqlite` PHP extension.
   - **Configuration**: `database` (the absolute or relative path to your SQLite database file).

> [!NOTE]
> All connections are created using PDO with strict error handling (`PDO::ERRMODE_EXCEPTION`), associative fetching by default (`PDO::FETCH_ASSOC`), and native prepared statements enabled (`PDO::ATTR_EMULATE_PREPARES => false`).

---

## Accessing Connections

NexusPHP does not use a central connection manager facade (like `DB::connection()`). Instead, the framework leverages its service container to manage the default database connection as a singleton.

### The Default Connection

The application container registers the default `Nexus\Database\Connection` singleton during bootstrap. You can resolve the default connection directly via the container or through Dependency Injection in your controllers or services.

```php
// Resolving via the application container
$connection = app(\Nexus\Database\Connection::class);
```

### Multiple Connections

NexusPHP does not automatically instantiate or pool secondary connections. If your application needs to talk to multiple databases, you can manually instantiate a new `Connection` object using the specific configuration array:

```php
use Nexus\Database\Connection;

$config = config('database.connections.pgsql');
$postgresConnection = new Connection($config);
```

You could also register these secondary connections as named singletons within a custom service provider if you need widespread access to them.

---

## The Connection Class

The core of NexusPHP's database interactions is the `Nexus\Database\Connection` class. It acts as a lightweight wrapper around a native `PDO` instance, abstracting query execution and transaction management.

### Public Methods

- `__construct(array|\PDO $config)`: Initializes the connection using an array of configuration options or an existing `PDO` instance.
- `getPdo(): \PDO`: Returns the underlying `PDO` instance for direct access.
- `select(string $query, array $bindings = []): array`: Executes a `SELECT` query and returns the results.
- `statement(string $query, array $bindings = []): bool`: Executes a general SQL statement (e.g., `INSERT`, `DROP`) and returns true on success.
- `affectingStatement(string $query, array $bindings = []): int`: Executes an `UPDATE` or `DELETE` query and returns the number of affected rows.

---

## Running Queries

### Raw SQL Queries

You can execute raw SQL directly on the `Connection` object. NexusPHP uses PDO prepared statements for all query executions to prevent SQL injection.

```php
$connection = app(\Nexus\Database\Connection::class);

// Fetching multiple rows
$users = $connection->select('SELECT * FROM users WHERE active = ?', [1]);

// Fetching a single value/row
$user = $connection->select('SELECT * FROM users WHERE id = ? LIMIT 1', [42]);

// Running an INSERT statement
$connection->statement('INSERT INTO users (name, email) VALUES (?, ?)', ['Alice', 'alice@example.com']);

// Running an UPDATE/DELETE and getting the affected row count
$deleted = $connection->affectingStatement('DELETE FROM users WHERE active = ?', [0]);
```

### Fetching Results

Since the `Connection` class configures the PDO fetch mode to `PDO::FETCH_ASSOC`, the `select()` method will always return an array of associative arrays.

---

## Transactions

NexusPHP provides both manual and automatic transaction management to ensure data integrity during complex database operations. 

### Basic Transaction Methods

If you need fine-grained control over transactions, you can manually begin, commit, or roll back transactions:

```php
$connection = app(\Nexus\Database\Connection::class);

try {
    $connection->beginTransaction();
    
    // Execute queries...
    $connection->statement('UPDATE accounts SET balance = balance - 100 WHERE id = 1');
    $connection->statement('UPDATE accounts SET balance = balance + 100 WHERE id = 2');
    
    $connection->commit();
} catch (\Throwable $e) {
    $connection->rollBack();
    throw $e; // Handle the exception
}
```

### Automatic Transactions

The most recommended approach is using the `transaction()` method. This method accepts a closure, automatically begins the transaction, and will commit it if the closure executes successfully. If an exception is thrown, the transaction is automatically rolled back.

```php
$connection->transaction(function ($conn) {
    $conn->statement('UPDATE accounts SET balance = balance - 100 WHERE id = 1');
    $conn->statement('UPDATE accounts SET balance = balance + 100 WHERE id = 2');
    
    return true; // Optional return value
});
```

> [!NOTE]
> Nested transactions and savepoints are not explicitly managed by the framework; they rely entirely on the native PDO driver's support for savepoints if utilized manually.

---

## Connection Events (Query Logging)

NexusPHP provides a static event listener mechanism strictly for monitoring database queries. This is extremely useful for profiling, debugging, or logging query execution times.

You can register a callback using the `listen` method. The callback receives the raw SQL string, the parameter bindings, and the execution duration (in milliseconds).

```php
use Nexus\Database\Connection;

Connection::listen(function (string $query, array $bindings, float $duration) {
    // Log the query to a file or standard output
    error_log("Query: {$query} | Duration: {$duration}ms");
});
```

> [!NOTE]
> Event listeners are triggered after the query successfully executes. Transaction lifecycle events (like `beginTransaction` or `commit`) are not currently broadcasted.

---

## Managing Connections

### Reconnecting and Disconnecting

NexusPHP's `Connection` class does not feature explicit `reconnect()` or `disconnect()` methods, nor does it implement built-in connection pooling. The connection lifecycle relies on PHP's standard PDO lifecycle. A connection is established as soon as the `Connection` class is instantiated (which the Service Container does when resolved). To close a connection, you simply let the `Connection` instance fall out of scope or manually unset it.

---

## Schema Operations

NexusPHP provides a `Schema` facade (`Nexus\Database\Schema`) for programmatic schema manipulation, often used inside Migrations. It relies on a `Blueprint` object to compile Data Definition Language (DDL) queries. 

```php
use Nexus\Database\Schema;
use Nexus\Database\Blueprint;

Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamps();
});

Schema::dropIfExists('users');
```

> [!NOTE]
> Schema introspection (e.g., checking if a table or column exists) is not currently provided natively by the `Schema` facade.

---

## Best Practices

1. **Dependency Injection**: Always inject the `Connection` or use `app(\Nexus\Database\Connection::class)` rather than instantiating connections manually for the default database.
2. **Environment Variables**: Never hardcode database credentials in `config/database.php`. Always use `.env`.
3. **Use Automatic Transactions**: Prefer the `transaction(callable)` method over manual `beginTransaction` to guarantee your transactions are rolled back if an exception occurs mid-execution.
4. **Beware of Singletons**: Since the framework handles the primary database connection as a singleton, you do not need to worry about multiple PDO instances being spawned repeatedly during a single HTTP request lifecycle.

---

## Next Steps

Now that you have a solid understanding of how database connections operate, you can explore higher-level database abstractions provided by NexusPHP:

- [Query Builder](query-builder.md): Learn how to build fluent, parameterized SQL queries dynamically.
- [Models & ORM](orm.md): Dive into the Laravel-style active record implementation.
- [Migrations](migrations.md): Manage your database schema programmatically.
