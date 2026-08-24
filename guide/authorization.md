# Authorization

Authorization determines whether an authenticated user is permitted to perform a given action or access a specific resource. While [Authentication](authentication.md) verifies *who* a user is, authorization controls *what* they can do.

NexusPHP provides a clean, zero-dependency authorization subsystem through the static `Nexus\Security\Gate` manager. It supports ability-based closures (Gates), model-based Policies, global before hooks for super-admin privileges, and flexible user resolution.

---

## Authorization Concepts

NexusPHP handles authorization using the `Nexus\Security\Gate` class. 

The framework differentiates between authentication and authorization:
- **Authentication (`Nexus\Security\Auth`)**: Handles logging in, verifying session credentials, decoding JWT tokens, and identifying the current `Model` instance representing the user.
- **Authorization (`Nexus\Security\Gate`)**: Evaluates permissions and rules against the authenticated user and optional target resources (such as ORM models).

### Core Authorization Features
- **Closure Gates**: Simple ability checks defined via callbacks.
- **Model Policies**: Plain PHP classes mapped to model classes to organize resource permissions.
- **Global `before` Callbacks**: Hooks for super-admin bypasses or pre-evaluation rules.
- **User Resolver Customization**: Configurable resolver for applications with custom user contexts.

---

## Authorization Approaches

### Gates & Closures

Gates are authorization callbacks suitable for actions that are not necessarily tied to a specific resource model (e.g., viewing an administrative panel or exporting site statistics).

#### Defining Gates

You register authorization gates using `Gate::define()`. The currently authenticated user (resolved via `Auth::user()` by default) is automatically passed as the first argument to the callback:

```php
use Nexus\Security\Gate;

// Simple ability check
Gate::define('view-admin-dashboard', function ($user) {
    return $user !== null && $user->role === 'admin';
});

// Ability check with additional arguments
Gate::define('edit-settings', function ($user, string $section) {
    return $user->role === 'admin' || $section === 'profile';
});
```

#### Method Signatures

```php
namespace Nexus\Security;

use Closure;

class Gate
{
    public static function define(string $ability, callable|string $callback): void;
    public static function policy(string $class, string $policy): void;
    public static function allows(string $ability, mixed $arguments = []): bool;
    public static function denies(string $ability, mixed $arguments = []): bool;
    public static function authorize(string $ability, mixed $arguments = []): bool;
    public static function check(string $ability, mixed $arguments = []): bool;
    public static function before(Closure $callback): void;
    public static function setUserResolver(Closure $resolver): void;
}
```

---

### Model Policies

For resource-centric authorization (e.g., verifying if a user can update or delete a specific `Post` model), NexusPHP supports **Policies**.

#### Policy Conventions

In NexusPHP, policy classes do **not** need to extend any base class or implement any framework interface. They are plain PHP classes whose method names correspond to authorization ability names (e.g., `update`, `delete`, `view`).

Method signatures in a policy receive the authenticated `$user` as the first argument, followed by any arguments passed to the check (typically the target model instance):

```php
namespace App\Policies;

use App\Models\User;
use App\Models\Post;

class PostPolicy
{
    /**
     * Determine if the user can update the post.
     */
    public function update(?User $user, Post $post): bool
    {
        return $user !== null && $user->id === $post->user_id;
    }

    /**
     * Determine if the user can delete the post.
     */
    public function delete(?User $user, Post $post): bool
    {
        return $user !== null && ($user->id === $post->user_id || $user->role === 'admin');
    }
}
```

#### Registering Policies

Register policy mappings using `Gate::policy()` during application bootstrap (e.g., in a service provider or bootstrap script):

```php
use Nexus\Security\Gate;
use App\Models\Post;
use App\Policies\PostPolicy;

Gate::policy(Post::class, PostPolicy::class);
```

When evaluating an ability against a model instance (or model class name), `Gate::check()` looks up the registered policy for that class, instantiates the policy class (`new $policy()`), and invokes the corresponding method if it exists.

---

### Roles and Permissions

> [!NOTE]
> **Framework Architecture**: NexusPHP does **not** include pre-packaged role/permission database tables, migrations, or RBAC traits in the core framework. This design keeps the framework lightweight and allows developers full flexibility.

To implement Role-Based Access Control (RBAC), define roles or permissions as methods or attributes on your `User` model, then check them within Gates or Policies:

```php
// In App\Models\User model
public function hasRole(string $role): bool
{
    return $this->role === $role;
}

public function hasPermission(string $permission): bool
{
    return in_array($permission, $this->permissions ?? [], true);
}

// In Gate definition
Gate::define('publish-articles', function ($user) {
    return $user !== null && $user->hasPermission('articles.publish');
});
```

---

### Attribute-Based Authorization

> [!NOTE]
> **Framework Architecture**: NexusPHP does **not** currently provide built-in PHP 8 authorization attributes (such as `#[Authorize]` or `#[Can]`). Authorization checks must be performed explicitly within route closures, controllers, or middleware using static `Gate` calls.

---

## Checking Authorization

NexusPHP provides several static methods on `Nexus\Security\Gate` to evaluate permissions in your application.

### Resolving Users

By default, `Gate` resolves the target user by invoking `Nexus\Security\Auth::user()`. If your application uses a custom user resolution mechanism, you can override this behavior using `Gate::setUserResolver()`:

```php
use Nexus\Security\Gate;

Gate::setUserResolver(function () {
    return App\Context::getCurrentUser();
});
```

### Evaluation Methods

#### `Gate::allows()`
Returns `true` if the given ability is authorized for the user, or `false` otherwise:

```php
use Nexus\Security\Gate;

if (Gate::allows('update', $post)) {
    // Perform update operation
}
```

