# Routing

This guide provides a comprehensive overview of how routing works in the NexusPHP framework, based on its actual implementation.

## Overview

Routing in NexusPHP is handled by the `Nexus\Routing\Router` class, which serves as the central hub for defining and dispatching routes. The framework strictly uses **explicit routing**, meaning every route must be manually defined. There is no automatic, convention-based, or attribute-based route discovery built-in. 

When a request enters the application, the `Router` matches the incoming HTTP method and URI against a collected set of route definitions (`Nexus\Routing\RouteCollection`) and dispatches the request to the matched handler.

## Basic Routing

By default, routes are defined in `routes/web.php`. This file is loaded automatically during the framework bootstrap process (`bootstrap/app.php`), and it receives a `$router` instance to configure available endpoints.

### Route Definition Methods

The `Router` provides explicit methods for registering routes for different HTTP verbs. Each method returns a `Nexus\Routing\Route` instance.

```php
// routes/web.php
use Nexus\Http\JsonResponse;

/** @var \Nexus\Routing\Router $router */

$router->get('/users', function () {
    return new JsonResponse(['users' => []]);
});

$router->post('/users', [UserController::class, 'store']);
$router->put('/users/{id}', [UserController::class, 'update']);
$router->patch('/users/{id}/status', [UserController::class, 'updateStatus']);
$router->delete('/users/{id}', [UserController::class, 'destroy']);
```

### Route Handlers

NexusPHP supports two main types of route handlers:

1. **Closures**: Anonymous functions that execute directly when the route is matched.
2. **Controller Methods**: Defined using an array syntax `[ControllerClass::class, 'methodName']`. The framework will automatically resolve the controller from the Service Container and invoke the specified method.


### Route Parameters

You can capture segments of the URI using route parameters. Parameters are defined by wrapping the segment name in curly braces (`{}`).

```php
$router->get('/users/{id}', [UserController::class, 'show']);
```

**Parameter Constraints (Regex):**
NexusPHP supports defining regular expression constraints directly inside the parameter definition using a colon `:` syntax.

```php
// The ID must be numeric
$router->get('/users/{id:[0-9]+}', function ($id) {
    // ...
});

// The slug must be alphanumeric
$router->get('/posts/{slug:[a-zA-Z0-9-]+}', [PostController::class, 'show']);
```

Optional parameters are not explicitly defined with a special syntax (like `?`), but you can manage optional segments through explicit route duplication or default parameters in the controller method if the regex allows empty matches (though standard practice is defining separate routes).

## Route Mapping to Request Delegates

When a request is dispatched (`$router->dispatch()`), the following sequence occurs:

1. **Method Filtering**: The router retrieves all candidate routes that match the incoming HTTP method (e.g., `GET`).
2. **Regex Matching**: It iterates through candidates and checks if the compiled regex pattern of the route URI matches the request URI.
3. **Parameter Extraction**: If matched, URI parameters are extracted by name based on the regex capture groups.
4. **Fallback (405 / 404)**: If no route matches the method and URI, it checks if the URI exists for *other* HTTP verbs. If so, it returns a `405 Method Not Allowed` response with an `Allow` header. Otherwise, it returns a `404 Not Found` response.

## Dependency Injection in Route Handlers

NexusPHP leverages its Service Container to provide powerful dependency injection for controller methods via the `Nexus\Http\ControllerDispatcher`.

When a controller method is invoked, the dispatcher uses reflection to inspect the method's parameters:

- **Route Parameters**: If a parameter name matches a captured route parameter (e.g., `$id`), the value from the URI is passed. The dispatcher automatically casts built-in types (`int`, `float`, `bool`) based on the parameter's type hint.
- **Form Requests**: If a parameter is typed as a `Nexus\Validation\FormRequest`, the container resolves it and automatically calls `validateResolved()`.
- **Models & Binding**: If a parameter is typed as a `Nexus\Database\Model`, the dispatcher calls `validateAndBind()` on the model class to hydrate it from the request.
- **Auto-wiring**: Any other non-builtin class type hinted in the method signature will be automatically resolved and injected from the Service Container (e.g., `Request`, services, or repositories).

