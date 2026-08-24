# Request Lifecycle

## Overview

Understanding the request lifecycle is crucial for mastering NexusPHP. By knowing exactly how an incoming HTTP request navigates through the framework, you can better structure your code, debug effectively, and write powerful middleware. 

Unlike many other frameworks that obscure their lifecycle behind layers of magic methods and massive dependency trees, NexusPHP's request flow is completely transparent and designed for maximum speed.

This guide provides a verified, step-by-step trace of how NexusPHP handles an HTTP request, from the entry point to the final response.

## The Lifecycle: Step by Step

### Stage 1: Entry Point (`public/index.php`)

Every request to your NexusPHP application is funneled through the front controller: `public/index.php`. 

**What Happens:**
The file requires the bootstrap script, retrieves the Application instance, resolves the HTTP Kernel via the Service Container, builds a Request object from PHP globals, handles it, and sends the Response back to the browser.

**Key Files/Classes:**
- `public/index.php`
- `Nexus\Foundation\Application`
- `Nexus\Http\Kernel`
- `Nexus\Http\Request`

**Code Snippet (`public/index.php`):**
```php
require __DIR__ . '/../bootstrap/app.php';

$app = Nexus\Foundation\Application::getInstance();
$kernel = $app->make(Nexus\Http\Kernel::class);

$request = Nexus\Http\Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
```

### Stage 2: Bootstrapping & Configuration (`bootstrap/app.php`)

Before the Kernel can handle the request, the framework must boot up. This occurs within `bootstrap/app.php`.

**What Happens:**
1. **Autoloading:** Manual PSR-4 autoloaders are registered for the `Nexus\` and `App\` namespaces (ensuring the framework can run even without Composer's autoloader).
2. **Helpers & Env:** Core helper files and validation rules are required, and environment variables are loaded via `Nexus\Support\Env::load()`.
3. **Application & Config:** The `Application` (which extends the Service Container) is instantiated. All files within the `config/` directory are loaded into the `Nexus\Foundation\Config` repository.
4. **Service Registration:** Core singletons (`Kernel`, `Router`) are bound. Configured services from `config/services.php` are registered via `$app->registerConfiguredServices()`.
5. **Routing Discovery:** The `routes/web.php` file is required, registering all application routes directly onto the Router instance.

**Key Files/Classes:**
- `bootstrap/app.php`
- `Nexus\Support\Env`
- `Nexus\Foundation\Config`

### Stage 3: The HTTP Kernel & Global Middleware (`Nexus\Http\Kernel`)

Once booted, the request is passed to the HTTP Kernel's `handle()` method.

**What Happens:**
The Kernel initializes a global `MiddlewareStack`. By default, it bootstraps essential global middleware: `ExceptionHandlerMiddleware`, `SecurityHeadersMiddleware`, and `CorsMiddleware`. The request is passed into this pipeline. The innermost callback of this pipeline delegates the request to the Router.

**Key Files/Classes:**
- `Nexus\Http\Kernel`
- `Nexus\Http\MiddlewareStack`

### Stage 4: Routing & Route Middleware (`Nexus\Routing\Router`)

The request reaches the Router, which determines which controller should execute.

**What Happens:**
The Router compares the incoming request URI and method against its `RouteCollection`. Once a match is found, the Router creates a *new* `MiddlewareStack` specific to that route. It executes any route-specific middleware before finally invoking the route's action.

**Key Files/Classes:**
- `Nexus\Routing\Router`
- `Nexus\Routing\RouteCollection`

### Stage 5: Controller Resolution & Execution (`Nexus\Http\ControllerDispatcher`)

If the route action points to a Controller (e.g., `[UserController::class, 'show']`), the `ControllerDispatcher` takes over.

**What Happens:**
Using PHP Reflection, the dispatcher inspects the controller method's parameters. It automatically performs powerful dependency injection:
- It injects route parameters directly.
- It instantiates and validates `Nexus\Validation\FormRequest` classes.
- It performs Model Binding if a `Nexus\Database\Model` is type-hinted.
- It resolves any other type-hinted classes from the Service Container.

Once dependencies are gathered, the method is invoked.

**Key Files/Classes:**
- `Nexus\Http\ControllerDispatcher`

**Code Snippet (`ControllerDispatcher.php` excerpt):**
```php
$result = $reflector->invokeArgs($controller, $args);

if ($result instanceof Response) {
    return $result;
}

if (is_array($result) || $result instanceof \JsonSerializable) {
    return new JsonResponse($result);
}

return new Response((string) $result);
```

### Stage 6: Response Generation (`Nexus\Http\Response`)

As seen in the snippet above, whatever your controller returns is automatically formatted. If you return an array or object, it is wrapped in a `JsonResponse`. If it is a string, it is wrapped in a standard `Response`.

**What Happens:**
Finally, back in `public/index.php`, the `$response->send()` method is called. This method emits the HTTP status code, iterates and emits all headers, and finally echoes the content to the client.

**Key Files/Classes:**
- `Nexus\Http\Response`
- `Nexus\Http\JsonResponse`

## Visual Flow Diagram

```text
Request 
  │
  ▼
[ public/index.php ] ──▶ Requires bootstrap/app.php 
                             │
                             ├── Registers Autoloaders
                             ├── Loads .env & configs
                             └── Registers Services & Routes
  │
  ▼
[ Nexus\Http\Kernel ] ──▶ Executes Global Middleware Stack
                             │
                             ├── ExceptionHandlerMiddleware
                             ├── SecurityHeadersMiddleware
                             └── CorsMiddleware
  │
  ▼
[ Nexus\Routing\Router ] ──▶ Matches Route & Executes Route Middleware Stack
  │
  ▼
[ Nexus\Http\ControllerDispatcher ] ──▶ Resolves DI, Models, & FormRequests
  │
  ▼
[ Controller Method ] ──▶ Executes Business Logic
  │
  ▼
[ Response Generation ] ──▶ Formats return value into Nexus\Http\Response
  │
  ▼
[ public/index.php ] ──▶ $response->send() (Headers & Content sent to client)
```

## Key Classes and Interfaces

- **`Nexus\Foundation\Application`**: Extends the Container. It holds shared instances and bootstraps the framework.
- **`Nexus\Http\Request`**: An immutable representation of the incoming HTTP request.
- **`Nexus\Http\MiddlewareStack`**: A robust pipeline implementation for executing middleware arrays sequentially.
- **`Nexus\Http\ControllerDispatcher`**: The reflection engine responsible for auto-wiring dependencies into controller methods.

## Common Extension Points

Because NexusPHP is highly decoupled, you can hook into this lifecycle at several verified points:
- **Service Registration**: You can define singletons, transients, or scoped services within `config/services.php` that the `Application` will automatically register during boot.
- **Global Middleware**: You can append to the pipeline in the `Kernel` (though you must currently extend or modify the `bootstrapGlobalMiddleware` method).
- **Route Middleware**: You can apply custom middleware directly to routes using `$router->get(...)->middleware(MyMiddleware::class)`.

## Summary
- NexusPHP handles requests via a highly deterministic, functional pipeline.
- The `bootstrap/app.php` file is the heart of initialization, running before the Kernel.
- Dependencies are automatically resolved at the controller level using Reflection.
- Controller return values are effortlessly wrapped into standard HTTP responses.

---

**Next Steps:** To understand how dependencies are managed and injected during Stage 5, read the [Service Container](container.md) guide. You can also dive deeper into [Routing](routing.md) and [Middleware](middleware.md).
