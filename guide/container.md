# Service Container

## Overview

The NexusPHP Service Container (`Nexus\Foundation\Container`) is the beating heart of the framework. It is a powerful, reflection-based Dependency Injection (DI) container responsible for managing class dependencies and performing auto-wiring.

Unlike heavier frameworks that rely on complex service provider architectures, deferred loading, or tagged services, the NexusPHP container embraces simplicity. It provides exactly what you need to manage object lifecycles—ranging from singletons to transient instances—while strictly avoiding unnecessary overhead.

**Note:** Everything in this documentation is based precisely on the actual NexusPHP source code (`framework/Foundation/Container.php`).

## Container Fundamentals

### What is the Container?
The Service Container is a central registry where you can define how objects should be instantiated. When the framework or your application needs an instance of a class, it asks the container to resolve it. If the class has dependencies in its constructor, the container will recursively resolve those dependencies first.

### How It Works
The container uses **PHP Reflection** to inspect the constructors of classes it is asked to resolve. If it detects type-hinted parameters, it automatically attempts to instantiate and inject them. This process is called **Auto-wiring**.

### Key Contracts
While the source code includes a docblock commenting that it is "PSR-11 compatible," it is important to note that the actual implementation relies on `make()` instead of the standard `get()` method, meaning it does not strictly implement the `Psr\Container\ContainerInterface`.

## Using the Container

### Service Registration

You can register services with the container using several methods. The container heavily favors `.NET ServiceCollection` style semantics for explicit lifetime management.

**Method: `bind(string $abstract, mixed $concrete = null, bool $shared = false)`**
- **Description**: The fundamental method for registering a binding. If `$concrete` is a closure, it will be executed when resolved. If it's a string, it will be auto-wired.
- **Parameters**: 
  - `$abstract` (string): The interface or class name.
  - `$concrete` (mixed): The implementation, closure, or null.
  - `$shared` (bool): Whether the resolved instance should be cached as a singleton.
- **Return Type**: `void`

**Method: `instance(string $abstract, mixed $instance)`**
- **Description**: Binds a pre-existing object instance directly into the container's singleton registry.
- **Parameters**:
  - `$abstract` (string): The interface or class name.
  - `$instance` (mixed): The existing object instance.
- **Return Type**: `void`
- **Helper Alias**: `addInstance(string $abstract, mixed $instance): static`

### Lifetime Management (Service Collection Style)

To make lifecycle intent explicit, the container provides fluent methods tailored for different instantiation rules:

**Method: `addTransient(string $abstract, mixed $concrete = null)`**
- **Description**: Registers a dependency where a **new instance** is created every single time it is resolved.
- **Return Type**: `static`

**Method: `addScoped(string $abstract, mixed $concrete = null)`**
- **Description**: Registers a dependency that acts as a singleton for the current request scope. (Under the hood, this currently maps identically to a singleton).
- **Return Type**: `static`

**Method: `addSingleton(string $abstract, mixed $concrete = null)`**
- **Description**: Registers a dependency where a single shared instance is created and returned for every subsequent resolution across the application lifecycle.
- **Return Type**: `static`
- **Legacy Alias**: `singleton(string $abstract, mixed $concrete = null): void`

### Service Resolution

**Method: `make(string $abstract)`**
- **Description**: Resolves the given type from the container. It checks for a cached singleton first, then looks for a registered binding. If no binding exists, it attempts to auto-wire the class dynamically.
- **Parameters**: 
  - `$abstract` (string): The class or interface to resolve.
- **Return Type**: `mixed`
- **Example**: `$router = $app->make(Nexus\Routing\Router::class);`

**Method: `has(string $abstract)`**
- **Description**: Determines if a given type has been explicitly bound or instantiated.
- **Parameters**: 
  - `$abstract` (string): The class or interface to check.
- **Return Type**: `bool`

## Auto-wiring & Dependency Resolution

The actual resolution engine resides in the protected `build(string $concrete)` method. Here is exactly what happens when you call `make()` on an unbound class:

1. **Circular Dependency Check**: The container pushes the class onto a `$buildStack`. If it detects the class is already in the stack, it throws a `RuntimeException("Circular dependency detected...")`.
2. **Reflection**: It creates a `ReflectionClass`. If the class is not instantiable (e.g., an interface without a binding), it throws an exception.
3. **Constructor Inspection**: It fetches the constructor parameters.
4. **Recursive Resolution**: For each parameter:
   - If it is a class/interface (`ReflectionNamedType` and not a PHP builtin), the container recursively calls `make()` for that type.
   - If the parameter has a default value defined in the constructor, it uses that value.
   - If it is unresolvable (e.g., a string or int without a default value), it throws a `RuntimeException("Unresolvable dependency...")`.

> [TODO: Check if contextual binding is supported - Not found in initial scan. The codebase relies purely on explicit bindings or auto-wiring.]

## Service Registration via Configuration

Unlike Laravel, which uses ServiceProvider classes, NexusPHP centralizes bulk service registration in a configuration file (`config/services.php`). 

During bootstrap, the `Nexus\Foundation\Application::registerConfiguredServices()` method is invoked. It reads the configuration arrays and registers them dynamically:

```php
// config/services.php structure expectation:
return [
    'singletons' => [
        MyInterface::class => MyImplementation::class,
    ],
    'transients' => [
        // ...
    ],
    'scoped' => [
        // ...
    ],
    'register' => function ($app) {
        // Custom closure execution for complex bindings
    }
];
```

## Best Practices & Common Patterns

1. **Prefer Explicit Lifetimes**: Use `addTransient` or `addSingleton` rather than the generic `bind()` to make your application's memory usage and state management intent crystal clear.
2. **Avoid Container Injection**: Do not inject the Container into your classes to resolve dependencies manually. Let the `ControllerDispatcher` or the container's auto-wiring handle injecting the dependencies via the constructor.
3. **Interface Binding**: Always bind interfaces to concrete implementations in your `config/services.php` rather than type-hinting concrete classes directly. This drastically improves testability.

## API Reference

| Method | Parameters | Return Type | Description |
| :--- | :--- | :--- | :--- |
| `bind` | `string $abstract, mixed $concrete = null, bool $shared = false` | `void` | Binds a type to an implementation. |
| `singleton` | `string $abstract, mixed $concrete = null` | `void` | Binds a shared instance into the container. |
| `addTransient` | `string $abstract, mixed $concrete = null` | `static` | Registers a non-shared dependency. |
| `addScoped` | `string $abstract, mixed $concrete = null` | `static` | Registers a scoped shared dependency. |
| `addSingleton` | `string $abstract, mixed $concrete = null` | `static` | Registers a globally shared dependency. |
| `addInstance` | `string $abstract, mixed $instance` | `static` | Registers a pre-instantiated object. |
| `instance` | `string $abstract, mixed $instance` | `void` | Legacy registration for a pre-instantiated object. |
| `has` | `string $abstract` | `bool` | Checks if a type is bound or instantiated. |
| `make` | `string $abstract` | `mixed` | Resolves a type via bindings or auto-wiring. |

## Summary

The NexusPHP Service Container provides a lightweight, exceptionally fast mechanism for handling Dependency Injection. By utilizing PHP Reflection and `.NET` style lifetime declarations, it keeps your classes decoupled and your configuration highly centralized without the overhead of massive service provider booting pipelines.

---

**Next Steps:**
- See how the container automatically injects dependencies during routing in the [Request Lifecycle](lifecycle.md) guide.
- Learn how to define routes that trigger auto-wired controllers in the [Routing](routing.md) guide.
