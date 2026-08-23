<?php
declare(strict_types=1);

namespace Nexus\Http\Client;

use Nexus\Http\Client\Exceptions\HttpConnectionException;
use Nexus\Http\Client\Exceptions\HttpException;

class FakeHttpTransport implements HttpTransport
{
    protected array $stubs = [];
    protected array $recordedRequests = [];

    public function __construct(array $stubs = [])
    {
        $this->stubs = $stubs;
    }

    public function stub(string $urlPattern, int $status = 200, array|string $body = [], array $headers = []): static
    {
        $this->stubs[$urlPattern] = [
            'status' => $status,
            'body' => is_array($body) ? json_encode($body) : $body,
            'headers' => $headers,
        ];
        return $this;
    }

    public function send(HttpRequest $request): HttpResponse
    {
        $this->recordedRequests[] = $request;

        foreach ($this->stubs as $pattern => $responseDef) {
            if ($this->matchPattern($pattern, $request->url)) {
                if (isset($responseDef['exception']) && $responseDef['exception'] instanceof \Throwable) {
                    throw $responseDef['exception'];
                }

                $status = $responseDef['status'] ?? 200;
                $body = $responseDef['body'] ?? '';
                if (is_array($body)) {
                    $body = json_encode($body);
                }
                $headers = $responseDef['headers'] ?? [];

                return new HttpResponse($status, $headers, (string) $body);
            }
        }

        // Default fake response if no stub matches
        return new HttpResponse(200, ['Content-Type' => 'application/json'], '{}');
    }

    public function getRecordedRequests(): array
    {
        return $this->recordedRequests;
    }

    protected function matchPattern(string $pattern, string $url): bool
    {
        if ($pattern === '*' || $pattern === $url) {
            return true;
        }

        $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#';
        return (bool) preg_match($regex, $url);
    }
}
