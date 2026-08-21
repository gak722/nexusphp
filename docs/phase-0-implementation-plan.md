# Phase 0: Core Foundation & Custom Autoloader

**Duration:** Week 1

## 1. What to Build

The foundational layer that establishes the framework's core identity, custom PSR-4 compliant autoloader, and the base Application container. This phase creates the zero-dependency foundation on which everything else builds.

### Core Deliverables:

- **`public/index.php`** — Front controller, the single entry point for all HTTP requests.
- **`bootstrap/app.php`** — Bootstrap the framework, register the autoloader, and instantiate the Application container.
- **`bootstrap/helpers.php`** — Collection of native PHP helper functions (str, arr, etc.) used throughout the framework.
- **`composer.json`** — Minimal file with only autoload configuration (no third-party dependencies).
- **`framework/Foundation/Application.php`** — The central `Application` class implementing the DI container and bootstrap logic.
- **`framework/Foundation/Container.php`** — PSR-11 compatible service container with constructor reflection-based auto-wiring.
- **`framework/Foundation/Config.php`** — Simple configuration loader that reads `.env` and `config/*.php` files.
- **`framework/Support/Str.php`** — String helper class (studly, camel, snake, ucfirst, etc.).
- **`framework/Support/Arr.php`** — Array helper class (get, flatten, etc.).
- **`framework/Support/Collection.php`** — Lazy iterable class for fluent data operations.
- **`framework/Support/Env.php`** — Environment variable loading with default fallbacks.

### Autoloader Specification:

- **Standard:** Custom PSR-4 style autoloader (not Composer-based).
- **Mapping:** `App\\\\ClassName` → `app/ClassName.php`, `Nexus\\\\Http\\\\Kernel` → `framework/Http/Kernel.php`.
- **Fallback:** Include `framework/Support/*.php` files explicitly in `bootstrap/app.php`.
- **Performance:** Use `spl_autoload_register()` with a single function for maximum efficiency.

### How It Fits With Previous Phases:

- **No dependencies:** This is the absolute base. All subsequent phases import from these files.
- **Dependency Injection:** Phase 1's Kernel and Phase 2's Router will resolve via the Container built here.
- **Configuration:** Phase 1's middleware pipeline and Phase 7's security features will use the Config loader.
- **Autoloading:** All future classes (Models, Controllers, Middleware) will be auto-loaded via this system.

## 2. How to Build

### Step-by-Step Implementation:

1. **Create directory structure:**
   ```
   /public/
   /bootstrap/
   /framework/Foundation/
   /framework/Support/
   /config/
   ```

2. **Create `public/index.php`:**
   ```php
   <?php
   declare(strict_types=1);
   
   require __DIR__.'/../bootstrap/app.php';
   
   $app = new Nexus\Foundation\Application();
   $kernel = $app->make(Nexus\Http\Kernel::class);
   
   $request = Nexus\Http\Request::createFromGlobals();
   $response = $kernel->handle($request);
   $response->send();
   ```

3. **Create `bootstrap/app.php`:**
   ```php
   <?php
   declare(strict_types=1);
   
   // Autoloader registration
   spl_autoload_register(function ($class) {
       $prefix = 'Nexus\\';
       $base_dir = __DIR__.'/../framework';
       
       $len = strlen($prefix);
       if (strncmp($prefix, $class, $len) !== 0) {
           return;
       }
       $relative_class = substr($class, $len);
       $file = $base_dir.str_replace('\\', '/', $relative_class).'.php';
       
       if (file_exists($file)) {
           require $file;
       }
   });
   
   // Support files autoloading
   foreach (glob(__DIR__.'/../framework/Support/*.php') as $file) {
       require $file;
   }
   
   // Config loading
   $configDir = __DIR__.'/../config';
   if (is_dir($configDir)) {
       foreach (glob($configDir.'/*.php') as $file) {
           require $file;
       }
   }
   
   // .env loading
   $envFile = __DIR__.'/../.env';
   if (file_exists($envFile)) {
       $defaults = require $envFile;
       foreach ($defaults as $key => $value) {
           putenv($key.'='.$value);
           if (!array_key_exists($key, $_ENV)) {
               $_ENV[$key] = $value;
               $_SERVER[$key] = $value;
           }
       }
   }
   
   // Application instantiation
   return new Nexus\Foundation\Application();
   ```

