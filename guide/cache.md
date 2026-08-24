# Cache System

NexusPHP provides a unified, extensible caching API supporting various backend drivers to improve your application's performance. The cache system is highly optimized and offers a clean abstraction layer over File, APCu, and Redis storage.

---

## Configuration

Cache configuration is automatically loaded from the `config/cache.php` file (or driven directly by your `.env` variables).

By default, the `file` driver is used, storing serialized cache data in `storage/cache/`. You can change this by modifying the `.env` file:

```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### Available Drivers

1. **`file`**: Serializes objects and stores them on the local filesystem (safest default).
2. **`apcu`**: High-performance in-memory caching utilizing the PHP APCu extension. (Falls back to `file` if the extension is disabled).
3. **`redis`**: Enterprise-grade caching utilizing the `phpredis` extension. (Falls back to `file` if the extension is missing).

---

## Basic Usage

You can resolve the cache instance using the `Nexus\Cache\CacheManager` via dependency injection or the global `app()` helper.

```php
use Nexus\Cache\CacheManager;

$cache = app(CacheManager::class)->driver();
```

If you wish to interact with a specific store rather than the default, you may pass the name of the driver:

```php
$redisCache = app(CacheManager::class)->driver('redis');
```

### Retrieving Items

To retrieve an item from the cache, use the `get` method. You may pass a default value as the second argument, which is returned if the item does not exist:

```php
$value = $cache->get('user_profiles');

// With a default fallback
$value = $cache->get('active_theme', 'light');
```

### Storing Items

You may use the `set` method to store items in the cache. You must pass a Time To Live (TTL) in seconds as the third argument:

```php
// Store for 10 minutes (600 seconds)
$cache->set('active_theme', 'dark', 600);
```

### Checking for Existence

The `has` method determines if an item exists in the cache:

```php
if ($cache->has('active_theme')) {
    // ...
}
```

### Removing Items

You may remove items from the cache using the `delete` method, or clear the entire cache using `clear`:

```php
$cache->delete('active_theme');

// Clear ALL cached items
$cache->clear();
```

---

## Cache Remember Pattern

A very common pattern is retrieving an item from the cache, but if it doesn't exist, fetching it from the database and storing it. The `remember` method handles this gracefully:

```php
$users = $cache->remember('users.all', 3600, function () {
    return \App\Models\User::all();
});
```

If the `users.all` key exists, its value is instantly returned. If it doesn't exist, the closure is executed, the result is saved in the cache for 3600 seconds (1 hour), and then the result is returned.
