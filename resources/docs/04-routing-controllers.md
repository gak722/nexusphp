# 04. Routing & Controllers

The NexusPHP Router (`Nexus\Routing\Router`) maps incoming HTTP requests to closure actions or Controller methods using high-speed regular expression compilation.

---

## 1. Basic Route Definitions

Routes are registered in `routes/web.php` or `routes/api.php`:

```php
use Nexus\Http\JsonResponse;
use Nexus\Http\Response;

// Closure Route
$router->get('/welcome', function () {
    return new Response('<h1>Welcome to NexusPHP</h1>');
});

// JSON API Route
$router->get('/api/status', function () {
    return new JsonResponse([
        'status' => 'active',
        'timestamp' => time()
    ]);
});
```

---

## 2. Dynamic Route Parameters

Route parameters are wrapped in curly braces `{param}`:

```php
// Dynamic route parameter
$router->get('/users/{id}', function (string $id) {
    return new JsonResponse(['user_id' => $id]);
});

// Multiple parameters
$router->get('/posts/{category}/{slug}', function (string $category, string $slug) {
    return new Response("Category: {$category}, Article: {$slug}");
});
```

---

## 3. Real-World Analogy: "Airport Security & Flight Gate"

Think of the Router and Controller flow like an **Airport Flight Gate**:

```
 [ Passenger (HTTP Request) ]
              │
              ▼
   ┌──────────────────────┐
   │ Security Checkpoint  │  <-- Route Middleware (CSRF, Auth, Cors)
   └──────────────────────┘
              │
              ▼
   ┌──────────────────────┐
   │ Gate Matching (URI)  │  <-- Router Regex Compiler (/flight/{id})
   └──────────────────────┘
              │
              ▼
   ┌──────────────────────┐
   │ Flight Crew Desk     │  <-- Controller Action (UserController@show)
   └──────────────────────┘
```

The passenger (Request) must present a valid passport/ticket (Headers/Middleware). Once verified, the airport staff guides them directly to their assigned gate (Controller Action).

---

## 4. Controllers

Controllers inherit from `Nexus\Http\Controller` and encapsulate action handlers:

```php
namespace App\Http\Controllers;

use Nexus\Http\Controller;
use Nexus\Http\Request;
use Nexus\Http\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = [['id' => 1, 'name' => 'Alice'], ['id' => 2, 'name' => 'Bob']];
        return $this->json(['data' => $users]);
    }

    public function show(string $id): Response
    {
        return $this->json([
            'id' => (int) $id,
            'name' => 'John Doe'
        ]);
    }
}
```

Registering Controller Routes in `routes/web.php`:

```php
use App\Http\Controllers\UserController;

$router->get('/users', [UserController::class, 'index']);
$router->get('/users/{id}', [UserController::class, 'show']);
```

---

## 5. Next Steps

Learn how request filtering works in [05. HTTP Middleware](05-middleware.md).
