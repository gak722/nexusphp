<?php
declare(strict_types=1);

namespace Nexus\Http;

use Nexus\View\ViewFactory;

/**
 * Fluent Response Factory helper
 */
class ResponseFactory
{
    public function make(string $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }

    public function json(mixed $data = [], int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    public function view(string $name, array $data = [], int $status = 200, array $headers = []): Response
    {
        $factory = \Nexus\Foundation\Application::getInstance()->make(ViewFactory::class);
        $html = $factory->make($name, $data)->render();
        return new Response($html, $status, array_merge(['Content-Type' => 'text/html; charset=UTF-8'], $headers));
    }

    public function redirect(string $url, int $status = 302, array $headers = []): RedirectResponse
    {
        return new RedirectResponse($url, $status, $headers);
    }

    public function redirectRoute(string $name, array $parameters = [], int $status = 302, array $headers = []): RedirectResponse
    {
        $router = \Nexus\Routing\Router::getInstance();
        if ($router === null) {
            throw new \RuntimeException("Router instance is not available for named route redirection.");
        }
        $route = $router->getRoutes()->getByName($name);
        if ($route === null) {
            throw new \InvalidArgumentException("Route [{$name}] not found.");
        }

        $uri = $route->uri;
        foreach ($parameters as $key => $value) {
            $uri = str_replace('{' . $key . '}', (string)$value, $uri);
        }

        return new RedirectResponse($uri, $status, $headers);
    }

    public function text(string $text, int $status = 200, array $headers = []): Response
    {
        return new Response($text, $status, array_merge(['Content-Type' => 'text/plain; charset=UTF-8'], $headers));
    }

    public function noContent(int $status = 204, array $headers = []): Response
    {
        return new Response('', $status, $headers);
    }

    public function success(mixed $data = [], int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => $data,
        ], $status, $headers);
    }

    public function created(mixed $data = [], array $headers = []): JsonResponse
    {
        return $this->success($data, 201, $headers);
    }

    public function error(string $message, int $status = 400, mixed $details = null, array $headers = []): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];
        if ($details !== null) {
            $payload['error'] = $details;
        }
        return new JsonResponse($payload, $status, $headers);
    }

    public function validationError(mixed $errors, string $message = 'Validation failed', array $headers = []): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422, $headers);
    }

    public function notFound(string $message = 'Resource not found', array $headers = []): JsonResponse
    {
        return $this->error($message, 404, ['code' => 'NOT_FOUND'], $headers);
    }
}
