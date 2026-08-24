# Requests & Responses

This guide provides a comprehensive overview of how to work with HTTP requests and responses in the NexusPHP framework, based strictly on the actual implementation.

## Overview

In NexusPHP, all HTTP communication is represented by the `Nexus\Http\Request` and `Nexus\Http\Response` objects. These classes provide a clean, immutable, and developer-friendly interface to interact with PHP's underlying superglobals and response headers. 

*(Note: NexusPHP uses its own lightweight implementations for maximum performance and simplicity, rather than implementing the verbose PSR-7 standard.)*

---

## The Request Object

The `Nexus\Http\Request` class provides an object-oriented way to interact with the incoming HTTP request. 

### Class Definition

- **Namespace**: `Nexus\Http\Request`
- **Creation**: The request is created automatically early in the application lifecycle using `Request::createFromGlobals()`.
- **Properties**: The class uses PHP 8.2 `readonly public` properties for fast, immutable access to raw data:
  - `$method` (string)
  - `$uri` (string)
  - `$headers` (array)
  - `$query` (array - mapped from `$_GET`)
  - `$post` (array - mapped from `$_POST`)
  - `$files` (array - mapped from `$_FILES`)
  - `$cookies` (array - mapped from `$_COOKIE`)
  - `$rawBody` (string - mapped from `php://input`)

### Accessing the Request

The current request is automatically injected into your controller methods if you type-hint it in the method signature:

```php
use Nexus\Http\Request;

public function store(Request $request)
{
    // ...
}
```

### Retrieving Request Data

While you can access the public properties directly (e.g., `$request->query['page']`), the `Request` object provides several helper methods for safely retrieving input data.

#### Input Data (GET & POST & JSON)

To retrieve a value from anywhere in the payload (falling back to a default value if missing), use the `input()` method. This method aggregates data from the query string, POST body, and decoded JSON payload:

```php
// Retrieve a specific input value
$name = $request->input('name', 'Anonymous');

// Retrieve all input data (array_merge of query, post, and json)
$allData = $request->input(); // or $request->all();
```

#### File Uploads

To safely check for and retrieve uploaded files, use the `hasFile()` and `file()` methods:

```php
if ($request->hasFile('avatar')) {
    $file = $request->file('avatar'); // Returns array from $_FILES
    
    // Store using local disk storage abstraction
    $storedPath = \Nexus\Support\Storage::putFile('avatars', $file);
}
```

You may also validate file extensions directly against forbidden executable types:

```php
if (!$request->validateFiles(['jpg', 'png', 'pdf'])) {
    return response()->json(['error' => 'Invalid file extension uploaded'], 422);
}
```

#### JSON Payloads

If your application receives JSON requests, you can use the `json()` method to safely retrieve data. This decodes the `$rawBody` internally.

```php
// Retrieve a specific dot-notated JSON key
$age = $request->json('user.age', 18);

// Retrieve the entire decoded JSON payload as an array
$payload = $request->json();
```

#### Headers

To retrieve a header value, use the `header()` method. Headers are normalized internally (e.g., `HTTP_USER_AGENT` becomes `User-Agent`), so you can request them using standard HTTP casing.

```php
$token = $request->header('Authorization');
$custom = $request->header('X-Custom-Header', 'default_value');
```

#### Request Information

The request object provides several helpers to determine the context of the request:

```php
$host = $request->host(); // Returns the validated Host header or SERVER_NAME
$isJson = $request->isJson(); // Checks if Content-Type contains application/json
$expectsJson = $request->expectsJson(); // Checks Content-Type or Accept header
```

### Validation and Data Binding

The `Request` object natively integrates with the NexusPHP validation system.

```php
// Validate the request payload directly
$validatedData = $request->validate([
    'email' => 'required|email',
    'age' => 'integer'
]);
```

You can also automatically validate and bind the request payload to an object (like a DTO or Model) using `validateAndBind()`:

```php
// Instantiates UserDTO, validates the payload based on UserDTO's rules, and hydrates it
$userDto = $request->validateAndBind(UserDTO::class);
```

---

## The Response Object

The `Nexus\Http\Response` class is responsible for sending HTTP headers and content back to the client.

### Class Definition

- **Namespace**: `Nexus\Http\Response`

### Creating Responses

You can create a response directly or use the global `response()` helper function.

```php
use Nexus\Http\Response;

// Direct instantiation
$response = new Response('Hello World', 200, ['Content-Type' => 'text/plain']);

// Using the global helper
$response = response('Hello World', 200);
```

If you call the global `response()` helper without arguments, it returns a `Nexus\Http\ResponseFactory` which provides fluent methods to build specific response types.

### Building Responses

The `Response` object provides a fluent interface for modifying the response before it is sent.

```php
$response = response('Content')
    ->status(201)
    ->header('X-Custom-Header', 'Value')
    ->withCookie('theme', 'dark', time() + 3600);
```

