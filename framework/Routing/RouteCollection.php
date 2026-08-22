<?php
declare(strict_types=1);

namespace Nexus\Routing;

/**
 * Indexed Route Collection Manager
 */
class RouteCollection
{
    protected array $routes = [];
    protected array $nameList = [];

    public function add(Route $route): void
    {
        $this->routes[strtoupper($route->method)][] = $route;
        if ($name = $route->getName()) {
            $this->nameList[$name] = $route;
        }
    }

    public function getRoutes(): array
    {
        $all = [];
        foreach ($this->routes as $methodRoutes) {
            foreach ($methodRoutes as $route) {
                $all[] = $route;
            }
        }
        return $all;
    }

    public function getByName(string $name): ?Route
    {
        return $this->nameList[$name] ?? null;
    }

    public function matchForMethod(string $method): array
    {
        return $this->routes[strtoupper($method)] ?? [];
    }
}
