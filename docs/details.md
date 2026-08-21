# Complete Roadmap: Custom PHP Framework Under 2000 Inodes  
**Codename:** NexusPHP  
**Goal:** Build a production-grade, secure, scalable, and highly extensible PHP framework with **fewer than 2000 filesystem inodes** (files + directories) while borrowing the best ideas from **Next.js, Express, and ASP.NET Core**.

---

## 1. Vision & Constraints

- **Hard limit:** `< 2000 inodes` total for the framework core + app skeleton.  
- **Zero mandatory Composer dependencies** – avoids `vendor/` inode explosion and supply-chain risk.  
- **PHP 8.2+** native features: `readonly`, `enum`, `attributes`, `fibers`, `match`.  
- **Target applications:** CMS, CRM, news portals, Telegram-like chat, distributed chat systems.  
- **Security:** OWASP Top 10 compliant, defense-in-depth, secure defaults.  
- **Scalability:** Stateless services, horizontal scaling, Redis integration, queue workers, WebSocket gateways.  

---

## 2. Inode Budget

Every file or directory counts as **1 inode**. NexusPHP keeps the core extremely lean.

| Area | Estimated Inodes |
|---|---|
| Framework core classes | ~120 |
| Bootstrap & helpers | ~8 |
| Configuration files | ~10 |
| Routes | ~4 |
| App skeleton (controllers/models/providers) | ~25 |
| Database migrations/seeds | ~5 |
| View files | ~8 |
| Storage directories | ~6 |
| Public entry | ~2 |
| **Total** | **~188** |

This leaves **~1800 inodes** for application-specific code, views, models, and migrations.

---

## 3. Architecture Overview

### Request Lifecycle (ASP.NET Core style pipeline + Express middleware)

```
Browser / Client
      │
      ▼
[ public/index.php ]           ← front controller
      │
      ▼
[ Bootstrap / Application ]    ← container, config, service providers
      │
      ▼
[ HTTP Kernel ]                ← global middleware stack
      │
      ├── ErrorHandling
      ├── CORS
      ├── SecurityHeaders
      ├── Session
      ├── CSRF
      ├── RateLimiter
      └── Router
            │
            ├── Route Middleware (auth, admin, etc.)
            │
            ▼
      [ Controller / Closure ]
            │
            ├── Validate Request
            ├── Business Logic
            ├── Database via ORM / Query Builder
            ├── Cache / Queue / Events
            │
            ▼
      [ Response ]             ← JSON, HTML, redirect, stream
      │
      ▼
[ Sent to client ]
```

### Core Design Principles

- **Dependency Injection** everywhere – constructor injection, service container.  
- **Middleware pipeline** – like Express, but with typed Request/Response.  
- **File-based routing** – routes in `routes/web.php` and `routes/api.php` are auto-loaded.  
- **Elegant ORM & migrations** – ActiveRecord-style models + schema builder.  
- **Graceful exception handling** – global handler, JSON/HTML responses, logging.  
- **Zero dependencies** – core uses only native PHP functions and optional PHP extensions.  

---

## 4. Directory Structure

