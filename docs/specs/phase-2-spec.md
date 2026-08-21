# Copilot Spec: Phase 2 — Router, Parameter Compiler & Controllers

## Objective
Implement dynamic routing, regex parameter compilation (`/users/{id:\d+}`), group prefixing, route middleware stacks, controller dispatching, and auto-wiring of controller action parameters.

## Target Files to Create / Modify
- `framework/Routing/Route.php`
- `framework/Routing/RouteCollection.php`
- `framework/Routing/RouteCompiler.php`
- `framework/Routing/Router.php`
- `framework/Routing/UrlGenerator.php`
- `framework/Http/ControllerDispatcher.php`
- `framework/Http/Controller.php`

---

## Detailed Specifications

### 1. `framework/Routing/RouteCompiler.php`
- **Method:** `public static function compile(string $uri): array`
- **Logic:** Convert parameterized segments `{param}` or `{param:regex}` into compiled regex pattern `#^...$#s`. Extract parameter names in positional order.

### 2. `framework/Routing/Route.php`
- **Constructor:** `string $method`, `string $uri`, `mixed $action`.
- **Method:** `matches(Request $request, array &$parameters): bool` — Compiles URI if needed and matches against `$request->uri`. Populates `$parameters` array.

### 3. `framework/Routing/Router.php`
- Methods: `get()`, `post()`, `put()`, `patch()`, `delete()`, `resource()`.
- Grouping: `group(array $attributes, \Closure $callback)` supporting `prefix` and `middleware`.
- Dispatcher: `dispatch(Request $request): Response` iterates routes; returns 404 `Response` if no match found.

### 4. `framework/Http/ControllerDispatcher.php`
- Uses `\ReflectionMethod` to analyze target controller action parameters.
- Matches route named parameters to method arguments.
- Auto-wires complex type-hinted dependencies using `Application::make()`.
- Automatically wraps return arrays/JsonSerializable objects into `JsonResponse`.

---

## Copilot Validation Rules
- [ ] Direct closure routes and `[Controller::class, 'method']` target arrays MUST both be supported.
- [ ] Route group prefixes MUST concatenate properly (e.g. `/api` + `/v1` + `/users` -> `/api/v1/users`).