```php
public function update(int $id, UpdateUserRequest $request, UserService $service)
{
    // $id is cast to an integer from the URI.
    // $request is validated automatically.
    // $service is injected from the container.
}
```

*Note: Closure-based routes currently receive route parameters sequentially. Complex auto-wiring is primarily handled for Controller methods via the `ControllerDispatcher`.*

## Different Approaches to Routing

### Resource Routing

For standard CRUD operations, NexusPHP provides a convenient `resource()` method that automatically registers the seven standard RESTful routes.

```php
$router->resource('photos', PhotoController::class);
```

This single line maps the following routes and assigns standard route names:
- `GET /photos` -> `index` (name: `photos.index`)
- `GET /photos/create` -> `create` (name: `photos.create`)
- `POST /photos` -> `store` (name: `photos.store`)
- `GET /photos/{id}` -> `show` (name: `photos.show`)
- `GET /photos/{id}/edit` -> `edit` (name: `photos.edit`)
- `PUT /photos/{id}` -> `update` (name: `photos.update`)
- `DELETE /photos/{id}` -> `destroy` (name: `photos.destroy`)

## Route Groups

You can group routes to apply shared attributes, such as a common URI prefix or shared middleware, without having to define them individually on every route.

```php
$router->group(['prefix' => 'admin', 'middleware' => [AuthMiddleware::class]], function ($router) {
    
    $router->get('/dashboard', [AdminController::class, 'dashboard']);
    $router->get('/users', [AdminController::class, 'users']);
    
});
```

Groups can be nested. Prefixes from nested groups are concatenated automatically.

## Route Middleware

Middleware can be assigned to individual routes or route groups. 

**Individual Assignment:**
Use the `middleware()` method on the returned `Route` instance.

```php
$router->get('/profile', [ProfileController::class, 'show'])
       ->middleware(AuthMiddleware::class);
```

Middleware can be provided as a string (class name to be resolved by the container) or as an array of class names. During dispatch, the router builds a `Nexus\Http\MiddlewareStack` specifically for the matched route.

## API Routes vs Web Routes

Unlike some frameworks that enforce a strict separation between `routes/web.php` and `routes/api.php` out-of-the-box, NexusPHP keeps things minimalistic.

By default, **only `routes/web.php` is loaded** in `bootstrap/app.php`. 

If your application requires separate API routes (e.g., with different middleware stacks for stateless authentication or rate limiting), you can easily implement this by adding a new route file and loading it in your bootstrap sequence or a Service Provider.

## Advanced Routing Features

### Named Routes & URL Generation

You can assign names to specific routes for easier URL generation throughout your application.

```php
$router->get('/profile/{id}', [ProfileController::class, 'show'])->name('profile.show');
```

To generate a URL to a named route, use the `Nexus\Routing\UrlGenerator`:

```php
// Assuming $urlGenerator is injected or resolved from the container
$url = $urlGenerator->route('profile.show', ['id' => 42]);
// Result: /profile/42
```

Query parameters can also be passed; any parameter that doesn't match a route segment will be appended as a query string.


## Best Practices

1. **Keep `web.php` Clean**: For large applications, avoid defining heavy closures in your route files. Defer logic to Controllers to keep route files readable and take advantage of the `ControllerDispatcher`'s dependency injection.
2. **Use Route Groups**: Logically group your routes by feature (e.g., admin panels, API endpoints) to centralize prefixing and middleware application.
3. **Name Your Routes**: Always name routes that you intend to link to. This decouples your URI structure from your views/redirects, allowing you to change URIs without breaking links.

## Next Steps

- [Middleware](middleware.md): Learn how to filter HTTP requests entering your application.
- [Controllers](controllers.md): Discover how to structure your request handling logic.
- [Service Container](container.md): Understand how dependencies are injected into your route handlers.