```
nexusphp/
├── public/
│   └── index.php
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── HomeController.php
│   │   ├── Middleware/
│   │   │   └── AuthMiddleware.php
│   │   └── Requests/
│   │       └── LoginRequest.php
│   ├── Models/
│   │   └── User.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Services/
├── bootstrap/
│   ├── app.php
│   └── helpers.php
├── config/
│   ├── app.php
│   ├── database.php
│   ├── auth.php
│   ├── cache.php
│   ├── queue.php
│   └── session.php
├── database/
│   ├── migrations/
│   │   └── 0001_create_users_table.php
│   └── seeds/
│       └── DatabaseSeeder.php
├── routes/
│   ├── web.php
│   └── api.php
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.php
│       ├── home.php
│       └── errors/
│           ├── 404.php
│           └── 500.php
├── storage/
│   ├── cache/
│   ├── logs/
│   └── uploads/
├── framework/
│   ├── Foundation/
│   │   ├── Application.php
│   │   ├── Container.php
│   │   ├── ServiceProvider.php
│   │   └── Config.php
│   ├── Http/
│   │   ├── Kernel.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Middleware.php
│   │   ├── MiddlewareStack.php
│   │   ├── Router.php
│   │   ├── Route.php
│   │   ├── ControllerDispatcher.php
│   │   ├── JsonResponse.php
│   │   ├── RedirectResponse.php
│   │   └── StreamedResponse.php
│   ├── Routing/
│   │   ├── RouteCollection.php
│   │   ├── RouteCompiler.php
│   │   └── UrlGenerator.php
│   ├── View/
│   │   ├── View.php
│   │   ├── ViewFactory.php
│   │   ├── TemplateEngine.php
│   │   └── Component.php
│   ├── Database/
│   │   ├── Connection.php
│   │   ├── QueryBuilder.php
│   │   ├── Model.php
│   │   ├── Relations/
│   │   │   ├── Relation.php
│   │   │   ├── BelongsTo.php
│   │   │   ├── HasMany.php
│   │   │   ├── HasOne.php
│   │   │   └── BelongsToMany.php
│   │   ├── Schema.php
│   │   ├── Blueprint.php
│   │   ├── Migration.php
│   │   ├── Migrator.php
│   │   └── Seeder.php
│   ├── Security/
│   │   ├── Csrf.php
│   │   ├── Auth.php
│   │   ├── Password.php
│   │   ├── Encryptor.php
│   │   ├── RateLimiter.php
│   │   └── SecurityHeaders.php
│   ├── Validation/
│   │   ├── Validator.php
│   │   ├── Rule.php
│   │   └── Rules/
│   │       ├── Required.php
│   │       ├── Email.php
│   │       ├── Min.php
│   │       ├── Max.php
│   │       ├── Unique.php
│   │       └── Confirmed.php
│   ├── Cache/
│   │   ├── CacheManager.php
│   │   ├── CacheInterface.php
│   │   ├── FileCache.php
│   │   ├── RedisCache.php
│   │   └── ApcuCache.php
│   ├── Queue/
│   │   ├── QueueManager.php
│   │   ├── Job.php
│   │   ├── Worker.php
│   │   └── QueueInterface.php
│   ├── Events/
│   │   ├── Dispatcher.php
│   │   ├── Event.php
│   │   └── Listener.php
│   ├── Support/
│   │   ├── Str.php
│   │   ├── Arr.php
│   │   ├── Collection.php
│   │   └── Env.php
│   └── Console/
│       ├── ConsoleApplication.php
│       └── Commands/
│           ├── MakeController.php
│           ├── MakeModel.php
│           ├── MakeMigration.php
│           └── Migrate.php
└── .env.example
```

---

## 5. Feature Mapping from Next.js, Express, ASP.NET Core

| Source | Feature | NexusPHP Implementation |
|---|---|---|
| **Next.js** | File-based routing | `routes/web.php` + `routes/api.php` loaded automatically; URL convention maps to controllers |
| **Next.js** | API Routes | Controllers return `JsonResponse` / `Response` |
| **Next.js** | Middleware | Global and route-specific middleware chain |
| **Next.js** | Layouts & Components | View layouts, partials, components |
| **Next.js** | SSR / SSG / ISR | Native PHP rendering + full-page output cache / HTTP cache |
| **Express** | Minimal middleware pipeline | `MiddlewareStack` with `use()` / `pipe()` |
| **Express** | Flexible routing | Router verbs: `get`, `post`, `put`, `patch`, `delete`, `resource` |
| **Express** | Error middleware | Global exception handler middleware |
| **ASP.NET Core** | Dependency Injection | Built-in service container with constructor injection |
| **ASP.NET Core** | Configuration & Options | `.env` + `config/*.php`, typed config classes |
| **ASP.NET Core** | Model Binding & Validation | Request validation, automatic binding, form requests |
| **ASP.NET Core** | EF Core-like ORM & Migrations | ActiveRecord ORM + schema builder migrations |
| **ASP.NET Core** | Filters / Attributes | Route middleware and controller filters |
| **ASP.NET Core** | Authentication (Cookies/JWT) | Session auth + JWT auth with refresh tokens |

