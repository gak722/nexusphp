# 03. Service Container & Architecture

At the heart of NexusPHP is the **IoC (Inversion of Control) Service Container**, implemented in `Nexus\Foundation\Container`. It provides high-performance dependency injection, automatic class reflection, singleton bindings, and instance management.

---

## 1. Understanding the Service Container

The Container acts as a central registry and factory for all services within your application.

```php
use Nexus\Foundation\Container;

$container = Container::getInstance();

// 1. Simple Binding (Closure Factory)
$container->bind(MailerInterface::class, function () {
    return new SmtpMailer(config('mail.host'));
});

// 2. Singleton Binding (Shared Instance across application lifetime)
$container->singleton(DatabaseConnection::class, function () {
    return new DatabaseConnection(env('DB_DATABASE'));
});

// 3. Automatic Resolution with Constructor Injection
$mailer = $container->make(MailerInterface::class);
```

---

## 2. Automatic Dependency Resolution (Zero-Config DI)

If a class does not require custom construction parameters, the NexusPHP container uses Reflection to automatically resolve type-hinted constructor parameters recursively:

```php
namespace App\Services;

use Nexus\Database\QueryBuilder;
use Nexus\Cache\CacheInterface;

class ReportGenerator
{
    // QueryBuilder and CacheInterface are automatically injected by the container!
    public function __construct(
        protected QueryBuilder $query,
        protected CacheInterface $cache
    ) {}

    public function generate(): array
    {
        return $this->cache->remember('daily_report', 3600, function () {
            return $this->query->table('sales')->selectRaw('SUM(amount) as total')->get();
        });
    }
}
```

---

## 3. Global Helper Functions

NexusPHP provides convenient global helpers to interact with the container:

```php
// Retrieve the application container instance
$app = app();

// Resolve a service class from the container
$router = app(\Nexus\Routing\Router::class);

// Access configuration values
$appName = config('app.name', 'NexusPHP');
```

> [!IMPORTANT]
> The container enforces strict singletons for core framework infrastructure (`Router`, `Config`, `ViewFactory`, `DatabaseConnection`) during the bootstrap sequence in `bootstrap/app.php`.

---

## 4. Next Steps

Explore how incoming requests are routed and handled in [04. Routing & Controllers](04-routing-controllers.md).
