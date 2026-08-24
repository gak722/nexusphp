# Models & ORM

The NexusPHP ORM provides a highly performant, enterprise-grade database abstraction. Unlike traditional Active Record implementations, NexusPHP's primary, advanced ORM architecture implements the **DataMapper** pattern utilizing a strict **Unit of Work** and **Identity Map**. 

This design completely decouples your domain logic from database persistence. Your entities remain pure PHP objects (POJOs), while the framework manages state tracking, relationship hydration, and efficient batch querying.

> [!IMPORTANT]
> This documentation strictly covers the `DbContext` (DataMapper) ORM architecture implementation in NexusPHP. While the framework also provides a legacy/utility `Nexus\Database\Model` ActiveRecord class, the DataMapper architecture is the officially recommended approach for enterprise applications.

---

## Getting Started with Models

In the NexusPHP ORM, a "Model" (or Entity) is simply a standard PHP class. It does not need to extend any base class. You define the structure of your entity using strictly typed PHP properties and configure its database mapping using native PHP 8 Attributes.

### Creating a Model

Because models don't inherit from a bulky base class, they are incredibly lightweight.

```php
namespace App\Domain\Entities;

use Nexus\Database\ORM\Attributes\Table;
use Nexus\Database\ORM\Attributes\Key;
use Nexus\Database\ORM\Attributes\Column;

#[Table(name: 'users')]
class User
{
    #[Key(autoIncrement: true)]
    public int $id;

    #[Column(name: 'email_address')]
    public string $email;

    #[Column]
    public ?string $name = null;
    
    // Domain methods...
}
```

> [!NOTE]
> Properties should ideally be `public` (or have accessible getters/setters) so the `ChangeTracker` can reflect and extract their values during persistence.

---

## Entity Metadata

Entity Metadata tells the ORM how to map your PHP class to your database table. In NexusPHP, this is handled dynamically by the `Nexus\Database\ORM\Mapping\MetadataFactory` class, which parses PHP 8 Attributes and caches them in memory (`static array $cache`) for blazing fast performance across requests.

### Defining Metadata

Metadata is defined exclusively using Attributes from the `Nexus\Database\ORM\Attributes` namespace.

- **`#[Table(name: 'table_name')]`**: Optional. If omitted, the `MetadataFactory` automatically generates a plural, snake_case table name based on the class basename.
- **`#[Key(autoIncrement: true)]`**: Marks a property as the primary key. If a property is named exactly `$id`, it is automatically assumed to be an auto-incrementing primary key even without this attribute.
- **`#[Column(name: 'col_name', nullable: true)]`**: Maps a property to a specific column. If omitted, the property name is converted to snake_case.
- **`#[SoftDeletes(column: 'deleted_at')]`**: Enables soft deletes for the entity.
- **`#[ConcurrencyToken]`**: Marks an integer property for Optimistic Concurrency Control.

### Type Mapping

The `MetadataFactory` and `EntityQueryBuilder` natively map PHP types to their database representations. When fetching records, values are automatically cast based on the property's declared PHP type (e.g., `int`, `float`, `bool`, `string`, `DateTimeImmutable`, `DateTime`).

---

## Change Tracking

The ORM utilizes the `Nexus\Database\ORM\Tracking\ChangeTracker` to manage the lifecycle state of your entities.

### How Change Tracking Works

When an entity is fetched from the database or registered for persistence, it is wrapped in an `EntityEntry` and assigned an `EntityState` enum (`Added`, `Unchanged`, `Modified`, `Deleted`, `Detached`).

The tracker captures a snapshot of the entity's properties (via Reflection) into an `originalValues` array. When it comes time to save changes to the database, the `ChangeTracker::detectChanges()` method iterates over the current property values, comparing them against `originalValues`. If any differences exist, the state is transitioned to `Modified`, and only the "dirty" properties are included in the subsequent `UPDATE` query.

---

## The Unit of Work

The core of the NexusPHP DataMapper ORM is the Unit of Work, implemented by the `Nexus\Database\ORM\DbContext` class.

The `DbContext` accumulates inserts, updates, and deletes in memory and executes them all at once within a single database transaction. This ensures data consistency and dramatically reduces database overhead.

### Managing Entities

You inject or resolve the `DbContext` from the application container.

```php
use Nexus\Database\ORM\DbContext;

$dbContext = app(DbContext::class);

$user = new User();
$user->email = 'test@example.com';

// 1. Register the new entity with the DbContext (State: Added)
$dbContext->add($user);

// 2. Register an entity for deletion (State: Deleted)
$dbContext->remove($oldUser);

// 3. Mark an existing entity as modified (State: Modified)
$dbContext->update($existingUser);

// 4. Commit all tracked changes to the database in one transaction!
$affectedRows = $dbContext->saveChanges();
```

> [!TODO]
> Verify exact batch insert/update logic - currently `saveChanges()` iterates through entries and executes individual statements inside a transaction; true multi-row insert batching is not explicitly supported.

### Identity Management

