<?php
declare(strict_types=1);

namespace Nexus\Http;

/**
 * Streamed HTTP Response Wrapper
 */
class StreamedResponse extends Response
{
    protected mixed $callback;

    public function __construct(callable $callback, int $status = 200, array $headers = [])
    {
        $this->callback = $callback;
        parent::__construct('', $status, $headers);
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        if (is_callable($this->callback)) {
            ($this->callback)();
        }
    }
}
