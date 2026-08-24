# Models & ORM

The NexusPHP ORM provides a highly performant, enterprise-grade database abstraction. It natively supports two complementary persistence paradigms:

1. **DataMapper ORM (`DbContext`)**: A decoupled architecture implementing a strict **Unit of Work** and **Identity Map**, keeping domain objects pure while managing state tracking and atomic transactions.
2. **ActiveRecord Models (`Nexus\Database\Model`)**: A lightweight, intuitive base model pattern for rapid application development.

---

## 1. DataMapper ORM Architecture (`DbContext`)

In the DataMapper architecture, your entities remain pure PHP objects (POJOs). State tracking, relationship hydration, and SQL generation are handled by the `DbContext` Unit of Work.

> [!IMPORTANT]
> The DataMapper ORM is the recommended pattern for complex domain logic where decoupling domain entities from persistence concerns is critical.

### Defining Entities & Attributes

Entity metadata is declared using native PHP 8 Attributes from `Nexus\Database\ORM\Attributes`.

```php
namespace App\Domain\Entities;

use Nexus\Database\ORM\Attributes\Table;
use Nexus\Database\ORM\Attributes\Key;
use Nexus\Database\ORM\Attributes\Column;
use Nexus\Database\ORM\Attributes\SoftDeletes;
use Nexus\Database\ORM\Attributes\ConcurrencyToken;

#[Table(name: 'users')]
class User
{
    #[Key(autoIncrement: true)]
    public int $id;

    #[Column(name: 'email_address')]
    public string $email;

    #[Column]
    public ?string $name = null;

    #[SoftDeletes(column: 'deleted_at')]
    public ?string $deletedAt = null;

    #[ConcurrencyToken]
    public int $version = 1;
}
```

#### Available Mapping Attributes:

- **`#[Table(name: 'table_name')]`**: Specifies the database table name. If omitted, the table name is automatically generated in plural snake_case.
- **`#[Key(autoIncrement: true)]`**: Marks a property as the primary key. If a property is named `$id`, it is automatically assumed to be an auto-incrementing primary key.
- **`#[Column(name: 'col_name', nullable: true)]`**: Maps a property to a specific column name and type.
- **`#[SoftDeletes(column: 'deleted_at')]`**: Enables soft deletes for the entity.
- **`#[ConcurrencyToken]`**: Marks an integer property for Optimistic Concurrency Control.

---

### Unit of Work & Change Tracking

The ORM utilizes `Nexus\Database\ORM\Tracking\ChangeTracker` to manage the lifecycle state of your entities.

When an entity is fetched or added to `DbContext`, it is wrapped in an `EntityEntry` and assigned an `EntityState` (`Added`, `Unchanged`, `Modified`, `Deleted`, `Detached`). A snapshot of initial property values is captured. When `saveChanges()` is called, `ChangeTracker::detectChanges()` compares current property values against the snapshot, generating optimized `UPDATE` statements for dirty properties.

#### Identity Map

The `ChangeTracker` acts as an **Identity Map**. Querying an entity by primary key returns the exact same object reference in memory during a request cycle, preventing conflicting entity states.

---

### CRUD Operations with `DbContext`

Inject or resolve `Nexus\Database\ORM\DbContext` to manage entity persistence:

```php
use Nexus\Database\ORM\DbContext;
use App\Domain\Entities\User;

$dbContext = app(DbContext::class);

// 1. Create (State: Added)
$user = new User();
$user->email = 'alice@example.com';
$user->name = 'Alice';
$dbContext->add($user);

// 2. Read
$users = $dbContext->query(User::class)->where('email', 'alice@example.com')->get();
$user = $dbContext->query(User::class)->where('id', 1)->firstOrFail();

// 3. Update (State: Modified - automatically detected)
$user->name = 'Alice Smith';
$dbContext->update($user);

// 4. Delete (State: Deleted)
$dbContext->remove($user);

// Commit all tracked changes in a single atomic transaction!
$affectedRows = $dbContext->saveChanges();
```

---

### Relationships & Eager Loading

Relationships are declared directly on entity properties using attributes:

- **`#[HasOne(targetEntity: Profile::class, foreignKey: 'user_id')]`**
- **`#[HasMany(targetEntity: Post::class, foreignKey: 'user_id')]`**
- **`#[BelongsTo(targetEntity: User::class, foreignKey: 'user_id')]`**
- **`#[BelongsToMany(targetEntity: Role::class, pivotTable: 'user_roles', foreignKey: 'user_id', relatedKey: 'role_id')]`**

```php
use Nexus\Database\ORM\Attributes\HasMany;

class User 
{
    #[HasMany(targetEntity: Post::class)]
    public array $posts = [];
}
```

To prevent N+1 query problems, eager load relationships using `with()`:

```php
$users = $dbContext->query(User::class)
    ->with('posts')
    ->get();
```

