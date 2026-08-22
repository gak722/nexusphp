# 06. Views & Native Engine

NexusPHP includes a native, isolated output buffering rendering engine (`Nexus\View\Engine`) that provides clean HTML rendering without template compiler overhead.

---

## 1. Creating View Templates

View files are stored in `resources/views/` and use standard `.php` extensions.

### Creating a View (`resources/views/users/index.php`):

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($title) ?></title>
</head>
<body>
    <h1>User Directory</h1>
    <ul>
        <?php foreach ($users as $user): ?>
            <li><?= e($user['name']) ?> (<?= e($user['email']) ?>)</li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
```

---

## 2. Rendering Views from Controllers

Use the global `view()` helper function:

```php
namespace App\Http\Controllers;

use Nexus\Http\Controller;
use Nexus\Http\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = [
            ['name' => 'Alice Smith', 'email' => 'alice@example.com'],
            ['name' => 'Bob Jones', 'email' => 'bob@example.com'],
        ];

        // Renders resources/views/users/index.php
        return view('users.index', [
            'title' => 'User Roster',
            'users' => $users,
        ]);
    }
}
```

---

## 3. HTML Escaping (`e()` Helper)

To prevent Cross-Site Scripting (XSS) vulnerabilities, always wrap raw user input in the global `e()` helper:

```php
// Escapes HTML special characters safely:
echo e("<script>alert('xss');</script>");
// Output: &lt;script&gt;alert(&#039;xss&#039;);&lt;/script&gt;
```

---

## 4. Layouts & Master Templates

Include master layouts directly inside child views using native PHP `include`:

```php
<?php
// resources/views/dashboard.php
$pageTitle = "Account Dashboard";
include app()->basePath('resources/views/layouts/master.php');
?>
```

---

## 5. Next Steps

Learn how to work with databases in [07. Database & Active Record ORM](07-database-orm.md).
