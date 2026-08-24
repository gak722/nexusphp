<?php
declare(strict_types=1);

namespace Nexus\Http;

use Nexus\Routing\Router;

/**
 * Central HTTP Orchestrator Kernel
 */
class Kernel
{
    protected MiddlewareStack $pipeline;
    protected Router $router;

    public function __construct(protected \Nexus\Foundation\Application $app)
    {
        $this->router = $app->has(Router::class) ? $app->make(Router::class) : new Router($app);
        $this->pipeline = new MiddlewareStack();
        $this->bootstrapGlobalMiddleware();
    }

    protected function bootstrapGlobalMiddleware(): void
    {
        $this->pipeline->add(new Middleware\ExceptionHandlerMiddleware());
        $this->pipeline->add(new Middleware\SecurityHeadersMiddleware());
        $this->pipeline->add(new Middleware\CorsMiddleware());
        $this->pipeline->add(new Middleware\CsrfMiddleware());
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function handle(Request $request): Response
    {
        $this->app->instance(Request::class, $request);
        return $this->pipeline->handle($request, function (Request $req) {
            $this->app->instance(Request::class, $req);
            return $this->router->dispatch($req);
        });
    }
}
