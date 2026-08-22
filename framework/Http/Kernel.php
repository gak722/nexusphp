<?php
declare(strict_types=1);

namespace Nexus\Http;

/**
 * Central HTTP Orchestrator Kernel
 */
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

    public function prependMiddleware(MiddlewareInterface|callable $middleware): static
    {
        $this->pipeline->add($middleware);
        return $this;
    }
}
