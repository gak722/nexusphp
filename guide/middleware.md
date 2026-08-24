# Middleware

This guide explains how middleware works in the NexusPHP framework, based on its actual implementation.

## Overview

Middleware provides a convenient mechanism for inspecting and filtering HTTP requests entering your application. It acts as a series of layers that surround your application's core request handler.

When a request enters a NexusPHP application, it passes through a pipeline of middleware. Each middleware can inspect the request, modify it, reject it entirely (by returning a response immediately), or pass it deeper into the application. Once the core application generates a response, that response passes back outward through the same middleware pipeline, allowing middleware to modify the outgoing response.

Common use cases include exception handling, setting CORS headers, applying security headers, authentication, and rate limiting.

## Creating Middleware

In NexusPHP, middleware is a class that implements the `Nexus\Http\MiddlewareInterface`.

### The Middleware Interface

The contract requires a single method: `handle()`. 

```php
namespace Nexus\Http;

interface MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): Response;
}
```

- **`$request`**: The incoming `Nexus\Http\Request` object.
- **`$next`**: A closure representing the next middleware in the pipeline (or the final route handler).
- **Return Type**: The method **must** return a `Nexus\Http\Response`.

### Middleware Structure

Here is a basic example of how a custom middleware might look:

```php
namespace App\Http\Middleware;

use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\Response;

class EnsureTokenIsValid implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): Response
    {
        if ($request->header('X-API-Token') !== 'my-secret-token') {
            // Reject the request and return a response immediately
            return new Response('Unauthorized', 401);
        }

        // Pass the request deeper into the pipeline
        $response = $next($request);

        // (Optional) Modify the response before sending it back
        $response->setHeader('X-Token-Verified', 'true');

        return $response;
    }
}
```

## The Middleware Pipeline

NexusPHP uses an "onion" style pipeline executed by the `Nexus\Http\MiddlewareStack` class. 

### Execution Order

The pipeline is built from the outside in.
1. The request enters the outermost middleware.
2. If the middleware calls `$next($request)`, execution passes to the next middleware.
3. This continues until the core handler (the Router) is reached.
4. The Router dispatches the request to the Controller/Closure, generating a `Response`.
5. The `Response` bubbles back *out* through the middleware stack in reverse order.

Global middleware executes first (it wraps the entire application). Route-specific middleware executes only when a specific route is matched.

## Middleware Registration

### Global Middleware

Global middleware runs on **every** HTTP request entering the application. 

In NexusPHP, global middleware is bootstrapped directly in the `Nexus\Http\Kernel` class via the `bootstrapGlobalMiddleware` method.

Currently, NexusPHP automatically registers three global middleware in the following order:
1. `Nexus\Http\Middleware\ExceptionHandlerMiddleware`
2. `Nexus\Http\Middleware\SecurityHeadersMiddleware`
3. `Nexus\Http\Middleware\CorsMiddleware`

*(Note: There is no global configurable array for middleware in the base framework; global middleware is explicitly hardcoded into the Kernel's bootstrap sequence.)*

### Route-Specific Middleware

You can assign middleware to individual routes using the `middleware()` method on the `Route` instance returned by the Router.

```php
$router->get('/profile', [ProfileController::class, 'show'])
       ->middleware(\App\Http\Middleware\RequireAuthentication::class);
```

You can also assign multiple middlewares by passing an array:

```php
$router->post('/submit', [FormController::class, 'store'])
       ->middleware([
           \App\Http\Middleware\VerifyCsrfToken::class,
           \App\Http\Middleware\RateLimitSubmission::class
       ]);
```

### Route Groups

If you want to apply middleware to a group of routes, you can use the `group()` method. The router will automatically concatenate the group's middleware onto every route defined within the closure.

```php
$router->group(['middleware' => [\App\Http\Middleware\RequireAuthentication::class]], function ($router) {
    $router->get('/dashboard', [DashboardController::class, 'index']);
    $router->get('/settings', [SettingsController::class, 'index']);
});
```

*(Note: NexusPHP does not come with pre-defined named middleware groups like `web` or `api`. Grouping is handled entirely via explicit array definitions in your route files.)*

## Dependency Injection in Middleware

NexusPHP supports Dependency Injection for route-specific middleware. 

When the Router dispatches a request (`Router::runRoute`), it iterates through the assigned route middleware. If a middleware is defined as a string (a class name), the Router automatically resolves it using the Service Container (`$app->make($mw)`).

This allows you to type-hint dependencies in your middleware's constructor:

```php
class CheckUserRole implements MiddlewareInterface
{
    public function __construct(protected RoleRepository $roles) {}

    public function handle(Request $request, \Closure $next): Response
    {
        // $this->roles is automatically injected by the container
        // ...
    }
}
```

## Built-In Middleware

NexusPHP ships with three core middleware classes out-of-the-box, all registered globally by the Kernel:

### 1. `ExceptionHandlerMiddleware`
- **Path**: `Nexus\Http\Middleware\ExceptionHandlerMiddleware`
- **Purpose**: Wraps the entire application execution in a `try/catch` block. It catches any unhandled `Throwable` thrown deeper in the application, formats it appropriately (JSON for AJAX/API requests, HTML otherwise), and returns a graceful HTTP 500 error response.

### 2. `SecurityHeadersMiddleware`
- **Path**: `Nexus\Http\Middleware\SecurityHeadersMiddleware`
- **Purpose**: Automatically injects modern security headers (like X-Frame-Options, X-Content-Type-Options, Strict-Transport-Security, and Content-Security-Policy) into the outgoing response to protect against common web vulnerabilities. Configuration is typically managed via `config/security.php`.

### 3. `CorsMiddleware`
- **Path**: `Nexus\Http\Middleware\CorsMiddleware`
- **Purpose**: Handles Cross-Origin Resource Sharing. It intercepts `OPTIONS` preflight requests (returning a 204 No Content response) and injects the necessary `Access-Control-*` headers into the response based on the configuration found in `config/cors.php`.


## Best Practices

1. **Keep Middleware Focused**: A middleware should ideally do one thing (e.g., *only* check authentication, or *only* handle CORS).
2. **Return Responses Promptly**: If a condition fails (e.g., unauthorized), return the `Response` immediately rather than nesting deep `if` statements.
3. **Use the Container**: Take advantage of the Service Container for route middleware to inject services, repositories, or configuration rather than relying on global state.

## Next Steps

- [Routing](routing.md): Learn how to define routes and apply your middleware to them.
- [Controllers](controllers.md): Discover how to handle requests once they pass through the middleware pipeline.
- [Requests & Responses](requests-responses.md): Understand the objects passed into and returned from your middleware.
