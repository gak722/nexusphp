# Phase 8: Cache Drivers (File, Redis, APCu)

**Duration:** Week 10

---

## 1. What to Build

Phase 8 provides a high-performance caching layer with multiple backends (`File`, `Redis`, `APCu`). It supports unified cache contracts, key tagging, atomic lock acquisition, and full-page output caching.

### Core Deliverables:

- **`framework/Cache/CacheInterface.php`** — Unified cache contract (`get()`, `set()`, `has()`, `delete()`, `clear()`, `remember()`).
- **`framework/Cache/FileCache.php`** — File-system based cache driver with atomic file reads/writes and TTL serialization.
- **`framework/Cache/RedisCache.php`** — High-throughput cache driver utilizing native PHP `redis` extension.
- **`framework/Cache/ApcuCache.php`** — Zero-latency in-memory opcode cache driver utilizing APCu.
- **`framework/Cache/CacheManager.php`** — Cache driver factory and facade routing commands to active default backends.

---

## 2. How Current Implementation Fits with Previous Phase Implementation

- **Service Container Wire-up:** `CacheManager` is bound as a singleton inside Phase 0's `Container`.
- **Query Builder Integration:** Phase 4 `QueryBuilder` can wrap expensive queries in `Cache::remember()`.
- **Session & Security Integration:** Rate limiters (Phase 7) use the Redis/APCu cache store for cross-node hit counters.

---

## 3. How to Build

### Step-by-Step Implementation:

1. **`framework/Cache/CacheInterface.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Cache;

   interface CacheInterface
   {
       public function get(string $key, mixed $default = null): mixed;
       public function set(string $key, mixed $value, ?int $ttl = null): bool;
       public function has(string $key): bool;
       public function delete(string $key): bool;
       public function clear(): bool;
       public function remember(string $key, int $ttl, \Closure $callback): mixed;
   }
   ```

2. **`framework/Cache/FileCache.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Cache;

   class FileCache implements CacheInterface
   {
       public function __construct(protected string $storagePath)
       {
           if (!is_dir($this->storagePath)) {
               mkdir($this->storagePath, 0755, true);
           }
       }

       protected function getFilePath(string $key): string
       {
           return $this->storagePath . '/' . md5($key) . '.cache';
       }

       public function get(string $key, mixed $default = null): mixed
       {
           $path = $this->getFilePath($key);
           if (!file_exists($path)) return $default;

           $content = file_get_contents($path);
           $data = unserialize($content);

           if ($data['expires_at'] !== 0 && time() > $data['expires_at']) {
               $this->delete($key);
               return $default;
           }

           return $data['value'];
       }

       public function set(string $key, mixed $value, ?int $ttl = null): bool
       {
           $path = $this->getFilePath($key);
           $expiresAt = $ttl !== null ? time() + $ttl : 0;
           $payload = serialize(['expires_at' => $expiresAt, 'value' => $value]);

           return file_put_contents($path, $payload, LOCK_EX) !== false;
       }

       public function has(string $key): bool
       {
           return $this->get($key) !== null;
       }

       public function delete(string $key): bool
       {
           $path = $this->getFilePath($key);
           if (file_exists($path)) {
               return unlink($path);
           }
           return true;
       }

       public function clear(): bool
       {
           foreach (glob($this->storagePath . '/*.cache') as $file) {
               unlink($file);
           }
           return true;
       }

       public function remember(string $key, int $ttl, \Closure $callback): mixed
       {
           $value = $this->get($key);
           if ($value !== null) return $value;

           $value = $callback();
           $this->set($key, $value, $ttl);
           return $value;
       }
   }
   ```

3. **`framework/Cache/CacheManager.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Cache;

   use Nexus\Foundation\Application;

   class CacheManager
   {
       protected array $drivers = [];

       public function __construct(protected Application $app) {}

       public function driver(?string $name = null): CacheInterface
       {
           $name = $name ?? $_ENV['CACHE_DRIVER'] ?? 'file';

           if (!isset($this->drivers[$name])) {
               $this->drivers[$name] = $this->createDriver($name);
           }

           return $this->drivers[$name];
       }

       protected function createDriver(string $name): CacheInterface
       {
           return match ($name) {
               'file' => new FileCache($this->app->storagePath('cache')),
               'apcu' => new ApcuCache(),
               'redis' => new RedisCache($_ENV['REDIS_HOST'] ?? '127.0.0.1', (int)($_ENV['REDIS_PORT'] ?? 6379)),
               default => throw new \InvalidArgumentException("Unsupported cache driver [{$name}]"),
           };
       }
   }
   ```

---

## 4. Success Criteria

- [ ] File Cache driver serializes payloads and handles expiration cleanly.
- [ ] Redis and APCu drivers fallback gracefully when native PHP extensions are unavailable.
- [ ] `remember()` pattern avoids thundering herd problems by fetching and storing missing keys atomically.
- [ ] Cache invalidation and key clearing work cleanly across driver implementations.
