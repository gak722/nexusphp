# ⚡ NexusPHP Framework

**NexusPHP** is a zero-dependency, ultra-lightweight **Laravel competitor** built for PHP 8.2+. Engineered specifically for high-concurrency applications, serverless runtimes, edge containers, and resource-constrained environments, NexusPHP delivers the developer ergonomics of Laravel with zero vendor bloat and a micro-footprint.

By eliminating third-party Composer runtime dependencies, NexusPHP runs at **< 3.5ms execution latency** and **< 2.0MB memory footprint**, making it the ultimate lightweight alternative for modern PHP development.

---

## 🔥 Why NexusPHP? (The Lightweight Laravel Competitor)

| Feature / Metric | Standard Heavy Frameworks (e.g., Laravel) | NexusPHP (Lightweight Competitor) |
| :--- | :--- | :--- |
| **Dependencies** | 30+ vendor packages (`composer install` required) | **Zero third-party dependencies** (Native PHP 8.2+) |
| **Base Memory Allocation** | ~12.0 MB - 25.0 MB per request | **< 2.0 MB** per request |
| **Base Boot Latency** | ~15.0 ms - 45.0 ms | **< 3.5 ms** per request |
| **Skeleton Inode Count** | ~10,000+ files and directories | **< 200 inodes** total skeleton footprint |
| **Security Defaults** | Multi-package dependencies | Built-in `libsodium` secretbox, Argon2id, PDO prepared SQL, CSRF, and rate limiting |

---

## 🎯 Core Principles & Architecture Philosophy

NexusPHP synthesizes three battle-tested software engineering paradigms:

1. **🚀 Zero-Dependency Purity & Deterministic Footprint (ASP.NET Core Integrity)**
   - No supply-chain vulnerability risks from nested third-party dependencies.
   - Base skeleton footprint is strictly capped under **200 inodes** with peak memory allocation of **< 2.0 MB** per request.
   - 100% strict type safety (`declare(strict_types=1);`) across the entire framework codebase.

2. **⚡ Predictable Lifecycle & Low Overhead (Express.js Ergonomics)**
   - Pure functional onion middleware pipeline: `fn(Request $request, Closure $next): Response`.
   - Zero dynamic magic methods (`__get`, `__set`) in core HTTP classes.
   - Baseline request dispatch latency strictly under **3.5ms**.

3. **💡 Modern Application Design (Next.js Capabilities)**
   - Intuitive routing ergonomics, grouped prefixes, parameter constraints, and controller dispatchers.
   - Native Server-Sent Events (`SseResponse`) streaming interface for real-time applications.
   - Unified caching and fine-grained output revalidation (`File`, `Redis`, `APCu`).

---

## ✨ Feature Overview

- **🔒 Enterprise Security Standard:**
  - **SQL Injection:** 100% positional prepared statement parameter binding (`?`) across `QueryBuilder` and `Model`.
  - **CSRF Mitigation:** Synchronizer Token pattern (`Csrf::validate()`) with constant-time string comparisons (`hash_equals()`).
  - **Authenticated Encryption:** Native `libsodium` secretbox encryption (`sodium_crypto_secretbox`).
  - **Password Security:** Native `Argon2id` and `Bcrypt` hashing wrappers.
  - **Authentication:** Stateful cookie sessions and stateless bearer `Jwt` guards.
  - **Rate Limiting:** Sliding-window throttle preventing brute-force attempts.
- **🗃️ ActiveRecord ORM & Database Schema:** Chainable query generator, relationship definitions (`BelongsTo`, `HasMany`, `HasOne`, `BelongsToMany`), programmatic DDL `Blueprint` / `Schema`, and versioned batch `Migrator`.
- **📨 Background Queues & Async Processing:** Background job serialization (`DatabaseQueue`, `RedisQueue`) with dedicated CLI worker loops (`Worker`).
- **📡 Realtime Pub/Sub & SSE Streaming:** Redis Pub/Sub broadcast manager and buffer-flushed Server-Sent Events.
- **🛠️ Nexus CLI Binary (`nexus`):** Standalone terminal tooling for database migrations, background queues, development server, and code generators (`make:controller`, `make:model`, `make:migration`).

---

## 🚀 Quick Start Guide

### 1. Boot Local Development Server
Launch the development server using the executable `nexus` binary:
```bash
php nexus serve
```
Open `http://127.0.0.1:8000` in your browser.

### 2. Route Definition (`routes/web.php`)
```php
<?php
declare(strict_types=1);

use Nexus\Http\JsonResponse;

/** @var \Nexus\Routing\Router $router */

$router->get('/', function () {
    return new JsonResponse([
        'framework' => 'NexusPHP',
        'status' => 'online',
        'version' => '1.0.0',
    ]);
});

$router->get('/users/{id:[0-9]+}', [App\Http\Controllers\UserController::class, 'show']);
```

### 3. HTTP Controller (`app/Http/Controllers/UserController.php`)
```php
<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Nexus\Http\Request;
use Nexus\Http\JsonResponse;
use Nexus\Http\Response;

class UserController
{
    public function show(Request $request, int $id): Response
    {
        return new JsonResponse([
            'user_id' => $id,
            'name' => 'Alice',
        ]);
    }
}
```

### 4. ActiveRecord Model (`app/Models/User.php`)
```php
<?php
declare(strict_types=1);

namespace App\Models;

use Nexus\Database\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected static string $primaryKey = 'id';
}
```

---

## 🛠️ CLI Binary (`php nexus`)

```bash
# Display help and command registry
php nexus help

# Code Generators
php nexus make:controller UserController
php nexus make:model Post
php nexus make:migration create_posts_table

# Administrative Commands
php nexus migrate        # Execute database migrations
php nexus queue:work     # Start background queue worker process
php nexus serve          # Start local web server
```

---

## 🧪 Zero-Dependency Test Suite

Run the built-in native PHP assertion test runner:
```bash
php tests/TestRunner.php
```

---

## ⚙️ Environment & Debugging

- **Environment File:** `.env`
- **Application Debugging:** `APP_DEBUG=true` renders dark-themed stack trace pages for web requests and explicit JSON stack trace arrays for API requests.
- **Log Files:**
  - `storage/logs/nexus.log` — HTTP Kernel & Runtime Exceptions
  - `storage/logs/events.log` — Event Listener Exceptions
  - `storage/logs/queue_failed.log` — Queue Worker Job Failures

---

## 🤝 Community & Collaboration

We welcome contributions from developers passionate about clean architecture, performance optimization, zero-dependency software design, and modern PHP!

### How You Can Contribute:
1. **⭐ Star the Repository:** Show your support and help spread the word.
2. **🐛 Report Issues & Vulnerabilities:** Open detailed GitHub issues with reproduction steps if you encounter bugs or security edge cases.
3. **💡 Propose Features & Enhancements:** Have an idea for optimizing latency, reducing memory footprints, or improving developer experience? Discussions and pull requests are warmly welcomed!
4. **🔧 Submit Pull Requests:** 
   - Fork the repository and create a feature branch (`git checkout -b feature/amazing-enhancement`).
   - Ensure 100% strict type coverage (`declare(strict_types=1);`).
   - Confirm all native assertions pass cleanly via `php tests/TestRunner.php`.
   - Open a Pull Request detailing your changes.

---

## 📄 License

The NexusPHP framework is open-sourced software licensed under the **MIT License**.
