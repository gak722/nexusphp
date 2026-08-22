<?php
declare(strict_types=1);

namespace Nexus\Http\Middleware;

use Nexus\Http\JsonResponse;
use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Validation\ValidationException;

/**
 * Exception Interceptor Middleware handling 500s and 422 Validation exceptions
 */
class ExceptionHandlerMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): Response
    {
        try {
            return $next($request);
        } catch (ValidationException $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
                'errors' => $e->errors,
            ], 422);
        } catch (\Throwable $e) {
            if ($request->isJson()) {
                return new JsonResponse([
                    'error' => true,
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ], 500);
            }

            $html = sprintf(
                "<h1>500 Internal Server Error</h1><p>%s</p>",
                htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            );

            return new Response($html, 500, ['Content-Type' => 'text/html']);
        }
    }
}
