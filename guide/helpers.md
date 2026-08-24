# Global Helpers & Static Facades

NexusPHP provides a variety of global PHP functions and static facades. These components keep your code clean, expressive, and predictable while adhering to the framework's zero-dependency architectural design.

---

## Core Helpers

### `app()`
Returns the singleton instance of the `Nexus\Foundation\Application` container. If a class or interface name is provided, it resolves that dependency.

```php
$container = app();
$userService = app(UserService::class);
```

### `config()`
Gets a configuration value using dot notation with an optional default.

```php
$value = config('app.timezone', 'UTC');
```

### `env()`
Retrieves an environment variable value.

```php
$env = env('APP_ENV', 'production');
```

### `value()`
Returns the value given, executing closures if provided.

```php
$result = value(function () {
    return 'dynamic value';
});
```

---

## HTTP & Rendering Helpers

### `response()`
Creates a `Nexus\Http\Response` or returns the `ResponseFactory`.

```php
return response()->json(['status' => 'ok']);
```

### `view()`
Returns a rendered HTML view response.

```php
return view('welcome', ['name' => 'John']);
```

---

## Static Support Facades

NexusPHP provides static facade accessors for common core services.

### Storage Facade (`Nexus\Support\Storage`)

Provides a unified interface to read, write, move, and store files inside `storage/app/`.

```php
use Nexus\Support\Storage;

// Write text file
Storage::put('documents/notes.txt', 'File contents');

// Store uploaded HTTP file with automatic hash filename
$path = Storage::putFile('avatars', $request->file('avatar'));

// Get file contents
$content = Storage::get('documents/notes.txt');

// Check existence & delete
if (Storage::exists('documents/notes.txt')) {
    Storage::delete('documents/notes.txt');
}
```

### Session Facade (`Nexus\Support\Session`)

Manages session data with full dot-notation support and flash messaging.

```php
use Nexus\Support\Session;

// Put nested data
Session::put('user.settings.theme', 'dark');

// Retrieve nested data
$theme = Session::get('user.settings.theme', 'light');

// Check if key exists
if (Session::has('user.settings')) {
    // ...
}

// Store flash message for next request
Session::flash('status', 'Profile updated successfully!');

// Clear session
Session::forget('user');
```

### Logger Facade (`Nexus\Support\Log`)

Writes structured PSR-3 compliant logs to `storage/logs/app.log`. Supports string message placeholder interpolation.

```php
use Nexus\Support\Log;

Log::info('User {username} logged in from IP {ip}', [
    'username' => 'alice',
    'ip' => $request->ip()
]);

Log::error('Database transaction failed', ['exception' => $e->getMessage()]);
```

---

## String & HTML Helpers

### `e()`
Escapes strings for UTF-8 HTML output to prevent XSS attacks.

```php
echo e('<html>foo</html>'); // &lt;html&gt;foo&lt;/html&gt;
```

### `str()`
Returns a fluent `Nexus\Support\Str\Stringable` instance.

```php
$slug = str('  Hello World  ')->trim()->slug(); // "hello-world"
```

---

## Array & Collection Helpers

### `collect()`
Creates a new `Nexus\Support\Collection` instance.

```php
$collection = collect([1, 2, 3])->map(fn($n) => $n * 2);
```

### `blank()` & `filled()`
Determines whether a value is empty/blank or filled with content.

```php
blank(''); // true
filled('NexusPHP'); // true
```

### `tap()`
Passes a value to a callback and returns the value.

```php
return tap(new User, function ($user) {
    $user->name = 'John Doe';
    $user->save();
});
```

---

## Date & Time Helpers

### `now()` & `today()`
Creates a `Nexus\Support\DateTime\DateTime` instance for current time or date.

```php
$now = now();
$tokyoNow = now('Asia/Tokyo');
$today = today();
```
