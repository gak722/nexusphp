<?php
declare(strict_types=1);

namespace Nexus\Http;

/**
 * Onion Middleware Pipeline Execution Stack
 */
class MiddlewareStack
{
    protected array $middlewares = [];

    public function add(MiddlewareInterface|callable $middleware): static
    {
        $this->middlewares[] = $middleware;
        return $this;
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
