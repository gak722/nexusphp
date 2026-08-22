<?php
declare(strict_types=1);

namespace Nexus\Routing;

use Nexus\Http\Request;

/**
 * Route Object Data Structure
 */
class Route
{
    protected array $compiled;
    protected array $middleware = [];
    protected ?string $name = null;

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

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