4. **Create `framework/Foundation/Application.php`:**
   ```php
   <?php
   declare(strict_types=1);
   
   namespace Nexus\Foundation;
   
   use Nexus\Foundation\Container;
   
   class Application
   {
       public function __construct()
       {
           $this->container = new Container();
           $this->registerCoreBindings();
       }
   
       public function make(string $abstract): mixed
       {
           return $this->container->make($abstract);
       }
   
       public function bind(string $abstract, mixed $concrete = null): void
       {
           $this->container->bind($abstract, $concrete);
       }
   
       public function registerCoreBindings(): void
       {
           // Core bindings will be overridden by service providers in later phases
       }
   
       public function configure(string $key, mixed $value): void
       {
           // Configuration setter
       }
   
       public function environment(): string
       {
           return $_ENV['APP_ENV'] ?? 'production';
       }
   }
   ```

5. **Create `framework/Foundation/Container.php`:**
   ```php
   <?php
   declare(strict_types=1);
   
   namespace Nexus\Foundation;
   
   class Container implements \Psr\Container\ContainerInterface
   {
       protected array $bindings = [];
       protected array $instances = [];
   
       public function bind(string $abstract, ?callable $concrete = null, bool $shared = false): void
       {
           $this->bindings[$abstract] = [
               'concrete' => $concrete,
               'shared' => $shared,
           ];
       }
   
       public function make(string $abstract): mixed
       {
           if (isset($this->instances[$abstract])) {
               return $this->instances[$abstract];
           }
   
           if (!isset($this->bindings[$abstract])) {
               throw new \InvalidArgumentException("Binding for [$abstract] not found.");
           }
   
           $binding = $this->bindings[$abstract];
           $concrete = $binding['concrete'];
   
           if (is_callable($concrete)) {
               $instance = $concrete($this);
           } elseif (is_string($concrete)) {
             // Auto-wiring via reflection
               $instance = $this->build($concrete);
           } else {
               $instance = new $concrete();
           }
   
           if ($binding['shared']) {
               $this->instances[$abstract] = $instance;
           }
   
           return $instance;
       }
   
       protected function build(string $concrete): mixed
       {
           $reflection = new \ReflectionClass($concrete);
   
           if (!$reflection->isInstantiable()) {
               throw new \RuntimeException("Class [$concrete] is not instantiable.");
           }
   
           $constructor = $reflection->getConstructor();
           if ($constructor === false) {
               return new $concrete();
           }
   
           $parameters = $constructor->getParameters();
           $dependencies = [];
   
           foreach ($parameters as $parameter) {
               $paramName = $parameter->getName();
               $typeHint = $parameter->getType();
   
               if ($typeHint === null) {
                   $dependencies[$paramName] = null;
                   continue;
               }
   
               $className = $typeHint->getName();
   
               // Check if it's a built-in type
               if ($typeHint->isBuiltin()) {
                   $dependencies[$paramName] = null;
                   continue;
               }
   
               // Resolve dependency by type-hint
               $dependencies[$paramName] = $this->make($className);
           }
   
           return $reflection->newInstanceArgs($dependencies);
       }
   
       public function instance(string $abstract, mixed $instance): void
       {
           $this->instances[$abstract] = $instance;
       }
   
       public function has(string $abstract): bool
       {
           return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
       }
   }
   ```

6. **Create `framework/Support/Str.php`:**
   ```php
   <?php
   declare(strict_types=1);
   
   namespace Nexus\Support;
   
   class Str
   {
       public static function studly(string $value): string
       {
           return ucfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $value))));
       }
   
       public static function camel(string $value): string
       {
           return lcfirst(self::studly($value));
       }
   
       public static function snake(string $value): string
       {
           $value = preg_replace('/\s+/', '', ucfirst($value));
           $value = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $value);
           $value = strtolower($value);
           return str_replace(' ', '_', $value);
       }
   
       public static function ucfirst(string $value): string
       {
           return mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1);
       }
   
       public static function classBasename(string $class): string
       {
           return basename((new \ReflectionClass($class))->getFileName());
       }
   }
   ```

