# NexusPHP Specifications Directory (`specs/`)

This directory contains strict, file-by-file technical specifications for each development phase of NexusPHP. These specifications are engineered to keep AI coding assistants and Copilots completely on track, enforcing framework constraints and preventing architectural drift.

## System Constraints & Invariant Rules

1. **Strict Typing:** All PHP files MUST start with `declare(strict_types=1);`.
2. **Zero Composer Dependencies:** The core framework MUST execute without `composer install` or third-party packages in `vendor/`.
3. **Inode Budget:** Framework core + clean skeleton MUST remain under **200 inodes** (`find . -type f -o -type d | wc -l` < 2000 total application headroom).
4. **No Magic Getters/Setters:** Avoid dynamic `__get`/`__set` magic methods in core HTTP/Foundation classes unless explicitly specified (e.g. ORM attributes).
5. **PHP 8.2 Baseline:** Utilize native `readonly` properties, union types, intersection types, and match expressions.

## Phase Specifications Index

- [Phase 0 Specification](file:///home/gak/Documents/nexusphp/specs/phase-0-spec.md) — Core Foundation, Container & Autoloader
- [Phase 1 Specification](file:///home/gak/Documents/nexusphp/specs/phase-1-spec.md) — HTTP Kernel & Middleware Pipeline
- [Phase 2 Specification](file:///home/gak/Documents/nexusphp/specs/phase-2-spec.md) — Router, Parameter Compiler & Controller Dispatcher
- [Phase 3 Specification](file:///home/gak/Documents/nexusphp/specs/phase-3-spec.md) — View Layer, Output Buffering & Layout Engine
- [Phase 4 Specification](file:///home/gak/Documents/nexusphp/specs/phase-4-spec.md) — Connection Pool, Query Builder & ActiveRecord ORM
- [Phase 5 Specification](file:///home/gak/Documents/nexusphp/specs/phase-5-spec.md) — Schema DDL Builder & Versioned Migrations
- [Phase 6 Specification](file:///home/gak/Documents/nexusphp/specs/phase-6-spec.md) — Validation Engine & Auto-Validating Form Requests
- [Phase 7 Specification](file:///home/gak/Documents/nexusphp/specs/phase-7-spec.md) — Security Subsystem (Auth, CSRF, JWT, Libsodium, Rate Limiter)
- [Phase 8 Specification](file:///home/gak/Documents/nexusphp/specs/phase-8-spec.md) — Cache Subsystem (File, Redis, APCu)
- [Phase 9 Specification](file:///home/gak/Documents/nexusphp/specs/phase-9-spec.md) — Queue Subsystem & Asynchronous Worker CLI
- [Phase 10 Specification](file:///home/gak/Documents/nexusphp/specs/phase-10-spec.md) — Event Dispatcher & Realtime SSE/PubSub
- [Phase 11 Specification](file:///home/gak/Documents/nexusphp/specs/phase-11-spec.md) — Nexus CLI Binary & Generator Tooling
- [Phase 12 Specification](file:///home/gak/Documents/nexusphp/specs/phase-12-spec.md) — Zero-Dependency Test Suite, Benchmarks & Sample Apps