---

## 6. Core Components and Implementation Plan

### 6.1 HTTP Kernel & Middleware

```php
// framework/Http/Kernel.php
class Kernel
{
    private MiddlewareStack $stack;

    public function __construct()
    {
        $this->stack = new MiddlewareStack();
    }

    public function pipe(callable|string $middleware): self
    {
        $this->stack->add($middleware);
        return $this;
    }

    public function handle(Request $request): Response
    {
        return $this->stack->handle($request, fn($req) => new Response('Not Found', 404));
    }
}
```

Middleware example:

```php
class AuthMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::check()) {
            return redirect('/login');
        }
        return $next($request);
    }
}
```

### 6.2 Routing

```php
// routes/web.php
use Nexus\Http\Router;

Router::get('/', [HomeController::class, 'index']);

Router::group(['middleware' => 'auth'], function () {
    Router::get('/dashboard', [DashboardController::class, 'index']);
    Router::resource('/posts', PostController::class);
});

// routes/api.php
Router::prefix('/api')->group(function () {
    Router::post('/login', [AuthController::class, 'login']);
    Router::get('/posts', [PostController::class, 'index']);
});
```

### 6.3 Controllers & Dependency Injection

```php
class PostController extends Controller
{
    public function __construct(private PostService $posts) {}

    public function show(Request $request, int $id): Response
    {
        $post = $this->posts->findOrFail($id);
        return view('posts.show', compact('post'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|max:255',
            'body' => 'required',
        ]);

        $post = $this->posts->create($data);
        return response()->json($post, 201);
    }
}
```

### 6.4 View & Templating

Simple PHP templating with layouts and escaping:

```php
// resources/views/layouts/app.php
<!DOCTYPE html>
<html>
<head><title><?= e($title ?? 'NexusPHP') ?></title></head>
<body>
    <?= $content ?>
</body>
</html>
```

```php
// resources/views/posts/show.php
<h1><?= e($post->title) ?></h1>
<p><?= e($post->body) ?></p>
```

View rendering:

```php
return view('posts.show', compact('post'));
```

### 6.5 Database & ORM

```php
class Post extends Model
{
    protected string $table = 'posts';
    protected array $fillable = ['title', 'body', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
```

Query builder:

```php
$posts = Post::query()
    ->where('published', true)
    ->where('title', 'like', '%php%')
    ->orderByDesc('created_at')
    ->paginate(15);
```

### 6.6 Migrations

```php
class CreatePostsTable extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('body');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
}
```

Run migrations:

```bash
php nexus migrate
php nexus migrate:rollback
```

### 6.7 Validation

```php
$request->validate([
    'email' => 'required|email|unique:users,email',
    'password' => 'required|min:8|confirmed',
]);
```

Custom rules:

```php
class Uppercase extends Rule
{
    public function passes(string $field, mixed $value): bool
    {
        return strtoupper($value) === $value;
    }

    public function message(string $field): string
    {
        return "The $field must be uppercase.";
    }
}
```

### 6.8 Security

- **CSRF:** token per session, verified on `POST`, `PUT`, `PATCH`, `DELETE`.  
- **XSS:** `e()` helper escapes output; automatic escaping in views.  
- **SQL Injection:** All queries use PDO prepared statements.  
- **Auth:** `password_hash()` / `password_verify()`, JWT with `openssl` or `sodium`.  
- **Encryption:** `sodium_crypto_secretbox` / `sodium_crypto_aead_aes256gcm`.  
- **Headers:** CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy.  
- **Rate Limiting:** in-memory, file, or Redis driver.  
- **File Uploads:** MIME validation, size limits, random names, storage outside web root.

