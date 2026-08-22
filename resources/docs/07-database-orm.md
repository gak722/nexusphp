# 07. Database & Active Record ORM

NexusPHP features a powerful, zero-dependency Fluent Query Builder (`Nexus\Database\QueryBuilder`) and Active Record ORM (`Nexus\Database\Model`).

---

## 1. Database Configuration

Database settings are configured in `.env` and initialized via PDO in `Nexus\Database\Connection`.

```env
DB_DRIVER=sqlite
DB_DATABASE=storage/database.sqlite
```

---

## 2. Fluent Query Builder

The Query Builder provides a clean fluent API for constructing SQL queries safely with prepared parameter bindings:

```php
use Nexus\Database\QueryBuilder;

$query = new QueryBuilder();

// Select with WHERE clause
$users = $query->table('users')
    ->where('status', '=', 'active')
    ->where('age', '>=', 18)
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();

// Insert record
$insertedId = $query->table('users')->insert([
    'name' => 'Sarah Connor',
    'email' => 'sarah@skynet.org',
    'created_at' => date('Y-m-d H:i:s')
]);

// Update record
$query->table('users')
    ->where('id', '=', $insertedId)
    ->update(['status' => 'verified']);

// Delete record
$query->table('users')->where('status', '=', 'banned')->delete();
```

---

## 3. Active Record ORM (`Nexus\Database\Model`)

Define domain models by extending `Nexus\Database\Model`:

```php
namespace App\Models;

use Nexus\Database\Model;
use Nexus\Database\Relations\HasMany;
use Nexus\Database\Relations\BelongsTo;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';

    // Model Relationship: User has many Posts
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'user_id', 'id');
    }
}
```

```php
namespace App\Models;

use Nexus\Database\Model;
use Nexus\Database\Relations\BelongsTo;

class Post extends Model
{
    protected string $table = 'posts';

    // Model Relationship: Post belongs to User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
```

---

## 4. Model CRUD Operations

```php
use App\Models\User;

// Find record by primary key
$user = User::find(1);
echo $user->name;

// Create new record
$user = new User();
$user->name = 'John Wick';
$user->email = 'john@continental.com';
$user->save();

// Update existing record
$user = User::find(1);
$user->email = 'newemail@example.com';
$user->save();

// Delete record
$user->delete();
```

---

## 5. Next Steps

Learn how to manage database schemas in [08. Migrations & Seeders](08-migrations-seeders.md).
