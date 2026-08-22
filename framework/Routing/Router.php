<?php
declare(strict_types=1);

namespace Nexus\Routing;

use Nexus\Foundation\Application;
use Nexus\Http\ControllerDispatcher;
use Nexus\Http\MiddlewareStack;
use Nexus\Http\Request;
use Nexus\Http\Response;

/**
 * Main Router Facade / Dispatcher
 */
class Router
{
    protected static ?Router $instance = null;
    protected RouteCollection $routes;
    protected array $groupStack = [];

    public function __construct(protected Application $app)
    {
        $this->routes = new RouteCollection();
        static::$instance = $this;
    }

    public static function getInstance(): ?Router
    {
        return static::$instance;
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    public function get(string $uri, mixed $action): Route
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, mixed $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, mixed $action): Route
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function patch(string $uri, mixed $action): Route
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    public function delete(string $uri, mixed $action): Route
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function resource(string $name, string $controller): void
    {
        $this->get("/{$name}", [$controller, 'index'])->name("{$name}.index");
        $this->get("/{$name}/create", [$controller, 'create'])->name("{$name}.create");
        $this->post("/{$name}", [$controller, 'store'])->name("{$name}.store");
        $this->get("/{$name}/{id}", [$controller, 'show'])->name("{$name}.show");
        $this->get("/{$name}/{id}/edit", [$controller, 'edit'])->name("{$name}.edit");
        $this->put("/{$name}/{id}", [$controller, 'update'])->name("{$name}.update");
        $this->delete("/{$name}/{id}", [$controller, 'destroy'])->name("{$name}.destroy");
    }

    public function group(array $attributes, \Closure $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    public function addRoute(string $method, string $uri, mixed $action): Route
    {
        $prefix = $this->getCurrentPrefix();
        $fullUri = '/' . trim(rtrim($prefix, '/') . '/' . ltrim($uri, '/'), '/');
        if ($fullUri !== '/') {
            $fullUri = rtrim($fullUri, '/');
        }

        $route = new Route($method, $fullUri, $action);

        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $route->middleware($group['middleware']);
            }
        }

        $this->routes->add($route);
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
        $candidateRoutes = $this->routes->matchForMethod($request->method);

        foreach ($candidateRoutes as $route) {
            $parameters = [];
            if ($route->matches($request, $parameters)) {
                return $this->runRoute($route, $request, $parameters);
            }
        }

        // Check for Method Not Allowed (405)
        $allRoutes = $this->routes->getRoutes();
        $allowedMethods = [];
        foreach ($allRoutes as $route) {
            $dummy = [];
            $tempReq = new Request('GET', $request->uri, [], [], [], [], [], '');
            if (preg_match('#^' . preg_replace_callback('/\{([a-zA-Z0-9_]+)(?::([^}]+))?\}/', fn($m) => '(' . ($m[2] ?? '[^/]+') . ')', $route->uri) . '$#s', $request->uri)) {
                $allowedMethods[] = $route->method;
            }
        }

        if (!empty($allowedMethods)) {
            $allowedMethods = array_unique($allowedMethods);
            return new Response('405 Method Not Allowed', 405, ['Allow' => implode(', ', $allowedMethods)]);
        }

        return new Response('404 Not Found', 404);
    }

    protected function runRoute(Route $route, Request $request, array $parameters): Response
    {
        $routeMiddleware = $route->getMiddleware();

        $pipeline = new MiddlewareStack();
        foreach ($routeMiddleware as $mw) {
            if (is_string($mw)) {
                $mw = $this->app->make($mw);
            }
            $pipeline->add($mw);
        }

        return $pipeline->handle($request, function (Request $req) use ($route, $parameters) {
            $action = $route->action;

            if ($action instanceof \Closure) {
                $result = $action(...array_values($parameters));
            } elseif (is_array($action)) {
                [$controllerClass, $method] = $action;
                $controller = is_object($controllerClass) ? $controllerClass : $this->app->make($controllerClass);
                $dispatcher = new ControllerDispatcher($this->app);
                return $dispatcher->dispatch($controller, $method, $parameters);
            } else {
                throw new \RuntimeException("Invalid route action signature.");
            }

            if ($result instanceof Response) {
                return $result;
            }

            if (is_array($result) || $result instanceof \JsonSerializable) {
                return new \Nexus\Http\JsonResponse($result);
            }

            return new Response((string) $result);
        });
    }
}
