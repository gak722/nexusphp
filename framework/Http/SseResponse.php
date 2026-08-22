<?php
declare(strict_types=1);

namespace Nexus\Http;

/**
 * Server-Sent Events (SSE) Streaming Response
 */
class SseResponse extends Response
{
    public function __construct(protected \Closure $callback)
    {
        parent::__construct('', 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        @flush();

        try {
            ($this->callback)(function (string $event, mixed $data): void {
                echo "event: {$event}\n";
                echo "data: " . json_encode($data) . "\n\n";
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                @flush();
            });
        } catch (\Throwable $e) {
            $logDir = dirname(__DIR__, 2) . '/storage/logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }

            @file_put_contents(
                $logDir . '/nexus.log',
                sprintf(
                    "[%s] SSE Stream exception: %s in %s:%d\nStack trace:\n%s\n\n",
                    date('Y-m-d H:i:s'),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString()
                ),
                FILE_APPEND | LOCK_EX
            );
        }
    }
}
