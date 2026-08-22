# 11. Caching Subsystem

NexusPHP features a high-performance, unified Caching Engine supporting multiple backends: **File**, **APCu**, and **Redis**.

---

## 1. Unified Cache Interface (`Nexus\Cache\CacheInterface`)

All drivers implement `Nexus\Cache\CacheInterface`:

```php
namespace Nexus\Cache;

interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function put(string $key, mixed $value, int $ttlSeconds = 3600): bool;
    public function has(string $key): bool;
    public function forget(string $key): bool;
    public function flush(): bool;
    public function remember(string $key, int $ttlSeconds, callable $callback): mixed;
}
```

---

## 2. Using the Cache Manager (`Nexus\Cache\CacheManager`)

```php
use Nexus\Cache\CacheManager;

$cache = app(CacheManager::class)->driver(); // Resolves default driver from config

// Store item in cache for 10 minutes (600s)
$cache->put('site_stats', ['visits' => 10450], 600);

// Retrieve item from cache
$stats = $cache->get('site_stats', ['visits' => 0]);

// Atomic Remember Pattern (Fetch or Compute & Cache)
$popularArticles = $cache->remember('popular_articles', 3600, function () {
    return \App\Models\Post::where('views', '>', 1000)->get();
});

// Remove item from cache
$cache->forget('site_stats');
```

---

## 3. Cache Drivers Comparison

| Driver | Storage Mechanism | Best Use Case |
| :--- | :--- | :--- |
| **File** | Serialized PHP files in `storage/framework/cache/` | Single-server web apps without Redis/APCu setup. |
| **APCu** | Shared memory segment in PHP process space | Sub-millisecond read speed on dedicated VPS servers. |
| **Redis** | In-memory TCP key-value database | Multi-server clusters, distributed queue state & locking. |

---

## 4. Next Steps

Learn how asynchronous background tasks are managed in [12. Queue Workers & Background Jobs](12-queues.md).