The `ChangeTracker` acts as an **Identity Map**. When you query for an entity, the ORM checks if an entity with that Class and ID (`"{$class}:{$id}"`) already exists in memory. If it does, the ORM returns the *exact same object reference*. This guarantees that you are never working with conflicting instances of the same database row during a single request.

---

## CRUD Operations

### Creating and Saving

Create a new instance of your class, set its properties, add it to the `DbContext`, and call `saveChanges()`. 

```php
$post = new Post();
$post->title = 'NexusPHP ORM';
$dbContext->add($post);
$dbContext->saveChanges();

// The auto-incremented primary key is automatically populated on the object!
echo $post->id; 
```

### Reading and Querying

To read records, use the `$dbContext->query(Entity::class)` method. This returns an `EntityQueryBuilder` specifically bound to your entity.

```php
// Find all
$users = $dbContext->query(User::class)->get(); // Returns a Collection of User objects

// Find with conditions
$admins = $dbContext->query(User::class)
    ->where('role', 'admin')
    ->orderBy('created_at', 'desc')
    ->get();

// Find a single record (throws ModelException if missing)
$user = $dbContext->query(User::class)->where('id', 42)->firstOrFail();

// Count aggregates
$total = $dbContext->query(User::class)->where('active', 1)->count();
```

### Updating

Fetch the record, modify its properties, and call `saveChanges()`. The `ChangeTracker` automatically detects the modifications.

```php
$user = $dbContext->query(User::class)->where('id', 1)->firstOrFail();
$user->name = 'Updated Name';

$dbContext->saveChanges(); // Only the 'name' column is sent in the UPDATE statement
```

### Deleting

Fetch the record, pass it to `remove()`, and commit. If the entity has the `#[SoftDeletes]` attribute, an `UPDATE` statement is executed to set the deletion timestamp. Otherwise, a `DELETE` statement is executed.

```php
$user = $dbContext->query(User::class)->where('id', 1)->firstOrFail();
$dbContext->remove($user);
$dbContext->saveChanges();
```

---

## Relationships

Relationships are defined declaratively via attributes directly on the properties of your entity.

### Defining Relationships

The `MetadataFactory` recognizes four primary relationship attributes:

- **`#[HasOne(targetEntity: Profile::class, foreignKey: 'user_id')]`**
- **`#[HasMany(targetEntity: Post::class, foreignKey: 'user_id')]`**
- **`#[BelongsTo(targetEntity: User::class, foreignKey: 'user_id')]`**
- **`#[BelongsToMany(targetEntity: Role::class, pivotTable: 'user_roles', foreignKey: 'user_id', relatedKey: 'role_id')]`**

If foreign keys or pivot tables are omitted, NexusPHP intelligently guesses them using standard snake_case naming conventions based on the class names.

```php
use Nexus\Database\ORM\Attributes\HasMany;

class User 
{
    // ...
    
    #[HasMany(targetEntity: Post::class)]
    public array $posts = [];
}
```

### Eager Loading

To prevent N+1 querying issues, the `EntityQueryBuilder` fully supports eager loading through the `with()` method. Eager loaded relationships are securely populated into your entity properties automatically.

```php
$users = $dbContext->query(User::class)
    ->with('posts') // Eager loads the $posts array on every User object
    ->get();
```

> [!TODO]
> Check if explicit lazy loading proxies or relationship mutation methods (e.g., `$user->posts()->save()`) are supported natively by the DataMapper implementation - not found in initial scan. Persisting relationships requires saving the parent/child entities with matching foreign keys manually.

---

## Model Events and Observers

> [!TODO]
> Check if Model Lifecycle Events (e.g., `creating`, `updated`) or Observers are supported by the `DbContext` architecture - not found in initial scan. Currently, changes are persisted transparently via the Unit of Work without explicit framework event hooks.

---

## Model Factories and Seeders

> [!TODO]
> Check if a dedicated Model Factory pattern exists for the DataMapper ORM - not found in initial scan. Seeding is available via standard query-builder seeders, but fluid factories are not natively bundled.

---

## Best Practices

1. **Keep Entities Pure**: Do not inject services or the database connection into your Entity classes. They should only contain properties and domain-specific state mutation methods.
2. **Commit Once**: Prefer accumulating all your state changes (adds, updates, deletes) during a request and calling `$dbContext->saveChanges()` exactly once at the end of the operation. This ensures everything happens in a single atomic transaction.
3. **Always Eager Load**: Whenever you know you will access relationship data on a collection of entities, use `with()` to load them efficiently.
4. **Optimistic Concurrency**: For critical entities (like wallets or inventory), define an integer property with the `#[ConcurrencyToken]` attribute. The ORM will automatically increment this token on update and throw a `ConcurrencyException` if another process modified the record simultaneously.

---

## Next Steps

Explore the lower-level systems that power the NexusPHP ORM:

- [Database Connections](database.md): Learn about connection configuration.
- [Entity Query Builder](entity-query-builder.md): Deep dive into querying, filtering, and eager loading entities.
- [Migrations & Seeding](migrations.md): Manage your database schema programmatically.