---

## 2. ActiveRecord Model Architecture (`Nexus\Database\Model`)

For applications preferring a classic ActiveRecord approach, extend `Nexus\Database\Model`.

```php
namespace App\Models;

use Nexus\Database\Model;
use Nexus\Database\ORM\SoftDeletes;

class User extends Model
{
    use SoftDeletes;

    protected static string $table = 'users';
    protected static string $primaryKey = 'id';
    protected array $fillable = ['name', 'email', 'password'];
}
```

---

### Soft Deleting Models

Soft deletion flags records as deleted in your database without physically removing the rows.

#### Enabling Soft Deletes

Add the `Nexus\Database\ORM\SoftDeletes` trait to your model:

```php
use Nexus\Database\Model;
use Nexus\Database\ORM\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;
}
```

You can customize the deletion column by defining a `DELETED_AT` constant on the model:

```php
class Post extends Model
{
    use SoftDeletes;
    public const DELETED_AT = 'archived_at';
}
```

#### Deleting & Restoring Records

Calling `delete()` on a model with `SoftDeletes` sets the timestamp column to the current date and time:

```php
$post = Post::find(1);
$post->delete(); // Sets deleted_at = current timestamp

// Check if soft deleted
if ($post->trashed()) {
    // Model is trashed
}

// Restore a soft deleted model
$post->restore(); // Sets deleted_at = NULL

// Permanently delete from database
$post->forceDelete();
```

#### Querying Soft Deleted Models

By default, soft-deleted records are excluded (`WHERE deleted_at IS NULL`). You can modify this behavior:

```php
// Include soft-deleted records
$allPosts = Post::withTrashed()->get();

// Retrieve ONLY soft-deleted records
$archivedPosts = Post::onlyTrashed()->get();
```

---

### Binding the Connection Resolver

ActiveRecord models (`Nexus\Database\Model`) rely on a shared database connection resolver. Before invoking any static query APIs (for example, `Model::find()`, `Model::where()`, or `Model::create()`), you must bind a `Nexus\Database\Connection` instance to the `Model` class. If the resolver hasn't been configured you'll encounter a runtime error stating the connection resolver is not configured.

You can bind the resolver during application bootstrap. For example, after the `Connection` singleton is available (such as inside `Application::registerCoreBindings()`), register the resolver:

```php
// Resolve the connection and bind it to the ActiveRecord Model
$connection = $this->make(\Nexus\Database\Connection::class);
\Nexus\Database\Model::setConnectionResolver($connection);
```

Or from a simple bootstrap script:

```php
$app = new \Nexus\Foundation\Application();
$connection = $app->make(\Nexus\Database\Connection::class);
\Nexus\Database\Model::setConnectionResolver($connection);
```

Unit and feature tests in this codebase also set the resolver explicitly (for example, `Model::setConnectionResolver($conn)`), which you can follow as a pattern for test setup.


## 3. Model Factories & Database Seeding

NexusPHP provides `Nexus\Database\Factory` for generating fake models for testing and seeders.

### Creating a Factory

Extend `Nexus\Database\Factory` and define the target `$model` class along with the `definition()` array:

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
            'name' => 'User ' . bin2hex(random_bytes(4)),
            'email' => bin2hex(random_bytes(4)) . '@example.com',
            'role' => 'user',
        ];
    }
}
```

### Using Factories

Generate models in memory (`make()`) or persist them directly to the database (`create()`):

```php
// Single model instance in memory
$user = UserFactory::new()->make();

// Create and persist 10 models to the database
$users = UserFactory::new()->count(10)->create();

// Apply custom attribute overrides or states
$adminUsers = UserFactory::new()
    ->count(5)
    ->state(['role' => 'admin'])
    ->create();
```

---

## Best Practices

1. **Choose the Right Paradigm**: Use `DbContext` DataMapper for domain-driven applications needing strict isolation and Unit of Work batching. Use `Nexus\Database\Model` ActiveRecord for fast, straightforward CRUD projects.
2. **Use Soft Deletes for Retention**: Apply `SoftDeletes` to critical entities (users, orders) so accidental deletions can be restored easily.
3. **Optimistic Concurrency**: For high-concurrency entities (wallets, inventory), decorate an integer property with `#[ConcurrencyToken]`. The DataMapper ORM automatically verifies and increments the token on update, throwing a `ConcurrencyException` if modified concurrently.
4. **Always Eager Load**: Prevent N+1 query bottlenecks by chaining `.with('relationship')` on queries when reading collections.

---

## Next Steps

Explore lower-level database components:

- [Database Connections](database.md): Learn about connection configuration.
- [Entity Query Builder](entity-query-builder.md): Deep dive into querying, filtering, and eager loading entities.
- [Migrations & Seeding](migrations.md): Manage your database schema programmatically.
