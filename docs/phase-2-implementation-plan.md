# Phase 2: Router, Parameter Compiler & Controllers

**Duration:** Week 3

---

## 1. What to Build

Phase 2 introduces the routing infrastructure, URL parameter compiler, route groups, and controller invocation mechanism. It enables path matching (`/users/{id:\d+}`), HTTP verb dispatching, dependency injection into controller methods, and route-level middleware stacks.

### Core Deliverables:

- **`framework/Routing/Route.php`** — Data structure defining HTTP method, URI pattern, action (closure/controller), parameters, and route middleware.
- **`framework/Routing/RouteCollection.php`** — Collection manager indexing routes by HTTP method for fast lookup.
- **`framework/Routing/RouteCompiler.php`** — Regex parameter compiler converting parameterized routes (`/posts/{slug}`) into regex patterns.
- **`framework/Routing/Router.php`** — Fluent interface for defining routes (`GET`, `POST`, `PUT`, `DELETE`, `resource()`), groups, and prefixes.
- **`framework/Routing/UrlGenerator.php`** — Named route URL generator with dynamic parameter substitution.
- **`framework/Http/ControllerDispatcher.php`** — Controller method resolver using `Container` reflection to inject dependencies and route parameters into action methods.
- **`framework/Http/Controller.php`** — Abstract base controller providing standard helper methods (`validate()`, `json()`, `view()`, `redirect()`).

---

## 2. How Current Implementation Fits with Previous Phase Implementation

- **Pipeline Integration:** `Router::dispatch(Request $request)` becomes the core inner handler passed into Phase 1's `MiddlewareStack`.
- **Request/Response Protocol:** The router receives Phase 1's `Request` object and returns a Phase 1 `Response` or `JsonResponse`.
- **Container Dependency Resolution:** `ControllerDispatcher` leverages Phase 0's `Container` auto-wiring to instantiate controllers and resolve method type-hinted dependencies automatically.

---

## 3. How to Build

### Step-by-Step Implementation:

1. **`framework/Routing/RouteCompiler.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Routing;

   class RouteCompiler
   {
       public static function compile(string $uri): array
       {
           $paramNames = [];
           $pattern = preg_replace_callback('/\{([a-zA-Z0-9_]+)(?::([^}]+))?\}/', function ($matches) use (&$paramNames) {
               $paramNames[] = $matches[1];
               $regex = $matches[2] ?? '[^/]+';
               return "({$regex})";
           }, $uri);

           $pattern = '#^' . $pattern . '$#s';

           return [
               'pattern' => $pattern,
               'paramNames' => $paramNames,
           ];
       }
   }
   ```

2. **`framework/Routing/Route.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Routing;

   use Nexus\Http\Request;
   use Nexus\Http\Response;

   class Route
   {
       protected array $compiled;
       protected array $middleware = [];

       public function __construct(
           public readonly string $method,
           public readonly string $uri,
           public readonly mixed $action
       ) {
           $this->compiled = RouteCompiler::compile($uri);
       }

       public function matches(Request $request, array &$parameters): bool
       {
           if (strtoupper($request->method) !== strtoupper($this->method)) {
               return false;
           }

           if (preg_match($this->compiled['pattern'], $request->uri, $matches)) {
               array_shift($matches);
               $parameters = [];
               foreach ($this->compiled['paramNames'] as $index => $name) {
                   $parameters[$name] = $matches[$index] ?? null;
               }
               return true;
           }

           return false;
       }

       public function middleware(string|array $middleware): static
       {
           $this->middleware = array_merge($this->middleware, (array) $middleware);
           return $this;
       }

       public function getMiddleware(): array
       {
           return $this->middleware;
       }
   }
   ```

3. **`framework/Routing/Router.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Routing;

   use Nexus\Http\Request;
   use Nexus\Http\Response;
   use Nexus\Foundation\Application;

   class Router
   {
       protected static ?Router $instance = null;
       protected array $routes = [];
       protected array $groupStack = [];

       public function __construct(protected Application $app)
       {
           static::$instance = $this;
       }

       public static function getInstance(): Router
       {
           return static::$instance;
       }

       public function get(string $uri, mixed $action): Route
       {
           return $this->addRoute('GET', $uri, $action);
       }

       public function post(string $uri, mixed $action): Route
       {
           return $this->addRoute('POST', $uri, $action);
       }

       public function addRoute(string $method, string $uri, mixed $action): Route
       {
           $prefix = $this->getCurrentPrefix();
           $fullUri = rtrim($prefix, '/') . '/' . ltrim($uri, '/');
           if ($fullUri !== '/') $fullUri = rtrim($fullUri, '/');

           $route = new Route($method, $fullUri, $action);
           
           if (!empty($this->groupStack)) {
               $lastGroup = end($this->groupStack);
               if (isset($lastGroup['middleware'])) {
                   $route->middleware($lastGroup['middleware']);
               }
           }

           $this->routes[] = $route;
           return $route;
       }

       protected function getCurrentPrefix(): string
       {
           $prefix = '';
           foreach ($this->groupStack as $group) {
               if (isset($group['prefix'])) {
                   $prefix .= '/' . trim($group['prefix'], '/');
               }
           }
           return $prefix;
       }

       public function dispatch(Request $request): Response
       {
           foreach ($this->routes as $route) {
               $parameters = [];
               if ($route->matches($request, $parameters)) {
                   return $this->runRoute($route, $request, $parameters);
               }
           }

           return new Response('404 Not Found', 404);
       }

       protected function runRoute(Route $route, Request $request, array $parameters): Response
       {
           $action = $route->action;

           if ($action instanceof \Closure) {
               return $action(...array_values($parameters));
           }

           if (is_array($action)) {
               [$controllerClass, $method] = $action;
               $controller = $this->app->make($controllerClass);
               
               $dispatcher = new \Nexus\Http\ControllerDispatcher($this->app);
               return $dispatcher->dispatch($controller, $method, $parameters);
           }

           throw new \RuntimeException("Invalid route action signature.");
       }
   }
   ```

4. **`framework/Http/ControllerDispatcher.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Http;

   use Nexus\Foundation\Application;

   class ControllerDispatcher
   {
       public function __construct(protected Application $app) {}

       public function dispatch(object $controller, string $method, array $routeParams): Response
       {
           $reflector = new \ReflectionMethod($controller, $method);
           $args = [];

           foreach ($reflector->getParameters() as $param) {
               $name = $param->getName();
               $type = $param->getType();

               if (array_key_exists($name, $routeParams)) {
                   $args[] = $routeParams[$name];
                   continue;
               }

               if ($type && !$type->isBuiltin()) {
                   $args[] = $this->app->make($type->getName());
                   continue;
               }

               if ($param->isDefaultValueAvailable()) {
                   $args[] = $param->getDefaultValue();
               } else {
                   $args[] = null;
               }
           }

           $result = $reflector->invokeArgs($controller, $args);

           if ($result instanceof Response) {
               return $result;
           }

           if (is_array($result) || $result instanceof \JsonSerializable) {
               return new JsonResponse($result);
           }

           return new Response((string) $result);
       }
   }
   ```

---

## 4. Success Criteria

- [ ] Parameterized routes (`/posts/{id:\d+}`) match correctly and pass parsed arguments to actions.
- [ ] Route groups correctly nest prefixes and route middleware.
- [ ] Controller action dependencies are automatically injected via the Service Container.
- [ ] Controller actions returning arrays automatically serialize to `JsonResponse`.
- [ ] Unmatched requests return clean 404 response status codes.
