# Product Requirements Document (PRD)

**Project Name:** NexusPHP

**Document Version:** 1.0.0

**Target Release:** Production v1.0

**Authors/Designers:** Core Architecture Team

---

## 1. Executive Summary & Vision

NexusPHP is a standalone, dependency-free PHP 8.2+ framework designed for high-concurrency, horizontally scalable, and resource-constrained environments (such as shared hosting or minimal micro-containers).

By eliminating third-party Composer runtime dependencies and capping total system files and folders below **2,000 inodes** (with a core footprint strictly under **200 inodes**), NexusPHP delivers an enterprise-grade developer experience. It synthesizes three design paradigms:

* **ASP.NET Core:** Strongly-typed service container (DI), end-to-end middleware pipeline, structured configuration, and compiled ORM patterns.
* **Express.js:** Lightweight functional middleware chaining, predictable request lifecycle, and low abstraction overhead.
* **Next.js:** Intuitive routing ergonomics, layout hierarchies, API endpoints, and fine-grained response caching.

---

## 2. Inode & Footprint Budget

Every filesystem node (file or directory) consumes 1 inode. NexusPHP enforces a deterministic budget to guarantee lean resource consumption.

| Tier | Component Scope | Target Inodes | Hard Limit |
| --- | --- | --- | --- |
| **Core Framework** | `framework/` (Foundation, Http, DB, Security, etc.) | 120 | 140 |
| **Bootstrap & Config** | `bootstrap/`, `config/`, `.env.example` | 18 | 25 |
| **App Skeleton** | `app/Http/`, `app/Models/`, `app/Providers/` | 25 | 35 |
| **Database & Views** | `database/migrations/`, `resources/views/` | 13 | 20 |
| **Storage & Public** | `storage/` dirs, `public/index.php`, CLI binary | 12 | 15 |
| **Total Base Footprint** | **Core Framework + Clean App Skeleton** | **~188** | **< 235** |
| **Application Headroom** | User-defined models, views, controllers, services | **~1,812** | **Remainder to 2,000** |

---

## 3. System Architecture & Technical Specifications

### Runtime Requirements

* **PHP Engine:** `PHP >= 8.2` (Strict types enabled across 100% of code via `declare(strict_types=1);`).
* **PHP Core Extensions:** `pdo`, `pdo_mysql`/`pdo_pgsql`/`pdo_sqlite`, `mbstring`, `openssl`, `sodium`, `json`, `fileinfo`, `session`, `intl`.
* **Optional / Accelerator Extensions:** `redis` (distributed state/queues), `apcu` (local memory cache), `opcache` (JIT execution), `swoole` or `workerman` (async runtime).

### High-Level Request Pipeline

```
[ Inbound HTTP / WebSocket ]
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

## 4. Functional Requirements & Subsystem Specifications

### 4.1 Dependency Injection & Service Container (`Foundation`)

* **Lifecycles:** Must support `Singleton`, `Scoped` (per request), and `Transient` (new instance every resolution).
* **Reflection & Auto-Wiring:** Recursively resolve class constructor parameter types using native PHP 8.2 reflection without external dependencies.
* **Binding Aliases:** Bind concrete classes directly to interfaces.

### 4.2 HTTP Kernel & Middleware Pipeline (`Http`)

* **Pipeline Mechanism:** Execute middleware using nested closures/onion pattern: `fn(Request $request, Closure $next): Response`.
* **Request & Response:** Immutable request abstraction aggregating `$_SERVER`, `$_GET`, `$_POST`, `$_FILES`, `php://input`, and headers. Specialized responses: `JsonResponse`, `RedirectResponse`, `StreamedResponse`.
* **Zero Magic Methods:** No hidden dynamic getters (`__get`, `__set`) in core HTTP classes.

### 4.3 Routing Engine (`Routing`)

* **Verbs:** Explicit methods for `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS`, `HEAD`, plus `resource()`.
* **Route Groups:** Support path prefixes, namespaces, and group middleware stacks.
* **URL Parameter Matching:** Fast regex-compiled parameter matching (`/users/{id:[0-9]+}`).

### 4.4 Database, ORM & Migrations (`Database`)

* **ActiveRecord Pattern:** Models inherit CRUD, state tracking, and hydration from `Database\Model`.
* **Query Builder:** Method-chained SQL generation with 100% parameter binding via PDO.
* **Relationships:** First-class support for `BelongsTo`, `HasMany`, `HasOne`, and `BelongsToMany` without proxy overhead.
* **Schema Blueprint & Migrator:** Programmatic DDL execution tracking applied versions in a `migrations` table (`up()` and `down()`).

