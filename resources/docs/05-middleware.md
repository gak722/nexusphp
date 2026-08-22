# 05. HTTP Middleware Pipeline

Middleware provides a convenient mechanism for inspecting and filtering HTTP requests entering your application.

---

## 1. Middleware Interface

All middleware classes implement `Nexus\Http\MiddlewareInterface`:

```php
namespace Nexus\Http;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
```

---

## 2. Custom Middleware Example

Here is an example of an Authentication Guard middleware:

```php
namespace App\Http\Middleware;

use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Http\JsonResponse;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $token = $request->header('Authorization');

        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return new JsonResponse(['error' => 'Unauthenticated access'], 401);
        }

        // Pass request to the next middleware layer or controller
        return $next($request);
    }
}
```

---

## 3. Built-In Core Middleware

NexusPHP includes several pre-built security and utility middleware out of the box:

| Middleware | Class | Purpose |
| :--- | :--- | :--- |
| **CORS** | `Nexus\Http\Middleware\CorsMiddleware` | Enforces Cross-Origin Resource Sharing policies and pre-flight handling. |
| **Security Headers** | `Nexus\Http\Middleware\SecurityHeadersMiddleware` | Attaches HSTS, X-Frame-Options, CSP, and X-Content-Type-Options headers. |
| **Exception Handler** | `Nexus\Http\Middleware\ExceptionHandlerMiddleware` | Catches uncaught throwables and formats structured JSON or HTML debug pages. |
| **CSRF Protection** | `Nexus\Security\Csrf` | Validates session CSRF tokens on `POST`, `PUT`, `DELETE` requests. |
| **Rate Limiter** | `Nexus\Security\RateLimiter` | Enforces client IP throttling rules per minute. |

---

## 4. Registering Global & Route Middleware

```php
use Nexus\Http\MiddlewareStack;
use App\Http\Middleware\AuthMiddleware;

$stack = new MiddlewareStack();

// Add global middleware
$stack->add(new \Nexus\Http\Middleware\SecurityHeadersMiddleware());
$stack->add(new \Nexus\Http\Middleware\CorsMiddleware());

// Add custom route middleware
$stack->add(new AuthMiddleware());

// Process request through stack pipeline
$response = $stack->handle($request, function ($req) {
    return new Response('Hello World');
});
```

---

## 5. Next Steps

Learn how to build user interfaces in [06. Views & Native Rendering Engine](06-views.md).
