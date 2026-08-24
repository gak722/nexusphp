# Architecture Concepts

## Overview

At the heart of NexusPHP lies an architecture built for blistering speed, transparency, and developer happiness. While heavily inspired by enterprise-grade frameworks, NexusPHP strips away the massive dependency trees and complex abstractions. Instead, it relies on modern PHP 8.2+ paradigms to deliver a clean, predictable, and highly performant foundation.

The framework is built around three core architectural pillars:
1. **Dependency Injection**: A lightweight, PSR-11 compatible Service Container that manages class lifetimes and dependencies.
2. **Onion Middleware Pipeline**: A clean, functional request-response cycle that intercepts HTTP requests without relying on magic methods.
3. **Convention over Configuration**: A deterministic application structure that auto-wires dependencies and routes effectively out of the box.

Understanding these concepts will help you leverage NexusPHP’s full potential, write cleaner code, and debug issues with ease.

## The Request Lifecycle (Preview)

When you build an application with NexusPHP, every HTTP request follows a strict, predictable path. Understanding this path is crucial for knowing exactly where to place your business logic.

Here is a high-level summary of a request's journey:
1. **Entry Point**: The request hits `public/index.php`, the front controller of your application.
2. **Bootstrapping**: The `bootstrap/app.php` script initializes the application. It registers the auto-loader, parses your `.env` file, loads all configuration files from the `config/` directory, and sets up the Service Container.
3. **Kernel & Routing**: The request is handed to the HTTP Kernel (`Nexus\Http\Kernel`). The router evaluates the request URI and method, matching it to a defined route in your `routes/web.php` file.
4. **Middleware Pipeline**: Before hitting your controller logic, the request passes through a stack of middleware (such as CSRF protection or CORS handling).
5. **Controller Resolution**: The framework's `ControllerDispatcher` resolves your controller from the Service Container, automatically injecting any type-hinted dependencies, and executes the target method.
6. **Response Generation**: Your controller returns a response (e.g., a `JsonResponse` or HTML view), which is then sent back to the client's browser.

To dive deeper into this flow, read the full [Request Lifecycle](lifecycle.md) guide.

## The Service Container (Preview)

The NexusPHP Service Container (`Nexus\Foundation\Container`) is the engine that powers the entire framework. It is a powerful, zero-dependency inversion of control (IoC) container used for managing class dependencies and performing dependency injection.

Key capabilities of the NexusPHP container include:
- **Auto-wiring**: The container uses PHP Reflection to inspect your class constructors. If it sees a type-hinted dependency, it will automatically instantiate and inject it for you—no configuration required.
- **Lifetime Management**: Borrowing proven concepts from modern enterprise frameworks (like .NET Core), the container explicitly supports registering services with specific lifetimes:
  - **Transient** (`addTransient`): A new instance is created every time it is requested.
  - **Scoped** (`addScoped`): A single instance is shared throughout the duration of the current HTTP request.
  - **Singleton** (`addSingleton`): A single, globally shared instance is created for the entire application lifecycle.
- **Circular Dependency Detection**: The container actively tracks the build stack, throwing clear exceptions if a circular dependency loop is detected, preventing infinite recursion crashes.

To learn how to bind interfaces to implementations and register your own services, explore the comprehensive [Service Container](container.md) guide.

## Why Architecture Matters

Taking the time to understand NexusPHP's architecture is an investment in your application's future. Grasping the request lifecycle and the service container will allow you to:
- **Write Testable Code**: By relying on dependency injection rather than tightly coupled classes or global state, your code becomes infinitely easier to mock and test.
- **Debug Effectively**: Knowing exactly where a request enters the application and how it navigates through middleware and controllers takes the guesswork out of troubleshooting.
- **Scale Confidently**: Leveraging singletons and scoped services appropriately ensures that your application utilizes memory efficiently, keeping that `< 2.0MB` memory footprint intact.

---

**Next Steps:** Dive into the details of these core concepts:
- [Request Lifecycle](lifecycle.md)
- [Service Container](container.md)
