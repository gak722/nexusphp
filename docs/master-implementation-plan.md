# NexusPHP: Master PRD & Phase-by-Phase Implementation Plan

---

## 1. Executive Summary & Vision

**NexusPHP** is a high-concurrency, standalone, dependency-free PHP 8.2+ web framework. Designed specifically for resource-constrained environments (such as shared hosting or minimal micro-containers), NexusPHP enforces a strict **2,000 total inode budget** with a core footprint strictly under **200 inodes** while eliminating external Composer runtime dependencies.

NexusPHP synthesizes three major web framework paradigms:
1. **ASP.NET Core:** Strongly-typed service container (DI), middleware pipeline, structured configuration, and compiled ORM patterns.
2. **Express.js:** Lightweight functional middleware chaining, predictable request lifecycle, and low abstraction overhead.
3. **Next.js:** Intuitive routing ergonomics, layout hierarchies, API endpoints, and fine-grained response caching.

---

## 2. Master System Pipeline Architecture

```
[ Inbound HTTP / WebSocket Request ]
           │
           ▼
   [ public/index.php ] ────────► [ PSR-4 Zero-Dependency Autoloader ]
           │
           ▼
[ Foundation\Application ] ────► [ Foundation\Container (DI Resolver) ]
           │
           ▼
   [ Http\Kernel ] ─────────────► [ Pipeline Stack: Middleware[] ]
                                          ├── 1. ExceptionHandler / ErrorCapture
                                          ├── 2. SecurityHeaders (CSP, HSTS, Sniff)
                                          ├── 3. CORS Policy Handler
                                          ├── 4. RateLimiter (Token Bucket)
                                          ├── 5. SessionHandler (File / Redis)
                                          ├── 6. CsrfTokenVerifier
                                          └── 7. Router Dispatcher
                                                     │
                                                     ▼
                                        [ Controller Action / Closure ]
                                                     │
                                                     ├── FormRequest Auto-Validation
                                                     ├── ORM / Active Record Query
                                                     └── Response Generation
                                                     │
                                                     ▼
                                        [ Http\Response Execution ]
                                                     │
                                                     ▼
                                        [ Client Output Buffer Flush ]
```

---

## 3. Comprehensive Phase-by-Phase Implementation Matrix

Below is the complete roadmap detailing what to build, how to build, and how each phase builds upon previous phase implementations.

| Phase | Title | Core Deliverables | Integration with Prior Phases |
| :--- | :--- | :--- | :--- |
| **Phase 0** | **Core Foundation & Custom Autoloader** | `public/index.php`, `bootstrap/app.php`, `Application`, `Container`, `Config`, `Support\*` | Base foundation. Establishes PSR-4 autoloader, environment loading, and reflection DI container. |
| **Phase 1** | **HTTP Kernel & Middleware Pipeline** | `Request`, `Response`, `JsonResponse`, `Kernel`, `MiddlewareStack`, Security/CORS/Exception Middleware | Resolved via Phase 0 `Container`. Uses Phase 0 support helpers and `.env` configuration. |
| **Phase 2** | **Router, Parameter Compiler & Controllers** | `Router`, `Route`, `RouteCompiler`, `ControllerDispatcher`, `Controller` | Dispatched inside Phase 1's `MiddlewareStack`. Action dependencies resolved by Phase 0 `Container`. |
| **Phase 3** | **View Layer & Output Buffering Engine** | `View`, `ViewFactory`, `Engine`, `Component`, output escaping `e()`, `view()` helper | Controller actions in Phase 2 return `View` objects. Standardizes HTML output into Phase 1 `Response`. |
| **Phase 4** | **Connection Pool, Query Builder & ORM** | `Connection` (PDO), `QueryBuilder`, `Model` (ActiveRecord), Relationships | Reads DB config from Phase 0 `Config`. Models auto-serialize to JSON for Phase 1 `JsonResponse`. |
| **Phase 5** | **Schema Builder & Migration Runner** | `Blueprint`, `Schema`, `Migration`, `Migrator`, `Seeder` | Executes DDL using Phase 4 `Connection`. Managed state stored in Phase 4 database tables. |
| **Phase 6** | **Validation Engine & Form Requests** | `Validator`, `RuleInterface`, `ValidationException`, Rules (`Required`, `Email`, etc.), `FormRequest` | Called inside Phase 1 `Request::validate()`. `ValidationException` caught by Phase 1 exception middleware. |
| **Phase 7** | **Security (Auth, CSRF, JWT, Sodium)** | `Csrf`, `Auth`, `Jwt`, `Password`, `Encryptor`, `RateLimiter` | CSRF and RateLimiter attach to Phase 1 pipeline. `Auth` loads users via Phase 4 `Model`. |
| **Phase 8** | **Cache Drivers (File, Redis, APCu)** | `CacheInterface`, `FileCache`, `RedisCache`, `ApcuCache`, `CacheManager` | `CacheManager` registered as Phase 0 `Container` singleton. Used by Phase 4 ORM & Phase 7 rate limiting. |
| **Phase 9** | **Queue Subsystem & Worker CLI** | `Job`, `QueueInterface`, `DatabaseQueue`, `RedisQueue`, `Worker` | `DatabaseQueue` uses Phase 4 `Connection`. Worker executed via Phase 11 CLI console. |
| **Phase 10**| **Event Dispatcher & Realtime Interfaces** | `Event`, `Dispatcher`, `ListenerInterface`, `BroadcastManager`, `SseResponse` | `Dispatcher` registered in Phase 0 `Container`. Listeners can push jobs to Phase 9 `Queue`. `SseResponse` extends Phase 1 `Response`. |
| **Phase 11**| **Nexus CLI Console Tooling** | `nexus` binary, `ConsoleApplication`, `Command`, `MakeController`, `MakeModel`, `MigrateCommand` | Boots framework using Phase 0 `bootstrap/app.php`. Interoperates with Phase 5 `Migrator` and Phase 9 `Worker`. |
| **Phase 12**| **Test Suite, Benchmarks & Sample Apps**| `TestRunner`, `HttpTest`, `OrmTest`, `InodeBudgetTest`, sample CMS/API controllers | Validates end-to-end integration of all phases. Verifies memory (< 2MB), speed (< 3.5ms), and inodes (< 2000). |

