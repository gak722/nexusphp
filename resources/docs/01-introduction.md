# 01. Introduction to NexusPHP

Welcome to **NexusPHP**, a modern, high-performance PHP framework built from the ground up for extreme concurrency, strict zero-dependency operation, and complete developer happiness.

> [!NOTE]
> NexusPHP operates with **zero external Composer dependencies**. Every component—from the Dependency Injection Container to the Active Record ORM and Queue Worker—is built as part of the core engine.

---

## 1. The Core Philosophy

Modern PHP web application development has become heavily reliant on monolithic vendor trees, leading to dependency bloat, security risks, slow cold starts, and complex upgrade paths. NexusPHP breaks this pattern by proving that a clean, zero-dependency PHP framework can deliver enterprise-grade capabilities with sub-millisecond response times.

### Key Framework Pillars:
- **Zero Third-Party Dependencies:** No vendor bloat. Zero vulnerability inheritance from external repositories.
- **Strict Inode Compliance:** Micro-footprint footprint designed to fit strictly within strict system inode limits (<2,000 files total).
- **PHP 8.4 Native:** Built specifically for modern PHP, taking full advantage of Typed Properties, Enums, Attributes, First-Class Callables, and Constructor Property Promotion.
- **Extreme Performance:** Built using optimized memory structures, pre-compiled route regexes, and isolated output buffering.

---

## 2. Real-World Analogy: The "Smart Barista"

To understand how NexusPHP handles incoming web traffic compared to traditional frameworks, consider the **Smart Barista** analogy:

```
[ Incoming HTTP Request ]
          │
          ▼
┌─────────────────────────────────────────────────────────┐
│              The Smart Barista (NexusPHP Core)          │
│                                                         │
│  1. Scans Request (Zero overhead routing)                │
│  2. Resolves Dependencies instantly from Memory Counter  │
│  3. Prepares exact Order (Controller Execution)         │
│  4. Hands back warm Response without unnecessary setup  │
└─────────────────────────────────────────────────────────┘
          │
          ▼
[ HTTP 200 Response rendered in < 1.2ms ]
```

- **Traditional Monoliths:** A barista who reads a 500-page manual before taking your order, fetching custom tools from 50 vendor boxes, and taking 85ms just to hand you an empty cup.
- **NexusPHP (The Smart Barista):** Keeps the counter clean and prepped. As soon as a customer (HTTP Request) arrives, the barista instantly resolves what is needed, executes the exact recipe, and serves the response in less than 2 milliseconds.

---

## 3. High-Level Architecture Overview

NexusPHP follows a strict unified request lifecycle:

| Layer | Component | Function |
| :--- | :--- | :--- |
| **Entry Point** | `public/index.php` | Captures the global server environment and initializes runtime. |
| **Bootstrapper** | `bootstrap/app.php` | Instantiates `Nexus\Foundation\Application` container. |
| **Kernel** | `Nexus\Http\Kernel` | Passes request through global middleware stack. |
| **Router** | `Nexus\Routing\Router` | Matches URI pattern against compiled route definitions. |
| **Controller** | `App\Http\Controllers` | Executes business logic, calls Models, and returns Response. |
| **Emitter** | `Response::send()` | Flushes output buffers and sends HTTP headers to client. |

---

## 4. Next Steps

Ready to get started? Proceed to [02. Installation & Quickstart](02-installation.md) to set up your first application.
