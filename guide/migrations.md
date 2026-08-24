# Migrations & Seeding

Migrations are a form of version control for your database schema. They allow you to define tables, columns, and constraints programmatically in PHP, making it easy to share database changes across your team and deploy them reliably to production. Seeding allows you to populate your database with initial data or generated test data.

NexusPHP provides a robust, dependency-free migration system built natively into the framework, complete with transaction safety, concurrency locks, and checksum validation.

---

## Introduction to Migrations

In NexusPHP, a migration is a PHP class that defines how to apply (and revert) structural changes to the database. Instead of writing raw SQL Data Definition Language (DDL) statements, you use the framework's intuitive `Schema` facade and `TableBuilder` to declare tables and columns fluently.

Migrations are stored in the `database/migrations/` directory of your project.

### Migration Naming Convention

Migration files follow a specific naming convention: `YYYY_MM_DD_His_migration_name.php`. The timestamp ensures that migrations are executed in the exact order they were created.

For example: `2024_01_01_153000_create_users_table.php`

---

## Creating Migrations

### Generating Migration Files

To create a new migration, use the `make:migration` console command:

```bash
php nexus make:migration create_users_table
```

This will generate a new file in `database/migrations/` pre-populated with a boilerplate schema definition.

> [!WARNING]  
> The internal `MigrationRunner` executes migrations by resolving a studly-cased class name from the file name (e.g., `CreateUsersTable`). Ensure your migration files explicitly define this class, or extend `Nexus\Database\Migration`, to ensure successful execution.

### Writing Migrations: Creating Tables

Inside your migration class, implement the `up()` method to define the new schema and the `down()` method to revert it.

To create a table, use `Nexus\Database\Schema::create()`, passing the table name and a closure that receives a `Nexus\Database\Schema\TableBuilder` instance.

```php
use Nexus\Database\Schema;
use Nexus\Database\Schema\TableBuilder;
use Nexus\Database\Migration;

class CreateUsersTable extends Migration 
{
    public function up(): void
    {
        Schema::create('users', function (TableBuilder $table) {
            $table->id(); // Creates auto-incrementing integer 'id'
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps(); // Adds created_at and updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}
```

### Available Column Types

The `TableBuilder` provides a wide variety of column definition methods:

- `$table->id('column_name')`
- `$table->string('name', $length = 255)`
- `$table->text('name')`
- `$table->integer('name')` / `$table->bigInteger('name')`
- `$table->boolean('name')`
- `$table->dateTime('name')` / `$table->date('name')` / `$table->time('name')` / `$table->timestamp('name')`
- `$table->decimal('name', $precision = 10, $scale = 2)`
- `$table->float('name')` / `$table->double('name')`
- `$table->json('name')`
- `$table->uuid('name')`
- `$table->char('name', $length = 255)`
- `$table->foreignId('name')`

### Column Modifiers & Constraints

You can chain modifiers to your column definitions:

```php
$table->string('nullable_field')->nullable();
$table->integer('status')->default(1);
```

You can define explicit indexes and constraints:

```php
// Primary Keys
$table->primary(['first_name', 'last_name']);

// Indexes
$table->index('email');
$table->uniqueIndex(['email', 'company_id']);

// Foreign Keys
$table->foreign('user_id')
      ->references('id')
      ->on('users')
      ->cascadeOnDelete()
      ->restrictOnUpdate();
```

---

## Running Migrations

NexusPHP includes several console commands to execute and manage your migrations.

### Executing Migrations

To run all pending migrations, use the `migrate` command. The runner acquires a concurrency lock (`migration_locks` table) to prevent multiple servers from executing migrations simultaneously, and runs each migration within a database transaction.

```bash
php nexus migrate
```

You can execute a "dry run" to simulate the migration process without committing changes:

```bash
php nexus migrate --dry-run
```

### Rolling Back Migrations

To revert the last "batch" of executed migrations, use the `migrate:rollback` command.

```bash
php nexus migrate:rollback
```

### Checking Migration Status

To view which migrations have been executed and verify their file checksums (ensuring previously run migrations haven't been maliciously modified), use the `migrate:status` command.

```bash
php nexus migrate:status
```

---

## Seeding the Database

Seeders allow you to populate your database tables with initial production data or generated test data.

### Writing Seeders

Seeders are standard PHP classes that extend the `Nexus\Database\Seeding\Seeder` base class and implement a single `run()` method. In this method, you can use the Query Builder or Model Factories to insert data.

```php
namespace Database\Seeders;

use Nexus\Database\Seeding\Seeder;
use Database\Factories\UserFactory;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Generate 50 test users using model factory
        UserFactory::new()->count(50)->create();
    }
}
```

### Model Factories

NexusPHP provides the `Nexus\Database\Factory` class to help generate data for testing and database seeders.

To create a factory, extend `Factory` and set the target `$model` class along with the default `definition()` array:

```php
namespace Database\Factories;

use Nexus\Database\Factory;
use App\Models\User;

class UserFactory extends Factory
{
    protected string $model = User::class;

    public function definition(): array
    {
        return [
            'name' => 'User ' . uniqid(),
            'email' => uniqid() . '@example.com',
            'role' => 'user',
        ];
    }
}
```

You can generate instances in memory using `make()` or save them directly to the database using `create()`:

```php
// Make in-memory array of models
$users = UserFactory::new()->count(10)->make();

// Persist models directly to the database with state overrides
$admin = UserFactory::new()
    ->state(['role' => 'admin'])
    ->create();
```

---

## Best Practices

1. **Keep Migrations Idempotent**: A `down()` method should perfectly revert whatever the `up()` method did. 
2. **Never Edit Old Migrations**: Once a migration has been committed and executed in production, you should never edit it. Instead, create a new migration to apply subsequent changes. NexusPHP's checksum tracking will flag modified historical migrations as invalid in `migrate:status`.
3. **Use Transactions Wisely**: NexusPHP automatically wraps each migration file execution in a database transaction. However, some DDL statements in certain databases (like MySQL) cause implicit commits. Be aware of your specific database engine's limitations.

---

## Next Steps

Now that your schema is set up, you can start writing data logic:

- [Database Connections](database.md): Learn about connection configuration.
- [Query Builder](query-builder.md): Build fluent SQL queries to insert and retrieve data.
- [Models & ORM](orm.md): Dive into the DataMapper & ActiveRecord ORM for robust domain logic.
