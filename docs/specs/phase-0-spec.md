# Copilot Spec: Phase 0 — Core Foundation & Custom Autoloader

## Objective
Implement the zero-dependency base framework foundation: the front controller (`public/index.php`), bootstrap file (`bootstrap/app.php`), PSR-4 compatible autoloader, environment parser, reflection-based service container, and core support helpers.

## Target Files to Create / Modify
- `public/index.php`
- `bootstrap/app.php`
- `bootstrap/helpers.php`
- `framework/Foundation/Application.php`
- `framework/Foundation/Container.php`
- `framework/Foundation/Config.php`
- `framework/Support/Str.php`
- `framework/Support/Arr.php`
- `framework/Support/Collection.php`
- `framework/Support/Env.php`

---

## Detailed Specifications

### 1. `bootstrap/app.php`
- **Autoloader Function:** Register a single `spl_autoload_register` callback mapping `Nexus\` namespace to `framework/`.
- **Support Loading:** Require all `framework/Support/*.php` and `bootstrap/helpers.php` files directly.
- **Config & Env Loading:** Parse `.env` using `Nexus\Support\Env` and set values into `$_ENV` and `$_SERVER`.
- **Return:** Must return an instance of `Nexus\Foundation\Application`.

### 2. `framework/Foundation/Container.php`
- Must implement `Psr\Container\ContainerInterface` without vendor dependencies.
- **Methods:**
  - `bind(string $abstract, ?callable $concrete = null, bool $shared = false): void`
  - `singleton(string $abstract, ?callable $concrete = null): void`
  - `make(string $abstract): mixed`
  - `instance(string $abstract, mixed $instance): void`
  - `has(string $abstract): bool`
- **Auto-wiring Rules:**
  - If class constructor parameters have type hints, recursively resolve them via `make()`.
  - If parameter is scalar with default value, use default value.
  - If parameter is scalar without default value, throw `\RuntimeException`.

### 3. `framework/Foundation/Application.php`
- Singleton pattern holding root static instance via `getInstance(): static`.
- Extends or encapsulates `Container`.
- Must provide paths: `basePath()`, `storagePath()`, `configPath()`, `publicPath()`.

### 4. `framework/Support/Str.php` & `framework/Support/Arr.php`
- `Str::studly(string $value): string`
- `Str::camel(string $value): string`
- `Str::snake(string $value): string`
- `Arr::get(mixed $array, mixed $key, mixed $default = null): mixed`
- `Arr::flatten(array $array): array`

---

## Copilot Validation Rules
- [ ] No `use Vendor\...` or Composer autoloader references allowed.
- [ ] `declare(strict_types=1);` mandatory at line 1 of every `.php` file.
- [ ] Inode check: Maximum +11 files added.
