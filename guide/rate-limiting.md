# Rate Limiting

NexusPHP includes a simple but powerful rate limiting abstraction that protects your application routes and authentication endpoints from brute-force attacks and abuse.

The `Nexus\Security\RateLimiter` automatically detects whether the `CacheManager` is configured (using it for distributed multi-process rate limiting) or falls back to an in-memory array for rapid local testing.

---

## Basic Usage

The `RateLimiter` class is accessed statically and uses string keys to track attempts. Typically, you will combine a route name or action with the user's IP address or user ID to create a unique tracking key.

### Checking Attempts

Use `RateLimiter::tooManyAttempts()` to determine if a specific key has exceeded the allowed number of hits within a given timeframe (decay seconds).

```php
use Nexus\Security\RateLimiter;
use Nexus\Http\Request;
use Nexus\Http\Response;

$key = 'login_attempts:' . $request->ip();
$maxAttempts = 5;
$decaySeconds = 60; // 1 minute

if (RateLimiter::tooManyAttempts($key, $maxAttempts, $decaySeconds)) {
    return (new Response('Too many login attempts. Please try again later.', 429))
        ->header('Retry-After', (string) $decaySeconds);
}
```

### Recording Hits

If the user has not exceeded their rate limit, you should record the current action as a "hit" using `RateLimiter::hit()`.

```php
// Record the attempt and set it to expire in 60 seconds
RateLimiter::hit($key, $decaySeconds);
```

### Resetting Attempts

Upon a successful action (like a successful login), you should clear the rate limiter's cache for that key to reset their attempt count using `RateLimiter::resetAttempts()`.

```php
if (Auth::attempt('email', $email, $password, User::class)) {
    // Authentication successful, clear the rate limiter
    RateLimiter::resetAttempts($key);
    
    return redirect('/dashboard');
}
```

---

## Built-In Usage

NexusPHP leverages the `RateLimiter` internally within the `Auth::attempt()` manager. By default, failed login attempts are restricted to **5 attempts per 5 minutes** based on the username and the client's IP address to securely protect against brute-force password cracking.

---

## Storage Backends

The `RateLimiter` is tightly integrated with the [Cache System](cache.md). 

If your application uses the `file`, `apcu`, or `redis` cache driver, rate limiting works seamlessly across multiple HTTP requests and load-balanced application servers. 

If no cache is configured, the rate limiter uses a static PHP array. *Note: The static array only tracks hits during the current HTTP request lifecycle, which is primarily useful for integration testing or long-running CLI queue workers.*
