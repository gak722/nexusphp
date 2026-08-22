<?php
declare(strict_types=1);

namespace Nexus\Routing;

/**
 * Named Route URL Generator
 */
class UrlGenerator
{
    public function __construct(protected RouteCollection $routes) {}

    public function route(string $name, array $parameters = []): string
    {
        $route = $this->routes->getByName($name);
        if (!$route) {
            throw new \InvalidArgumentException("Route [{$name}] not defined.");
        }

        $uri = $route->uri;

        foreach ($parameters as $key => $value) {
            if (str_contains($uri, '{' . $key)) {
                $uri = preg_replace('/\{' . preg_quote($key, '/') . '(?::[^}]+)?\}/', (string)$value, $uri);
                unset($parameters[$key]);
            }
        }

        if (!empty($parameters)) {
            $uri .= '?' . http_build_query($parameters);
        }

        return $uri;
    }
}
