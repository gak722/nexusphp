# 03. Dependency Injection & Service Container

At the heart of NexusPHP is the **IoC (Inversion of Control) Service Container**, implemented in `Nexus\Foundation\Container`. It provides high-performance dependency injection, automatic class reflection, singleton bindings, closure factories, and controller method injection.

---

## 1. Core Container Features

| Feature | Description | Allowed & Supported? |
| :--- | :--- | :---: |
| **Constructor Injection** | Reflection-based recursive dependency resolution for concrete classes | ✅ Supported |
| **Method Injection** | Type-hinted parameter resolution inside Controller action methods | ✅ Supported |
| **Interface Bindings** | Map contract interfaces to concrete implementations (`bind()`) | ✅ Supported |
| **Singleton Lifecycle** | Register shared singletons preserved across request lifetime (`singleton()`) | ✅ Supported |
| **Closure Factories** | Lazy evaluation callbacks for complex setup logic | ✅ Supported |
| **Global Helper** | Direct access via `app(Service::class)` or `app()` | ✅ Supported |

---

## 2. Allowed Approaches to Dependency Injection

### Approach A: Constructor Auto-Wiring (Recommended)
NexusPHP automatically inspects constructor signatures using PHP `ReflectionClass`. When classes are type-hinted, the container recursively instantiates and injects required dependencies without requiring explicit configuration.

```php
namespace App\Services;

use App\Repositories\UserRepository;
use Nexus\Cache\CacheInterface;

class UserService
{
    // UserRepository and CacheInterface are automatically injected!
    public function __construct(
        protected UserRepository $userRepository,
        protected CacheInterface $cache
    ) {}

    public function getUser(int $id): array
    {
        return $this->cache->remember("user:{$id}", 3600, fn() => $this->userRepository->find($id));
    }
}
```

---

### Approach B: Binding Interfaces to Concrete Classes
When programming to contracts/interfaces, register the abstract interface and target concrete class inside the application container:

```php
use App\Contracts\PaymentGatewayInterface;
use App\Services\StripePaymentGateway;
use Nexus\Foundation\Application;

$app = Application::getInstance();

// 1. Standard Binding (Generates a new instance on every resolution)
$app->bind(PaymentGatewayInterface::class, StripePaymentGateway::class);

// 2. Resolve anywhere in service constructors
class CheckoutService
{
    public function __construct(
        protected PaymentGatewayInterface $paymentGateway
    ) {}
}
```

---

### Approach C: Singleton Registration
Singletons ensure only one instance of a class exists across the application runtime, preserving state and saving memory:

```php
use App\Services\MetricsCollector;

// Bind a Closure Singleton
$app->singleton(MetricsCollector::class, function ($app) {
    return new MetricsCollector(
        environment: env('APP_ENV', 'production')
    );
});

// Or bind an already instantiated instance
$app->instance('metrics.store', new MetricsCollector('production'));
```

---

### Approach D: Controller Method Injection
NexusPHP automatically resolves type-hinted service objects directly in Controller methods alongside route parameters:

```php
namespace App\Http\Controllers;

use App\Services\UserService;
use Nexus\Http\Controller;
use Nexus\Http\Request;

class UserController extends Controller
{
    public function show(Request $request, UserService $userService, int $id): string
    {
        $user = $userService->getUser($id);

        return view('user.profile', [
            'user' => $user
        ]);
    }
}
```

---

### Approach E: Global `app()` Helper Resolution
When constructor or method injection is not feasible, use the global `app()` helper function:

```php
// Resolve a registered service or auto-wire a class
$userService = app(\App\Services\UserService::class);

// Retrieve the core application container instance
$container = app();
```

---

## 3. Best Practices & Primitive Parameters

> [!TIP]
> **Handling Primitive Parameters (Strings, Ints, Arrays)**:
> Reflection-based auto-wiring resolves class type-hints automatically. If a constructor requires primitive parameters without default values (e.g. `$apiKey`), bind a closure factory:

```php
$app->bind(ThirdPartyApiClient::class, function () {
    return new ThirdPartyApiClient(
        apiKey: env('API_KEY', 'secret-key'),
        timeout: 30
    );
});
```

---

## 4. Next Steps

Explore how incoming requests are routed and handled in [04. Routing & Controllers](04-routing-controllers.md).
