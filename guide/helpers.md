# Global Helpers

NexusPHP provides a variety of global "helper" PHP functions. These functions are typically used by the framework itself, but you are free to use them in your own applications to keep your code clean and concise.

These helpers are automatically loaded by the framework's `bootstrap/helpers.php` file, meaning they are always available anywhere in your application.

---

## Core Helpers

### `app()`
The `app` function returns the singleton instance of the `Nexus\Foundation\Application` container. If you pass a class or interface name to the function, it will resolve that specific dependency from the container.

```php
$container = app();
$userService = app(UserService::class);
```

### `config()`
The `config` function gets the value of a configuration variable using dot notation. If the configuration value does not exist, an optional default value is returned.

```php
$value = config('app.timezone');
$value = config('app.timezone', 'UTC');
```

### `env()`
The `env` function retrieves the value of an environment variable or returns a default value.

```php
$env = env('APP_ENV');
$env = env('APP_ENV', 'production');
```

### `value()`
The `value` function returns the value it is given. However, if you pass a `Closure` to the function, the `Closure` will be executed and its returned value will be returned.

```php
$result = value(true); // Returns true
$result = value(function () {
    return false; // Returns false
});
```

---

## HTTP & Rendering Helpers

### `response()`
The `response` function creates a new `Nexus\Http\Response` instance or returns an instance of the `ResponseFactory` if called with no arguments.

```php
return response('Hello World', 200, ['X-Header' => 'Value']);

// Or use the factory for JSON
return response()->json(['status' => 'ok']);
```

### `view()`
The `view` function returns a `Response` containing a rendered HTML view template.

```php
return view('welcome', ['name' => 'John']);
```

---

## String & HTML Helpers

### `e()`
The `e` function runs PHP's `htmlspecialchars` function with `ENT_QUOTES | ENT_SUBSTITUTE` flags, specifically for UTF-8 encoded strings. It is extremely useful for preventing XSS in templates.

```php
echo e('<html>foo</html>'); // &lt;html&gt;foo&lt;/html&gt;
```

### `str()`
The `str` function returns a new `Nexus\Support\Str\Stringable` instance for a given string, allowing for fluent string manipulation. If no argument is provided, it returns the base `Nexus\Support\Str` factory.

```php
$string = str('  NexusPHP  ')->trim()->lower();
```

---

## Array & Object Helpers

### `collect()`
The `collect` function creates a new `Nexus\Support\Collection` instance from the given array or iterable.

```php
$collection = collect([1, 2, 3])->map(function ($item) {
    return $item * 2;
});
```

### `blank()`
The `blank` function checks whether the given value is "blank" (null, an empty string, an empty array, or a string containing only whitespace). Note that booleans and numbers (like `0` or `false`) are **not** considered blank.

```php
blank(''); // true
blank('   '); // true
blank(null); // true
blank([]); // true
blank(0); // false
blank(false); // false
```

### `filled()`
The `filled` function is the exact inverse of the `blank` function.

```php
filled(''); // false
filled('Nexus'); // true
```

### `tap()`
The `tap` function accepts two arguments: an arbitrary `$value` and a `Closure`. The `$value` will be passed to the `Closure` and then returned by the `tap` function. The return value of the Closure is irrelevant.

```php
return tap(new User, function ($user) {
    $user->name = 'John Doe';
    $user->save();
});
```

---

## Time & Date Helpers

### `now()`
The `now` function creates a new `Nexus\Support\DateTime\DateTime` instance for the current time. You may optionally pass a specific timezone string.

```php
$now = now();
$tokyoTime = now('Asia/Tokyo');
```

### `today()`
The `today` function creates a new `Nexus\Support\DateTime\DateTime` instance for the current date. The time will be exactly set to `00:00:00`.

```php
$today = today();
```