### 6.9 Caching

- Drivers: File, Redis, APCu.  
- HTTP cache: ETag, Last-Modified.  
- Query cache.  
- Full-page output cache.

```php
$cached = Cache::remember('posts:latest', 3600, fn() => Post::latest()->limit(10)->get());
```

### 6.10 Queue & Background Jobs

```php
class SendWelcomeEmail extends Job
{
    public function __construct(public int $userId) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        mail($user->email, 'Welcome', 'Hello!');
    }
}

Queue::push(new SendWelcomeEmail($user->id));
```

Worker:

```bash
php nexus queue:work
```

Drivers: Database, Redis, File.

### 6.11 Events & Realtime

```php
Event::listen(MessageSent::class, SendNotification::class);

Event::dispatch(new MessageSent($message));
```

Realtime broadcast via Redis pub/sub or WebSocket gateway.

### 6.12 CLI Console

```bash
php nexus make:controller PostController
php nexus make:model Post
php nexus make:migration create_posts_table
php nexus migrate
php nexus serve
php nexus queue:work
```

### 6.13 Exception Handling

```php
class ExceptionHandler
{
    public function report(Throwable $e): void
    {
        Logger::error($e->getMessage(), ['exception' => $e]);
    }

    public function render(Request $request, Throwable $e): Response
    {
        $status = $e instanceof HttpException ? $e->status : 500;

        if ($request->wantsJson()) {
            return response()->json(['error' => $e->getMessage()], $status);
        }

        return view("errors.$status", ['message' => $e->getMessage()], $status);
    }
}
```

---

## 7. Dependencies

### Required PHP Extensions

| Extension | Purpose |
|---|---|
| `pdo` | Database access |
| `pdo_mysql` / `pdo_pgsql` / `pdo_sqlite` | DB drivers |
| `mbstring` | Unicode strings |
| `openssl` | Encryption, TLS |
| `json` | JSON handling |
| `fileinfo` | File upload MIME detection |
| `intl` | Internationalization |
| `curl` | External HTTP requests (optional for APIs) |
| `session` | Session management |
| `sodium` | Modern cryptography (recommended) |

### Optional Extensions

| Extension | Purpose |
|---|---|
| `redis` | Cache, queue, sessions, pub/sub |
| `swoole` / `workerman` | Async HTTP, WebSockets, coroutines |
| `apcu` | In-memory opcode/user cache |
| `opcache` | Production performance |
| `bcmath` | Arbitrary precision math |

### No Composer Dependencies

The framework core uses **zero third-party Composer packages** to keep inode count low and avoid supply-chain vulnerabilities. Application developers may add Composer packages if needed, but the core remains dependency-free.

---

## 8. Security Hardening Guidelines

- **Defense in depth:** Multiple independent security layers.  
- **Secure defaults:** Cookies `HttpOnly`, `Secure`, `SameSite=Lax`.  
- **Password hashing:** `password_hash()` with `PASSWORD_BCRYPT` or `PASSWORD_ARGON2ID`.  
- **JWT:** Short-lived access tokens (15 min), refresh tokens rotated, signed with RS256 or HS256.  
- **Input validation:** Validate all incoming data; never trust client.  
- **Output escaping:** Escape all dynamic data in HTML attributes, text, JS, CSS.  
- **SQL injection:** Only prepared statements via PDO.  
- **CSRF:** Synchronizer token pattern for all state-changing requests.  
- **XSS:** Use `e()` helper, CSP headers.  
- **File upload:** Validate MIME, extension, size, image dimensions; store outside web root; randomize names.  
- **CORS:** Allowlist specific origins, methods, headers; credentials only for trusted origins.  
- **Rate limiting:** Per IP/user for login, API, file uploads.  
- **Audit logging:** Log authentication, authorization, admin actions.  
- **Encryption:** AES-256-GCM for data at rest, TLS for transit.  
- **No dangerous functions:** Disable `eval`, `exec`, `system`, `shell_exec`, `passthru`.  
- **Headers:** `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Strict-Transport-Security`, `Content-Security-Policy`.  
- **Session security:** Regenerate ID after login, session fixation protection, strict mode.  

