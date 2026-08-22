# 16. Architecture Pattern: RESTful API (SPA Backend)

This pattern demonstrates configuring NexusPHP as a headless, ultra-fast backend for single-page applications (Vue, React, Svelte, or Mobile apps).

---

## 1. Core Pattern Requirements

- **Authentication:** Stateless JWT bearer tokens (`Nexus\Security\Jwt`).
- **Response Format:** Structured `Nexus\Http\JsonResponse`.
- **Cors & Rate Limiting:** Global middleware stack (`CorsMiddleware` & `RateLimiter`).

---

## 2. API Routes (`routes/api.php`)

```php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArticleController;

/** @var \Nexus\Routing\Router $router */

$router->post('/api/v1/login', [AuthController::class, 'login']);
$router->get('/api/v1/articles', [ArticleController::class, 'index']);
$router->get('/api/v1/articles/{id}', [ArticleController::class, 'show']);

// Authenticated API endpoints
$router->post('/api/v1/articles', [ArticleController::class, 'store']);
```

---

## 3. JWT Authentication Controller Implementation

```php
namespace App\Http\Controllers\Api;

use Nexus\Http\Controller;
use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Security\Jwt;
use Nexus\Security\Password;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request): Response
    {
        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::where('email', '=', $email)->first();

        if (!$user || !Password::verify($password, $user->password)) {
            return $this->json(['error' => 'Invalid email or password'], 401);
        }

        $token = Jwt::encode([
            'sub' => $user->id,
            'email' => $user->email,
            'exp' => time() + (86400 * 7), // 7 days expiration
        ], config('app.key'));

        return $this->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }
}
```

---

## 4. Next Steps

Explore traditional server-side applications in [17. Architecture Pattern: Server-Side Rendering (SSR)](17-arch-ssr.md).
