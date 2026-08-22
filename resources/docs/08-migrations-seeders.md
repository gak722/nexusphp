# 08. Migrations & Seeders

Database migrations allow developers to version-control the application database schema cleanly.

---

## 1. Creating Migrations

Use the `nexus` CLI tool to generate migration blueprint files:

```bash
php nexus make:migration create_users_table
```

This creates a timestamped migration file in `database/migrations/`:

```php
use Nexus\Database\Migration;
use Nexus\Database\Blueprint;
use Nexus\Database\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

---

## 2. Schema Blueprint Methods

| Method | Column Type | Description |
| :--- | :--- | :--- |
| `$table->id()` | `INTEGER PRIMARY KEY AUTOINCREMENT` | Auto-incrementing primary key ID |
| `$table->string('col')` | `VARCHAR(255)` | Text column |
| `$table->text('col')` | `TEXT` | Long text column |
| `$table->integer('col')` | `INTEGER` | Integer column |
| `$table->timestamps()` | `DATETIME` | Adds `created_at` and `updated_at` columns |

---

## 3. Running & Rolling Back Migrations

To execute pending migrations:

```bash
php nexus migrate
```

To rollback the last migration batch:

```bash
php nexus migrate:rollback
```

---

## 4. Database Seeders

Database seeders populate tables with sample or test data. Extend `Nexus\Database\Seeder`:

```php
namespace Database\Seeders;

use Nexus\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@nexusphp.dev',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
        ]);
    }
}
```

---

## 5. Next Steps

Learn how to validate incoming form and API data in [09. Validation & Form Requests](09-validation.md).