#### Setting Cookies

The `withCookie()` method allows you to safely attach cookies to the response:

```php
$response->withCookie(
    name: 'session_token', 
    value: 'abc123xyz', 
    expire: time() + 86400, 
    path: '/', 
    domain: '', 
    secure: true, 
    httponly: true, 
    samesite: 'Lax'
);
```

---

## API Response Resources (`JsonResource`)

When building RESTful APIs, you may need a transformation layer between your database models and the JSON responses returned to your application's clients. NexusPHP provides `Nexus\Http\Resources\JsonResource` and `Nexus\Http\Resources\ResourceCollection` for this purpose.

### Defining a Resource

Extend `JsonResource` and implement the `toArray()` method:

```php
namespace App\Http\Resources;

use Nexus\Http\Resources\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(): array
    {
        return [
            'id' => $this->id, // Magic proxy to $this->resource->id
            'name' => $this->name,
            'email' => $this->email,
            'is_admin' => $this->when($this->role === 'admin', true, false),
            'created_at' => $this->created_at,
        ];
    }
}
```

> [!TIP]
> **Magic Property Delegation**: `JsonResource` transparently proxies property accesses (`$this->name`) and method calls directly to the underlying model or array resource, saving you from typing `$this->resource->name`.

### Returning Single Resources

In your controller, pass the model instance to your resource class:

```php
use App\Http\Resources\UserResource;
use App\Models\User;

public function show(int $id)
{
    $user = User::findOrFail($id);
    return (new UserResource($user))->response();
}
```

### Resource Collections

To transform a collection or array of models, use the static `collection()` method:

```php
public function index()
{
    $users = User::all();
    return UserResource::collection($users)->response();
}
```

This returns a JSON response formatted as:
```json
{
    "data": [
        { "id": 1, "name": "Alice", "email": "alice@example.com" },
        { "id": 2, "name": "Bob", "email": "bob@example.com" }
    ]
}
```

---

## Response Factory Methods

The `Nexus\Http\ResponseFactory` (accessible via `response()`) provides powerful semantic helpers:

#### JSON Responses

Returns a `Nexus\Http\JsonResponse` which automatically sets the `Content-Type` to `application/json` and safely encodes the array.

```php
return response()->json(['status' => 'ok']);
```

API helpers are also available:
- `response()->success($data, $status = 200)`
- `response()->created($data)`
- `response()->error($message, $status = 400, $details = null)`
- `response()->validationError($errors)`
- `response()->notFound()`

#### HTML Views

Renders a view template and returns it as a response with the correct `text/html` headers.

```php
return response()->view('home', ['title' => 'Welcome']);
// Alternatively, use the global view() helper:
// return view('home', ['title' => 'Welcome']);
```

#### Plain Text & Empty Responses

```php
return response()->text('Plain text content');
return response()->noContent(204); // Sends an empty response
```

#### Redirects

Returns a `Nexus\Http\RedirectResponse`. NexusPHP automatically sanitizes URLs against Open-Redirect vulnerabilities (stripping `javascript:` protocols or `//evil.com` scheme-relative attacks).

```php
// Redirect to a path
return response()->redirect('/dashboard');

// Redirect to a named route (resolving parameters automatically)
return response()->redirectRoute('profile.show', ['id' => 42]);
```

---

## Response Lifecycle

1. A `Response` object is returned from a Controller or Route Closure.
2. The response bubbles outward through the `MiddlewareStack`, allowing middleware to append headers (like CORS or Security headers).
3. The HTTP Kernel returns the final `Response` object to `public/index.php`.
4. Finally, `$response->send()` is called. This method:
   - Checks if headers are already sent.
   - Sets the HTTP response code.
   - Iterates over all headers and calls PHP's `header()` function.
   - Echoes the `$content`.

---

## Best Practices

1. **Use Type-Hinting**: Always type-hint `Nexus\Http\Request` in your controller methods instead of relying on PHP superglobals (`$_POST`, `$_GET`). It ensures your code is testable and secure.
2. **Use the Input Aggregator**: Use `$request->input('key')` rather than checking `$request->query` and `$request->post` manually. It provides a cleaner API and allows fallback defaults.
3. **Use the Response Factory & Resources**: For API endpoints, use `JsonResource` or the `response()->json()` helper. It ensures correct headers and standardized JSON formats are delivered.
4. **Avoid Direct `echo`**: Never use `echo`, `print`, or `header()` directly in your application code. Always return a `Response` object to ensure the middleware pipeline executes correctly.

---

## Next Steps

- [Routing](routing.md): See how incoming requests are matched to your code.
- [Controllers](controllers.md): Learn where to process these request and response objects.
- [Middleware](middleware.md): Discover how to intercept requests and modify responses globally.
- [Validation](validation.md): Learn more about the `$request->validate()` integration.