---

## 4. Phase Implementation Files Reference

All individual implementation plan files have been generated under `docs/`:

1. Phase 0: [phase-0-implementation-plan.md](file:///home/gak/Documents/nexusphp/docs/phase-0-implementation-plan.md)
2. Phase 1: [phase-1-implementation-plan.md](file:///home/gak/Documents/nexusphp/docs/phase-1-implementation-plan.md)
3. Phase 2: [phase-2-implementation-plan.md](file:///home/gak/Documents/nexusphp/docs/phase-2-implementation-plan.md)
4. Phase 3: [phase-3-implementation-plan.md](file:///home/gak/Documents/nexusphp/docs/phase-3-implementation-plan.md)
5. Phase 4: [phase-4-implementation-plan.md](file:///home/gak/Documents/nexusphp/docs/phase-4-implementation-plan.md)
6. Phase 5: [phase-5-implementation-plan.md](file:///home/gak/Documents/nexusphp/docs/phase-5-implementation-plan.md)
7. Phase 6: [phase-6-implementation-plan.md](file:///home/gak/Documents/nexusphp/docs/phase-6-implementation-plan.md)
8. Phase 7: [phase-7-implementation-plan.md](file:///home/gak/Documents/nexusphp/docs/phase-7-implementation-plan.md)
9. Phase 8: [phase-8-implementation-plan.md](file:///home/gak/Documents/nexusphp/docs/phase-8-implementation-plan.md)
10. Phase 9: [phase-9-implementation-plan.md](file:///home/gak/Documents/nexusphp/docs/phase-9-implementation-plan.md)
11. Phase 10: [phase-10-implementation-plan.md](file:///home/gak/Documents/nexusphp/docs/phase-10-implementation-plan.md)
12. Phase 11: [phase-11-implementation-plan.md](file:///home/gak/Documents/nexusphp/docs/phase-11-implementation-plan.md)
13. Phase 12: [phase-12-implementation-plan.md](file:///home/gak/Documents/nexusphp/docs/phase-12-implementation-plan.md)

---

## 5. Success Criteria & Verification

Every phase includes strict acceptance tests:
- **Zero Third-Party Runtime Dependencies:** Executable without `composer install`.
- **Inode Budget Assurance:** Total filesystem nodes strictly below 2,000 (automated via `InodeBudgetTest`).
- **PHP 8.2 Strict Type Enforcement:** 100% type annotations with `declare(strict_types=1);`.
- **PHPStan Level 8:** Zero implicit coercions or untyped parameters.