---

## 9. Scalability & Deployment

### Architecture for Horizontal Scaling

```
                      [ CDN ]
                         |
              [ Load Balancer (Nginx/HAProxy) ]
                         |
        +----------------+----------------+
        |                                 |
[ Web Node 1 (PHP-FPM/Swoole) ]   [ Web Node 2 ]
        |                                 |
        +----------------+----------------+
                         |
                   [ Redis Cluster ]
                         |
              [ DB Primary + Read Replicas ]
                         |
        +----------------+----------------+
        |                                 |
[ Queue Worker 1 ]              [ WebSocket Gateway 1 ]
[ Queue Worker 2 ]              [ WebSocket Gateway 2 ]
```

### Scalability Practices

- **Stateless HTTP layer:** Sessions stored in Redis; any node can serve any request.  
- **Database replication:** Write to primary, read from replicas (query builder supports read/write split).  
- **Caching:** Redis for hot data, HTTP caching for pages.  
- **Queue:** Async processing for email, notifications, image processing.  
- **WebSocket:** Separate gateway service; Redis pub/sub for cross-node message fanout.  
- **CDN:** Serve static assets, cached pages, uploads.  
- **Autoscaling:** Docker + Kubernetes; scale web/ws nodes independently.  
- **Monitoring:** Structured logs, metrics, alerting.

---

## 10. Building Advanced Applications

### Telegram-like Chat

- **REST API:** Auth, contacts, groups, file upload.  
- **Realtime:** WebSocket gateway or SSE fallback.  
- **Message flow:**  
  1. Client sends message via REST or WS.  
  2. Server validates, persists to DB, dispatches `MessageSent` event.  
  3. Listener publishes to Redis channel `chat:updates`.  
  4. All WebSocket gateways subscribe to Redis and push to connected users.  
- **Presence:** Typing indicators, online status via WS.  
- **End-to-end encryption (optional):** `sodium_crypto_box` for private chats.  

### CMS / CRM / News Portal

- **CRUD resources:** `php nexus make:controller -r Post` generates RESTful controller.  
- **Admin panel:** Role-based access, CRUD forms, validation.  
- **Content types:** Migrations for posts, pages, categories, tags.  
- **Media library:** File uploads with validation and storage.  
- **Caching:** Full-page cache for public pages; invalidate on content update.  

### Distributed Chat Application

- **WebSocket Gateway:** Swoole/Workerman server, stateless except for connections.  
- **Redis Pub/Sub:** Message bus for all nodes.  
- **Database:** Chat messages, users, rooms.  
- **Queue:** Push notifications, message persistence.  
- **Scale:** Add more gateways; Redis handles fanout.  
- **Fallback:** SSE or long-polling when WebSockets unavailable.  

---

## 11. Learning Path

1. **PHP & OOP fundamentals** – classes, interfaces, traits, namespaces.  
2. **HTTP basics** – request/response, methods, headers, cookies.  
3. **MVC pattern** – understand separation of concerns.  
4. **Read NexusPHP source** – start with `public/index.php`, then `bootstrap/app.php`.  
5. **Understand request lifecycle** – follow a request through middleware, router, controller, response.  
6. **Build a simple blog** – routes, controller, model, view.  
7. **Add database** – migrations, query builder, ORM relations.  
8. **Add authentication** – register, login, password reset.  
9. **Build a CMS** – admin panel, CRUD, validation.  
10. **Build a realtime chat** – SSE first, then WebSocket gateway.  

---

## 12. Development Roadmap (Phases)