### 4.5 Security Engine (`Security`)

* **CSRF Mitigation:** Synchronizer token validation injected into sessions and validated on state-modifying verbs (`POST`, `PUT`, `PATCH`, `DELETE`).
* **Cryptography:** Native `libsodium` integration (`sodium_crypto_secretbox`, `sodium_crypto_aead_aes256gcm`).
* **Authentication:** Multi-guard support:
* Stateful cookie-based authentication with `SameSite=Lax/Strict`, `HttpOnly`, `Secure`.
* Stateless token (JWT) signed via `sodium` / `openssl` (Ed25519/HMAC-SHA256).


* **Defensive Output:** Built-in context-aware escaping helper `e()` for HTML context.

### 4.6 Validation Subsystem (`Validation`)

* Form request rules engine supporting: `required`, `string`, `numeric`, `email`, `min:<n>`, `max:<n>`, `unique:table,column`, `confirmed`, `regex:<pattern>`.
* Error bag normalization with JSON field mapping.

### 4.7 Cache & Asynchronous Processing (`Cache`, `Queue`, `Events`)

* **Cache Adapters:** Unified `CacheInterface` supporting `File`, `Redis`, and `APCu`.
* **Queues:** Job serialization to `Database`, `Redis`, or local filesystem, driven by CLI worker processes (`php nexus queue:work`).
* **Event Dispatcher:** Synchronous and pub/sub-backed event distribution.

---

## 5. Non-Functional & Security Requirements

| Category | Requirement Specification | Metric / Verification |
| --- | --- | --- |
| **Filesystem Footprint** | Complete core framework + app boilerplate must remain under 2,000 total filesystem nodes. | Automated CI check: `find . -type f -o -type d | wc -l` < 2000. |
| **Dependency Purity** | Zero third-party packages in `composer.json` for core execution. | Runtime verified with empty `vendor/` or autoloader isolation. |
| **Throughput / Latency** | Baseline "Hello World" execution overhead must remain under 3.5ms on PHP-FPM / OPcache. | Tested via `wrk` (concurrency: 50, threads: 4). |
| **Security Standards** | OWASP Top 10 mitigation: 100% prepared SQL, auto-CSRF, hardened default headers (HSTS, CSP, X-Frame-Options: DENY). | Static analysis (PHPStan Level 8) + security audit scripts. |
| **Memory Baseline** | Peak memory allocation per simple request must not exceed 2.0 MB. | `memory_get_peak_usage(true)` assertions. |

---

## 6. Implementation Milestones

```
Phase 0: Core Foundation & Custom Autoloader ──────── (Week 1)
Phase 1: HTTP Kernel & Middleware Pipeline ────────── (Week 2)
Phase 2: Router, Parameter Compiler & Controllers ─── (Week 3)
Phase 3: View Layer & Output Buffering Engine ─────── (Week 4)
Phase 4: Connection Pool, Query Builder & ORM ─────── (Weeks 5-6)
Phase 5: Schema Builder & Migration Runner ────────── (Week 7)
Phase 6: Validation Engine & Form Requests ────────── (Week 8)
Phase 7: Security (Auth, CSRF, JWT, Sodium) ───────── (Week 9)
Phase 8: Cache Drivers (File, Redis, APCu) ────────── (Week 10)
Phase 9: Queue Subsystem & Worker CLI ─────────────── (Week 11)
Phase 10: Event Dispatcher & Realtime Interfaces ──── (Week 12)
Phase 11: Nexus CLI Console Tooling ───────────────── (Week 13)
Phase 12: Test Suite, Benchmarks & Sample Apps ────── (Week 14)

```

---

## 7. Acceptance Criteria for Production Release (v1.0)

1. **Inode Verification:** Running `find . | wc -l` on the clean template produces a count $\le 200$.
2. **Zero-Dependency Check:** The framework initializes and serves HTTP/API requests without requiring a `composer install` step.
3. **End-to-End Authentication:** Ready-to-use registration, session login, JWT issuance, and RBAC operational out-of-the-box.
4. **Database Operations:** Migrations execute, rollback cleanly, and models query relationships without N+1 query leaks.
5. **Code Quality:** Static analysis passes at **PHPStan Level 8** with 100% type coverage and zero implicit type coercions.