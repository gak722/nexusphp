# Controllers

This guide provides a comprehensive overview of how controllers work in the NexusPHP framework, based entirely on its actual implementation.

## Overview

In NexusPHP, controllers are the final destination for an HTTP request after it has successfully passed through the routing and middleware pipelines. They act as the "C" in the MVC (Model-View-Controller) architecture, responsible for receiving the request, processing data, interacting with services or models, and returning an appropriate HTTP response.

Controllers help you organize your request handling logic into discrete classes, rather than cluttering your route files with heavy closure functions.

## What is a Controller?

A controller is simply a PHP class whose methods (often called "actions") map to specific route endpoints. 
When a request matches a route that points to a controller, the framework instantiates the controller and invokes the specified method. 

Typical controller responsibilities include:
- Validating incoming request data (often delegated to FormRequests).
- Calling domain services or interacting with the database.
- Returning structured JSON data, rendering an HTML view, or issuing a redirect.

## Creating Controllers

### Controller Structure

In NexusPHP, a typical controller is a standard PHP class. By convention, they are placed in the `App\Http\Controllers` namespace.

While not strictly required, controllers can extend the `Nexus\Http\Controller` base class, which provides convenient helper methods for generating responses.

```php
namespace App\Http\Controllers;

use Nexus\Http\Controller;
use Nexus\Http\Request;
use Nexus\Http\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        // $this->json() is a helper provided by the base Controller
        return $this->json([
            'users' => [
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob'],
            ]
        ]);
    }
}
```

### The Base Controller

The `Nexus\Http\Controller` base class provides the following protected helper methods:

- `response(string $content = '', int $status = 200, array $headers = [])`: Returns a standard `Response`. If called without arguments, it returns a `Nexus\Http\ResponseFactory` instance for fluent response building.
- `json(mixed $data = [], int $status = 200, array $headers = [])`: Returns a `Nexus\Http\JsonResponse`.
- `redirect(string $url, int $status = 302)`: Returns a `Nexus\Http\RedirectResponse`.
- `view(string $name, array $data = [], int $status = 200, array $headers = [])`: Renders a view and returns it wrapped in a `Response`.

*(Note: The base controller does not provide built-in `validate()` or `authorize()` methods. Validation is handled either by injecting FormRequests or manually within the action.)*

## Controller Methods

Controller methods handle the logic for a specific endpoint. 

### Handling Requests

Unlike older frameworks where you might rely on global state, NexusPHP uses powerful reflection via the `Nexus\Http\ControllerDispatcher` to inject precisely what your method needs.

You do not have to strictly define a `$request` parameter if you don't need it. You simply type-hint the dependencies you require.

```php
public function show(int $id, Request $request)
{
    // $id is extracted from the URI route parameters
    // $request is injected by the container
}
```

## Dependency Injection in Controllers

NexusPHP provides advanced, automatic dependency injection into controller methods.

### Method Injection

When the `ControllerDispatcher` invokes a controller method, it inspects the type hints of the method's parameters:

1. **Route Parameters**: If a parameter name matches a captured route parameter (e.g., `{id}` in the route matches `$id` in the method signature), the value from the URI is passed. The dispatcher automatically casts the value to `int`, `float`, or `bool` if the parameter is type-hinted with a built-in type.
2. **Form Requests**: If you type-hint a class that extends `Nexus\Validation\FormRequest`, the container resolves it and automatically calls `validateResolved()` before your method even runs.
3. **Models**: If you type-hint a `Nexus\Database\Model`, the dispatcher calls `validateAndBind()` on the model class to hydrate it directly from the request data.
4. **Container Services**: Any other non-builtin class (like `Request`, repositories, or domain services) will be automatically resolved and injected from the Service Container.

```php
// Dependency Injection Example
public function update(
    int $id, // From route parameter
    UpdateUserRequest $request, // Validated automatically
    UserRepository $repository // Injected from container
) {
    $repository->save($id, $request->validated());
    return $this->json(['status' => 'success']);
}
```

### Constructor Injection

Because controllers themselves are instantiated out of the Service Container (if they are bound or auto-wireable), you can also use standard constructor injection for services that are needed across multiple methods in the controller.

## Response Handling

Controller methods should return a value that the framework can translate into an HTTP response. The framework handles different return types automatically:

- **`Nexus\Http\Response` Instances**: If you return an instance of `Response` (or its children like `JsonResponse`, `RedirectResponse`), it is sent back to the client exactly as-is.
- **Arrays or `\JsonSerializable`**: If you return an array or a JSON-serializable object, the framework automatically converts it into a `Nexus\Http\JsonResponse` (HTTP 200).
- **Strings (and other scalars)**: If you return a string, integer, or other scalar, the framework casts it to a string and wraps it in a standard `Nexus\Http\Response` (text/html).

### The Response Factory

If you need to build complex responses, you can use the `Nexus\Http\ResponseFactory`. This can be accessed by calling `$this->response()` without arguments from a class extending the base `Controller`.

The factory provides fluent methods such as:
- `json()`, `text()`, `noContent()`, `view()`
- `redirect()`, `redirectRoute()`
- API helpers: `success()`, `created()`, `error()`, `validationError()`, `notFound()`

## Controller Middleware

In NexusPHP, middleware is applied strictly at the **routing level**.

Unlike some frameworks, there is no `$this->middleware()` mechanism available within the controller's constructor. To apply middleware to a controller or its specific methods, you must attach it when defining the route:

```php
// Apply to a specific controller method
$router->get('/profile', [ProfileController::class, 'show'])
       ->middleware(\App\Http\Middleware\RequireAuth::class);

// Apply to a group of controller routes
$router->group(['middleware' => [\App\Http\Middleware\RequireAuth::class]], function($router) {
    $router->get('/settings', [SettingsController::class, 'index']);
    $router->post('/settings', [SettingsController::class, 'update']);
});
```

## Resource Controllers

NexusPHP provides built-in routing support for RESTful resource controllers. 

If you have a controller that handles standard CRUD operations for a resource, you can register it using the `$router->resource()` method in your route file:

```php
$router->resource('photos', PhotoController::class);
```

This automatically maps standard URIs and route names to the following controller methods:
1. `index()`: GET `/photos`
2. `create()`: GET `/photos/create`
3. `store()`: POST `/photos`
4. `show($id)`: GET `/photos/{id}`
5. `edit($id)`: GET `/photos/{id}/edit`
6. `update($id)`: PUT `/photos/{id}`
7. `destroy($id)`: DELETE `/photos/{id}`

You must ensure your controller actually implements these methods, as the router simply registers the routes expecting them to exist.

[TODO: Verify if single-action invokable controllers (using `__invoke`) are natively supported by the `ControllerDispatcher`.]

## Best Practices

1. **Keep Controllers Thin**: Controllers should primarily handle the HTTP transport layer (receiving requests, coordinating, returning responses). Push heavy business logic into Services or Action classes.
2. **Leverage Method Injection**: Use the `ControllerDispatcher`'s powerful method injection. Instead of manually pulling data from the `Request`, type-hint route parameters and `FormRequest` classes directly in your method signature.
3. **Use the Base Controller Helpers**: If returning JSON or views, extending the base `Controller` class makes your code cleaner and more readable by utilizing its helper methods.

## Next Steps

- [Routing](routing.md): Review how to point URIs to your controllers.
- [Requests & Responses](requests-responses.md): Deep dive into the HTTP request and response objects.
- [Validation & Data Binding](validation.md): Learn how to use FormRequests for automatic validation in controllers.
- [Views](views.md): See how to render HTML templates from your controllers.