### Phase 0: Foundation (Week 1)
- Directory structure  
- `Application` container  
- Config loader, `.env` parser  
- Custom PSR-4 autoloader  
- Helpers  
- **Deliverable:** `public/index.php` boots the app.

### Phase 1: HTTP Kernel & Middleware (Week 2)
- Request/Response classes  
- Middleware stack  
- Global middleware: errors, security headers, CORS  
- **Deliverable:** A request passes through middleware and returns a response.

### Phase 2: Routing & Controllers (Week 3)
- Router with verbs, params, groups, resource routes  
- Controller dispatcher with DI  
- Route model binding  
- **Deliverable:** Routes resolve to controllers with typed parameters.

### Phase 3: Views & Templating (Week 4)
- View factory, layouts, partials  
- Output escaping  
- HTTP caching helpers  
- **Deliverable:** Render complex pages with layouts.

### Phase 4: Database & ORM (Week 5-6)
- Connection manager (PDO)  
- Query builder  
- ActiveRecord Model  
- Relations: BelongsTo, HasMany, HasOne, BelongsToMany  
- **Deliverable:** Perform CRUD and complex queries with models.

### Phase 5: Migrations & Seeds (Week 7)
- Schema builder  
- Migration runner  
- Seeders  
- **Deliverable:** Versioned database schema via CLI.

### Phase 6: Validation & Form Requests (Week 8)
- Validator, rules  
- Automatic request validation  
- **Deliverable:** Controllers validate input elegantly.

### Phase 7: Security & Auth (Week 9)
- CSRF, XSS, SQLi protections  
- Session auth, JWT  
- Password hashing, encryption  
- Rate limiting  
- **Deliverable:** Secure login, protected routes.

### Phase 8: Caching & Performance (Week 10)
- Cache drivers (File, Redis, APCu)  
- Query and page caching  
- **Deliverable:** Reduced DB load, faster responses.

### Phase 9: Queue & Jobs (Week 11)
- Queue manager  
- Job classes, worker  
- **Deliverable:** Async task processing.

### Phase 10: Events & Realtime (Week 12)
- Event dispatcher  
- Redis pub/sub broadcast  
- SSE controller  
- Optional WebSocket gateway (Swoole/Workerman)  
- **Deliverable:** Real-time updates.

### Phase 11: CLI Console (Week 13)
- Console application  
- Make commands  
- Migrate, queue:work, serve  
- **Deliverable:** Developer-friendly CLI.

### Phase 12: Testing, Docs & Samples (Week 14)
- Lightweight test harness  
- Documentation  
- Sample apps: blog, CMS, chat  
- **Deliverable:** Complete, learnable framework.

---

## 13. Coding Standards & Contribution Guidelines

- **PSR-4 autoloading:** `Nexus\` namespace mapped to `framework/`.  
- **PSR-12 coding style:** Consistent formatting.  
- **No globals:** Dependency injection only.  
- **Strict types:** `declare(strict_types=1);` in all files.  
- **Readonly where possible:** Use PHP 8.2 `readonly` classes/properties.  
- **Avoid magic methods:** Only `__construct`, `__invoke` if needed; no `__get`/`__set` in core.  
- **Error handling:** Throw typed exceptions; catch globally.  
- **Testing:** Write unit tests for core features using the built-in test runner.  
- **Documentation:** Every public method documented with PHPDoc.

---

## 14. Conclusion

NexusPHP proves that a **powerful, secure, and scalable PHP framework** can be built with **fewer than 2000 inodes** and **zero external dependencies**. It combines:

- **Next.js** developer experience: file-based routes, layouts, API routes, caching.  
- **Express** simplicity: middleware pipeline, flexible routing, minimal abstractions.  
- **ASP.NET Core** robustness: DI, configuration, validation, ORM, migrations.

This roadmap provides a complete, phased plan to build the framework and then use it to create anything from a blog to a distributed real-time chat platform — all while maintaining **military-grade security** and **production-grade scalability**.