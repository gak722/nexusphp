<?php
declare(strict_types=1);

namespace Nexus\Http\Middleware;

use Nexus\Foundation\Application;
use Nexus\Foundation\Config;
use Nexus\Http\JsonResponse;
use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Validation\ValidationException;

/**
 * Global Exception & Error Interceptor Middleware
 * Environment-aware debug reporting (stack traces, file, line) and automated file logging.
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
            $this->logException($e);

            $debugConfig = $this->config()->get('app.debug');
            $debug = $debugConfig !== null
                ? filter_var($debugConfig, FILTER_VALIDATE_BOOLEAN)
                : filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN);

            if ($request->isJson()) {
                $payload = [
                    'error' => true,
                    'message' => $e->getMessage(),
                ];

                if ($debug) {
                    $payload['exception'] = get_class($e);
                    $payload['file'] = $e->getFile();
                    $payload['line'] = $e->getLine();
                    $payload['trace'] = explode("\n", $e->getTraceAsString());
                }

                return new JsonResponse($payload, 500);
            }

            if ($debug) {
                $traceHtml = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');
                $html = sprintf(
                    "<!DOCTYPE html>
<html>
<head>
    <title>Unhandled Exception: %s</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 2rem; margin: 0; }
        .card { background: #1e293b; border-radius: 8px; padding: 1.5rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        h1 { color: #f43f5e; font-size: 1.5rem; margin-top: 0; }
        .meta { color: #94a3b8; font-family: monospace; font-size: 0.9rem; margin-bottom: 1rem; }
        pre { background: #090d16; padding: 1rem; border-radius: 6px; overflow-x: auto; color: #38bdf8; font-size: 0.85rem; line-height: 1.5; }
    </style>
</head>
<body>
    <div class='card'>
        <h1>%s: %s</h1>
        <div class='meta'>In <strong>%s</strong> on line <strong>%d</strong></div>
        <h3>Stack Trace:</h3>
        <pre>%s</pre>
    </div>
</body>
</html>",
                    htmlspecialchars(get_class($e), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars(get_class($e), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8'),
                    $e->getLine(),
                    $traceHtml
                );
            } else {
                $html = "<h1>500 Internal Server Error</h1><p>An unexpected error occurred. Please try again later.</p>";
            }

            return new Response($html, 500, ['Content-Type' => 'text/html']);
        }
    }

    protected function logException(\Throwable $e): void
    {
        $customLogPath = $this->config()->get('app.log_path');
        if ($customLogPath && is_string($customLogPath)) {
            $logFile = $customLogPath;
            $logDir = dirname($logFile);
        } else {
            $logDir = dirname(__DIR__, 3) . '/storage/logs';
            $logFile = $logDir . '/nexus.log';
        }

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logMessage = sprintf(
            "[%s] %s: %s in %s:%d\nStack trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    protected function config(): Config
    {
        try {
            $app = Application::getInstance();

            if ($app->has(Config::class)) {
                $config = $app->make(Config::class);

                if ($config instanceof Config) {
                    return $config;
                }
            }
        } catch (\Throwable $e) {
            // Fallback to standalone default Config instance
        }

        return new Config();
    }
}
