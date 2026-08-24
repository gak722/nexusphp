# Authentication

Authentication is the process of verifying a user's identity. NexusPHP provides a highly integrated, lightweight authentication subsystem that simultaneously supports **Stateful Session-based** authentication for web applications and **Stateless JWT-based** authentication for APIs, all handled by a unified authentication manager.

Unlike heavier frameworks that require complex Guard and Provider configurations, NexusPHP's authentication is designed to work seamlessly with the native `Model` architecture directly out of the box, requiring zero configuration files.

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

// Assuming you've manually verified the user's password...

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

To authenticate incoming API requests, you must instruct the `Auth` manager to inspect the request. Call `Auth::guard()`, passing the current request and the class name of your User model.

If the request contains a valid `Authorization: Bearer <token>` header, the `Auth` manager will decode the JWT (validating the HS256 signature and `exp` claim) and automatically retrieve the user matching the `sub` claim.

```php
$user = Auth::guard($request, User::class);

if ($user) {
    // JWT was valid and user was retrieved!
}
```

> [!TODO]
> Verify advanced JWT capabilities. Native token blacklisting (for explicit JWT invalidation before expiration) and automatic token refresh flows are not currently provided by the `Jwt` class and must be implemented at the application level if needed.

---

## Protecting Routes

> [!TODO]
> Verify bundled authentication middleware. While the framework provides the robust `Auth` class, an explicitly bundled `AuthMiddleware` class was not found in the initial framework scan. 

To protect your routes, you should create a custom middleware that utilizes the `Auth::guard()` method. Here is how you can easily implement one:

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
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            return redirect('/login');
        }

        return $next($request);
    }
}
```

Once created, you can apply this middleware to any route or route group in your `routes/web.php` or `routes/api.php` files.

---

## Next Steps

Now that your application is secure, you can explore other critical security features in NexusPHP:

- [CSRF Protection](csrf.md): Learn how NexusPHP protects your stateful forms.
- [Encryption & Hashing](encryption.md): Securely store sensitive data.
- [Rate Limiting](rate-limiting.md): Understand the rate limiter used by `Auth::attempt()`.
- [Middleware](middleware.md): Learn how to register your authentication middleware.
