<?php
declare(strict_types=1);

namespace Nexus\Http;

/**
 * Abstract Base Controller Class
 */
abstract class Controller
{
    protected function json(mixed $data = [], int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    protected function response(string $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }

    protected function view(string $name, array $data = [], int $status = 200, array $headers = []): Response
    {
        return view($name, $data, $status, $headers);
    }
}
