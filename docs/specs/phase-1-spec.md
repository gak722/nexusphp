# Copilot Spec: Phase 1 — HTTP Kernel & Middleware Pipeline

## Objective
Build the immutable HTTP abstraction layer (`Request`, `Response`, `JsonResponse`, `RedirectResponse`, `StreamedResponse`), global middleware execution stack, and central `Http\Kernel`.

## Target Files to Create / Modify
- `framework/Http/Request.php`
- `framework/Http/Response.php`
- `framework/Http/JsonResponse.php`
- `framework/Http/RedirectResponse.php`
- `framework/Http/StreamedResponse.php`
- `framework/Http/MiddlewareInterface.php`
- `framework/Http/MiddlewareStack.php`
- `framework/Http/Kernel.php`
- `framework/Http/Middleware/SecurityHeadersMiddleware.php`
- `framework/Http/Middleware/CorsMiddleware.php`
- `framework/Http/Middleware/ExceptionHandlerMiddleware.php`

---

## Detailed Specifications

### 1. `framework/Http/Request.php`
- **Properties (all `public readonly`):**
  - `string $method`, `string $uri`, `array $headers`, `array $query`, `array $post`, `array $files`, `array $cookies`, `string $rawBody`
- **Static Factory:** `createFromGlobals(): static` constructing instance from PHP superglobals (`$_SERVER`, `$_GET`, `$_POST`, `$_FILES`, `$_COOKIE`, `php://input`).
- **Methods:**
  - `header(string $key, ?string $default = null): ?string` (case-insensitive lookup)
  - `isJson(): bool`
  - `json(?string $key = null, mixed $default = null): mixed`

### 2. `framework/Http/Response.php` & Subclasses
- `Response`: `__construct(string $content = '', int $statusCode = 200, array $headers = [])`. `send(): void` flushes `http_response_code` and headers via `header()`.
- `JsonResponse`: Inherits `Response`, automatically JSON encodes data and sets header `Content-Type: application/json`.
- `RedirectResponse`: Inherits `Response`, sets status 302 and `Location: $url`.

### 3. `framework/Http/MiddlewareStack.php`
- Must implement Express-like onion architecture.
- `add(MiddlewareInterface|callable $middleware): void`
- `handle(Request $request, \Closure $coreHandler): Response` — array_reduce reverse pipeline execution.

### 4. `framework/Http/Kernel.php`
- Integrates `MiddlewareStack`.
- Default global middleware order:
  1. `ExceptionHandlerMiddleware`
  2. `SecurityHeadersMiddleware`
  3. `CorsMiddleware`

---

## Copilot Validation Rules
- [ ] Do not mutate state on `Request` object after construction (immutable pattern).
- [ ] Security headers MUST include: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `X-XSS-Protection: 1; mode=block`.
