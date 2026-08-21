# Phase 1: HTTP Kernel & Middleware Pipeline

**Duration:** Week 2

---

## 1. What to Build

Phase 1 establishes the HTTP core of NexusPHP. It receives incoming HTTP requests from the front controller (`public/index.php`), passes them through a deterministic, layered pipeline of middleware, and constructs an immutable, structured HTTP response.

### Core Deliverables:

- **`framework/Http/Request.php`** — Immutable HTTP request abstraction wrapping `$_SERVER`, `$_GET`, `$_POST`, `$_FILES`, `$_COOKIE`, and `php://input`.
- **`framework/Http/Response.php`** — Standard HTTP response supporting status codes, headers, and string content body.
- **`framework/Http/JsonResponse.php`** — Specialized subclass of `Response` for JSON payloads with automatic `Content-Type` header setting.
- **`framework/Http/RedirectResponse.php`** — Specialized response for 301/302/303 HTTP redirects.
- **`framework/Http/StreamedResponse.php`** — Chunked/streamed response output wrapper for large payloads or SSE.
- **`framework/Http/MiddlewareInterface.php`** — Contract defining `handle(Request $request, Closure $next): Response`.
- **`framework/Http/MiddlewareStack.php`** — Nested closure execution pipeline (Express.js onion model).
- **`framework/Http/Kernel.php`** — Central HTTP orchestrator managing global middleware registration and request handling.
- **`framework/Http/Middleware/SecurityHeadersMiddleware.php`** — OWASP security headers (CSP, HSTS, X-Frame-Options, X-Content-Type-Options).
- **`framework/Http/Middleware/CorsMiddleware.php`** — Configurable Cross-Origin Resource Sharing handler.
- **`framework/Http/Middleware/ExceptionHandlerMiddleware.php`** — Catch-all exception interceptor converting uncaught exceptions into formatted HTML/JSON error responses.

---

## 2. How Current Implementation Fits with Previous Phase Implementation

- **Service Container Wire-up:** `Kernel` is resolved via `Application::make(Kernel::class)` initialized in Phase 0.
- **Autoloading:** All `Nexus\Http\*` classes are autoloaded using the custom PSR-4 autoloader created in `bootstrap/app.php`.
- **Helper Integration:** String/Array manipulation uses `Nexus\Support\Str` and `Nexus\Support\Arr` from Phase 0.
- **Configuration:** `SecurityHeadersMiddleware` and `CorsMiddleware` pull settings directly from `Nexus\Foundation\Config` loaded during bootstrap.

---

## 3. How to Build

### Step-by-Step Implementation:

1. **`framework/Http/Request.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Http;

   class Request
   {
       public function __construct(
           public readonly string $method,
           public readonly string $uri,
           public readonly array $headers,
           public readonly array $query,
           public readonly array $post,
           public readonly array $files,
           public readonly array $cookies,
           public readonly string $rawBody
       ) {}

       public static function createFromGlobals(): static
       {
           $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
           $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
           
           $headers = [];
           foreach ($_SERVER as $key => $value) {
               if (str_starts_with($key, 'HTTP_')) {
                   $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                   $headers[$headerName] = $value;
               }
           }

           $rawBody = file_get_contents('php://input') ?: '';

           return new static($method, $uri, $headers, $_GET, $_POST, $_FILES, $_COOKIE, $rawBody);
       }

       public function header(string $key, ?string $default = null): ?string
       {
           $key = strtolower($key);
           foreach ($this->headers as $k => $v) {
               if (strtolower($k) === $key) return $v;
           }
           return $default;
       }

       public function isJson(): bool
       {
           return str_contains($this->header('Content-Type', ''), 'application/json');
       }

       public function json(?string $key = null, mixed $default = null): mixed
       {
           $data = json_decode($this->rawBody, true) ?? [];
           if ($key === null) return $data;
           return $data[$key] ?? $default;
       }
   }
   ```

2. **`framework/Http/Response.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Http;

   class Response
   {
       public function __construct(
           protected string $content = '',
           protected int $statusCode = 200,
           protected array $headers = []
       ) {}

       public function send(): void
       {
           http_response_code($this->statusCode);
           foreach ($this->headers as $name => $value) {
               header("$name: $value");
           }
           echo $this->content;
       }

       public function setHeader(string $name, string $value): static
       {
           $this->headers[$name] = $value;
           return $this;
       }

       public function getStatusCode(): int
       {
           return $this->statusCode;
       }
   }
   ```

3. **`framework/Http/JsonResponse.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Http;

   class JsonResponse extends Response
   {
       public function __construct(mixed $data = [], int $status = 200, array $headers = [])
       {
           $headers['Content-Type'] = 'application/json';
           $content = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
           parent::__construct($content, $status, $headers);
       }
   }
   ```

4. **`framework/Http/MiddlewareInterface.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Http;

   interface MiddlewareInterface
   {
       public function handle(Request $request, \Closure $next): Response;
   }
   ```

5. **`framework/Http/MiddlewareStack.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Http;

   class MiddlewareStack
   {
       protected array $middlewares = [];

       public function add(MiddlewareInterface|callable $middleware): void
       {
           $this->middlewares[] = $middleware;
       }

       public function handle(Request $request, \Closure $coreHandler): Response
       {
           $pipeline = array_reduce(
               array_reverse($this->middlewares),
               function (\Closure $next, $middleware) {
                   return function (Request $request) use ($next, $middleware) {
                       if ($middleware instanceof MiddlewareInterface) {
                           return $middleware->handle($request, $next);
                       }
                       return $middleware($request, $next);
                   };
               },
               $coreHandler
           );

           return $pipeline($request);
       }
   }
   ```

6. **`framework/Http/Kernel.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Http;

   class Kernel
   {
       protected MiddlewareStack $pipeline;

       public function __construct()
       {
           $this->pipeline = new MiddlewareStack();
           $this->bootstrapGlobalMiddleware();
       }

       protected function bootstrapGlobalMiddleware(): void
       {
           $this->pipeline->add(new Middleware\ExceptionHandlerMiddleware());
           $this->pipeline->add(new Middleware\SecurityHeadersMiddleware());
           $this->pipeline->add(new Middleware\CorsMiddleware());
       }

       public function handle(Request $request): Response
       {
           return $this->pipeline->handle($request, function (Request $req) {
               return new Response('NexusPHP Core Active', 200);
           });
       }
   }
   ```

---

## 4. Success Criteria

- [ ] `Request::createFromGlobals()` accurately parses HTTP verb, headers, and body.
- [ ] Pipeline executes middleware in sequential onion order (outer to inner and back).
- [ ] `Kernel::handle()` returns a valid `Response` object with standard security headers.
- [ ] Exceptions inside middleware/handlers are caught gracefully by `ExceptionHandlerMiddleware`.
- [ ] Inode budget impact remains strictly within allocated headroom (+8 files).