#### `Gate::denies()`
Returns `true` if the given ability is unauthorized for the user (the exact opposite of `allows()`):

```php
if (Gate::denies('update', $post)) {
    // Show unauthorized message or return error
}
```

#### `Gate::check()`
The underlying evaluation engine used by `allows()` and `denies()`. Performs resolution in the following order:
1. Executes any registered `Gate::before()` callback. If it returns non-null, that boolean result is returned immediately.
2. Checks if an ability callback was registered via `Gate::define()`.
3. If arguments are passed and match a registered model policy (via `Gate::policy()`), instantiates the policy and calls the method matching the ability name.
4. Returns `false` if no match is found or authorization fails.

```php
$isAllowed = Gate::check('edit-settings', ['section' => 'billing']);
```

#### `Gate::authorize()`
Checks if the ability is allowed. If authorization fails (returns `false`), `Gate::authorize()` throws a native PHP `\RuntimeException`:

```php
use Nexus\Security\Gate;

// Throws RuntimeException("This action is unauthorized.") if false
Gate::authorize('update', $post);

// Execution continues if authorized...
$post->update($request->all());
```

---

## Authorization Failures & Exceptions

When calling `Gate::authorize()`, an unauthorized attempt triggers a `\RuntimeException` with the message `"This action is unauthorized."`.

You can catch this exception in custom middleware or controller exception handlers to respond with an HTTP `403 Forbidden` response:

```php
use Nexus\Security\Gate;
use Nexus\Http\Response;

try {
    Gate::authorize('delete-user', $targetUser);
} catch (\RuntimeException $e) {
    return new Response(json_encode(['error' => $e->getMessage()]), 403, [
        'Content-Type' => 'application/json'
    ]);
}
```

---

## Authorization Middleware

> [!NOTE]
> **Framework Architecture**: NexusPHP does not ship with built-in route middleware specifically for authorization (such as `can` or `authorize`). Middleware can easily be built using `Nexus\Http\MiddlewareInterface`.

### Implementing Custom Authorization Middleware

To protect routes with authorization checks, create a custom middleware class:

```php
namespace App\Http\Middleware;

use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Security\Gate;

class AuthorizeMiddleware implements MiddlewareInterface
{
    protected string $ability;

    public function __construct(string $ability)
    {
        $this->ability = $ability;
    }

    public function handle(Request $request, \Closure $next): Response
    {
        if (Gate::denies($this->ability)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Forbidden'], 403);
            }

            return new Response('403 Forbidden', 403);
        }

        return $next($request);
    }
}
```

---

## Controller Authorization

The base controller class `Nexus\Http\Controller` provides response and view helpers, but does not contain a `$this->authorize()` method. Authorization checks inside controllers should call `Nexus\Security\Gate` directly.

### Single Controller Action Check

```php
namespace App\Http\Controllers;

use Nexus\Http\Controller;
use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Security\Gate;
use App\Models\Post;

class PostController extends Controller
{
    public function update(Request $request, int $id): Response
    {
        $post = Post::find($id);

        if ($post === null) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        // Authorize action using Gate
        if (Gate::denies('update', $post)) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $post->title = $request->input('title');
        $post->save();

        return $this->json($post);
    }
}
```

### Using `Gate::authorize()` in Controllers

```php
public function destroy(int $id): Response
{
    $post = Post::find($id);

    // Throws RuntimeException if unauthorized
    Gate::authorize('delete', $post);

    $post->delete();

    return $this->json(['status' => 'deleted']);
}
```

---

## Best Practices

### 1. Global Super-Admin Bypasses with `Gate::before()`
To grant administrative users access to all abilities without defining individual rules, register a `Gate::before()` callback during application startup:

```php
use Nexus\Security\Gate;

Gate::before(function ($user, string $ability, array $arguments) {
    if ($user !== null && $user->role === 'super-admin') {
        return true; // Authorize all actions for super-admins
    }

    return null; // Fall through to standard ability/policy checks
});
```

> [!IMPORTANT]
> The `before` callback must return `null` when a non-admin user is evaluated. Returning `false` from `before()` would explicitly deny the request and skip all subsequent ability or policy checks.

### 2. Choose Between Gates and Policies
- Use **Gates** for global or non-resource actions (e.g. `access-admin`, `view-analytics`).
- Use **Policies** for model-specific operations (e.g. `update`, `delete`, `publish` on `Post`, `Comment`, or `Order` models).

### 3. Organize Policies standardly
Keep policies inside an `App\Policies` namespace and map them explicitly in bootstrap files to maintain clarity.

---

## Common Use Cases

### 1. Protecting Admin Routes
Define an administrative ability and check it in custom middleware or route handlers:

```php
Gate::define('access-admin', function ($user) {
    return $user !== null && in_array($user->role, ['admin', 'manager'], true);
});
```

### 2. Resource Ownership (Edit Own Post)
Define a policy method that checks resource ownership:

```php
namespace App\Policies;

use App\Models\User;
use App\Models\Post;

class PostPolicy
{
    public function update(?User $user, Post $post): bool
    {
        return $user !== null && $user->id === $post->user_id;
    }
}
```

### 3. Multi-Tenancy & Team Permissions
Pass additional context arguments to `Gate::allows()` or `Gate::check()`:

```php
Gate::define('manage-team-billing', function ($user, int $teamId) {
    return $user !== null && $user->team_id === $teamId && $user->role === 'owner';
});

// Checking in code:
if (Gate::allows('manage-team-billing', $team->id)) {
    // Perform team billing action
}
```

---

## Next Steps

- [Authentication (Session & JWT)](authentication.md): Learn how NexusPHP authenticates users.
- [Middleware](middleware.md): Learn how to build and register custom HTTP middleware.
- [Models & ORM](orm.md): Explore NexusPHP ORM models and relationships.
