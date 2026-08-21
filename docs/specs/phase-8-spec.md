# Copilot Spec: Phase 8 — Cache Subsystem (File, Redis, APCu)

## Objective
Implement unified cache contract (`CacheInterface`), atomic file store (`FileCache`), Redis adapter (`RedisCache`), in-memory opcode store (`ApcuCache`), and default driver factory (`CacheManager`).

## Target Files to Create / Modify
- `framework/Cache/CacheInterface.php`
- `framework/Cache/FileCache.php`
- `framework/Cache/RedisCache.php`
- `framework/Cache/ApcuCache.php`
- `framework/Cache/CacheManager.php`

---

## Detailed Specifications

### 1. `framework/Cache/CacheInterface.php`
- Methods: `get()`, `set()`, `has()`, `delete()`, `clear()`, `remember()`.

### 2. `framework/Cache/FileCache.php`
- Stores values using `serialize()` wrapped with expiration timestamps.
- File writes MUST use `LOCK_EX` to prevent race conditions during concurrent writes.

---

## Copilot Validation Rules
- [ ] `remember(key, ttl, closure)` MUST fetch and store atomically if missing.
- [ ] Non-file drivers MUST fallback cleanly to `FileCache` if PHP extensions (`redis`, `apcu`) are missing.
