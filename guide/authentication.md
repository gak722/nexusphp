# Authentication & Authorization

Authentication is the process of verifying a user's identity, while authorization determines what actions an authenticated user is permitted to perform. NexusPHP provides a highly integrated, lightweight security subsystem supporting **Session-based** web login, **Stateless JWT-based** API authentication, and declarative **Gate / Policy authorization rules**.

---

## Authentication Concepts

NexusPHP handles authentication via the static `Nexus\Security\Auth` manager. 

There are no separate "web" or "api" guards to configure. Instead, the `Auth::guard()` method intelligently inspects the incoming HTTP request. If it detects an `Authorization: Bearer <token>` header, it attempts a stateless JWT authentication. If no token is found, it automatically falls back to checking the active PHP session.

To interact with the authentication system, your user objects must extend the standard `Nexus\Database\Model` class.

---

## Password Hashing

NexusPHP securely hashes passwords using the native `Nexus\Security\Password` wrapper. 

By default, the framework utilizes the **Argon2id** algorithm (`PASSWORD_ARGON2ID`) if supported by your PHP environment. If Argon2id is unavailable, it securely falls back to **Bcrypt** (`PASSWORD_BCRYPT`).

To hash a password:
```php
use Nexus\Security\Password;

$hash = Password::hash('super-secret-password');
```

To verify a password:
```php
if (Password::verify('super-secret-password', $user->password)) {
    // Password matches
}
```

---

## Session-Based Authentication

Session-based authentication is typically used for web applications where the browser automatically stores and transmits session cookies.

### Attempting Login

You can use the `Auth::attempt()` method to automatically verify credentials against the database and log the user into the session.

This method requires:
1. The database column name to check (e.g., `'email'`)
2. The provided credential (e.g., `'user@example.com'`)
3. The raw password
4. The fully qualified class name of your User model.

```php
use Nexus\Security\Auth;
use App\Models\User;

$ipAddress = $request->ip(); // Optional: helps with rate limiting

if (Auth::attempt('email', 'user@example.com', 'password123', User::class, $ipAddress)) {
    // Authentication passed...
    return redirect('/dashboard');
}
```

> [!TIP]
> **Built-in Rate Limiting**: The `Auth::attempt()` method automatically integrates with `Nexus\Security\RateLimiter`. It restricts failed login attempts to 5 attempts per 5 minutes per username/IP combination to protect against brute-force attacks.

### Manual Login

If you already have a `Model` instance (e.g., after registering a new user), you can log them in manually using `Auth::login()`. This regenerates the session ID to prevent session fixation attacks and stores the user's primary key in `$_SESSION['auth_user_id']`.

```php
Auth::login($user);
```

### Checking Authentication Status

You can check if a user is currently authenticated anywhere in your application:

```php
if (Auth::check()) {
    // User is logged in
    $userId = Auth::id(); // Returns the primary key
    $user = Auth::user(); // Returns the authenticated Model instance
}
```

### Logging Out

To invalidate the session and remove the user's data from memory:

```php
Auth::logout();
```

---

## JWT-Based Authentication

For APIs and Single Page Applications (SPAs), NexusPHP provides a native, zero-dependency JWT implementation via `Nexus\Security\Jwt`.

The framework uses the highly secure **HS256** (HMAC with SHA-256) signature algorithm. The secret key used to sign the tokens is pulled automatically from your `.env` file via the `APP_KEY` variable.

### Generating a Token

Upon successful login in an API controller, you can manually generate a JWT for the user. By convention, you should store the user's primary key in the `sub` (Subject) claim.

```php
use Nexus\Security\Jwt;

$payload = [
    'sub' => $user->id,
    'role' => $user->role
];

$secret = env('APP_KEY');
$token = Jwt::encode($payload, $secret, 3600); // Expires in 1 hour (3600 seconds)

return response()->json(['token' => $token]);
```

> [!NOTE]
> The `Jwt::encode` method automatically appends the `iat` (Issued At) and `exp` (Expiration Time) claims based on the TTL you provide.

### Authenticating with JWT

To authenticate incoming API requests, call `Auth::guard()`, passing the current request and the class name of your User model.

If the request contains a valid `Authorization: Bearer <token>` header, the `Auth` manager will decode the JWT (validating the HS256 signature and `exp` claim) and automatically retrieve the user matching the `sub` claim.

```php
$user = Auth::guard($request, User::class);

if ($user) {
    // JWT was valid and user was retrieved!
}
```

---

## Authorization Policies (`Gate`)

In addition to authentication, NexusPHP provides `Nexus\Security\Gate` for authorizing user actions against abilities or policy classes.

### Defining Abilities

You may define authorization callbacks on the `Gate` using `Gate::define()`. The currently authenticated user is automatically passed as the first argument from `Auth::user()`:

```php
use Nexus\Security\Gate;

Gate::define('update-post', function ($user, $post) {
    return $user->id === $post->user_id;
});
```

### Checking Abilities

To authorize an action, use the `allows()`, `denies()`, or `authorize()` methods:

```php
if (Gate::allows('update-post', $post)) {
    // User is authorized to edit post
}

if (Gate::denies('update-post', $post)) {
    // User is NOT authorized
}

// Throws a RuntimeException if unauthorized
Gate::authorize('update-post', $post);
```

### Global Super-Admin Bypasses

You can define a `before()` callback that executes before all other authorization checks. If the callback returns a non-null result, it will override the check:

```php
Gate::before(function ($user, $ability, $arguments) {
    if ($user && $user->role === 'admin') {
        return true; // Super-admin receives full access
    }
    return null; // Fall through to standard checks
});
```

### Model Policies

You may also map entire Policy classes to models using `Gate::policy()`:

```php
namespace App\Policies;

class PostPolicy
{
    public function update($user, $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete($user, $post): bool
    {
        return $user->role === 'admin';
    }
}

// Register in your application bootstrap
Gate::policy(App\Models\Post::class, App\Policies\PostPolicy::class);
```

Then check the ability directly by passing the model instance:

```php
if (Gate::allows('delete', $post)) {
    // Executed PostPolicy::delete($user, $post)
}
```

---

## Protecting Routes

To protect your routes, create a custom middleware that utilizes the `Auth::guard()` method:

```php
namespace App\Http\Middleware;

use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Security\Auth;
use App\Models\User;

class Authenticate implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): Response
    {
        // Check JWT or Session automatically
        if (!Auth::guard($request, User::class)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            return redirect('/login');
        }

        return $next($request);
    }
}
```

---

## Next Steps

Explore other critical security features in NexusPHP:

- [CSRF Protection](csrf.md): Learn how NexusPHP protects your stateful forms.
- [Encryption & Hashing](encryption.md): Securely store sensitive data.
- [Rate Limiting](rate-limiting.md): Understand the rate limiter used by `Auth::attempt()`.
- [Middleware](middleware.md): Learn how to register your authentication middleware.