7. **Create `framework/Support/Arr.php`:**
   ```php
   <?php
   declare(strict_types=1);
   
   namespace Nexus\Support;
   
   class Arr
   {
       public static function get(mixed $array, mixed $key, mixed $default = null): mixed
       {
           if (is_array($array) || $array instanceof \Traversable) {
               return data_get($array, $key, $default);
           }
   
           return value($default);
       }
   
       public static function flatten(mixed $array): array
       {
           $result = [];
           foreach ($array as $key => $value) {
               if (is_array($value) || $value instanceof \Traversable) {
                   $result = array_merge($result, self::flatten($value));
               } else {
                   $result[$key] = $value;
               }
           }
           return $result;
       }
   
       public static function wrap(mixed $value, callable $callback): mixed
       {
           return $callback($value);
       }
   
       public static function wrapRecursive(mixed $value, callable $callback): mixed
       {
           if (is_array($value) || $value instanceof \Traversable) {
               $result = [];
               foreach ($value as $key => $val) {
                   $result[$key] = self::wrapRecursive($val, $callback);
               }
               return $result;
           }
           return $callback($value);
       }
   }
   ```

8. **Create `framework/Support/Collection.php`:**
   ```php
   <?php
   declare(strict_types=1);
   
   namespace Nexus\Support;
   
   class Collection
   {
       protected array $items;
   
       public function __construct(array $items = [])
       {
           $this->items = $items;
       }
   
       public static function make(array $items = []): static
       {
           return new static($items);
       }
   
       public function all(): array
       {
           return $this->items;
       }
   
       public function get(int $key): mixed
       {
           return $this->items[$key] ?? null;
       }
   
       public function first(mixed $default = null): mixed
       {
           return $this->items[0] ?? $default;
       }
   
       public function filter(callable $callback = null): static
       {
           $callback = $callback ?? function ($value) { return $value; };
           return new static(array_filter($this->items, $callback));
       }
   
       public function map(callable $callback): static
       {
           return new static(array_map($callback, $this->items));
       }
   
       public function sum(): int
       {
           return array_sum(array_column($this->items, array_keys($this->items)[0] ?? 0));
       }
   }
   ```

9. **Create `framework/Support/Env.php`:**
   ```php
   <?php
   declare(strict_types=1);
   
   namespace Nexus\Support;
   
   class Env
   {
       protected array $values = [];
   
       public function __construct(array $values = [])
       {
           $this->values = $values;
       }
   
       public static function make(string $key, mixed $default = null): mixed
       {
           $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
   
           return $value;
       }
   
       public function get(string $key, mixed $default = null): mixed
       {
           return $this->values[$key] ?? $default;
       }
   
       public function all(): array
       {
           return $this->values;
       }
   
       public function load(string $file): static
       {
           if (!file_exists($file)) {
               return new static();
           }
   
           $values = [];
           $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
   
           foreach ($lines as $line) {
               if (strpos(trim($line), '=') === false) {
                   continue;
               }
   
               list ($key, $value) = explode('=', $line, 2);
               $key = trim($key);
               $value = trim($value);
   
               // Remove quotes if present
               if (strlen($value) >= 2 && $value[0] === '"' && $value[strlen($value)-1] === '"') {
                   $value = substr($value, 1, -1);
               }
   
               $values[$key] = $value;
           }
   
           return new static($values);
       }
   }
   ```

## 3. Success Criteria

- [ ] `find . | wc -l` returns a count ≤ 200 (inode budget compliance)
- [ ] Framework initializes without `composer install` (zero-dependency check)
- [ ] `public/index.php` successfully bootstraps the Application container
- [ ] Custom autoloader correctly loads `Nexus\Foundation\Application` and `Nexus\Http\Kernel`
- [ ] `bootstrap/helpers.php` functions are available globally
- [ ] Configuration loading from `.env` and `config/*.php` works correctly
- [ ] Container auto-wiring resolves class constructor dependencies via reflection