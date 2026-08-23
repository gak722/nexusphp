<?php
declare(strict_types=1);

namespace Nexus\Http\Client;

use Nexus\Http\Client\Exceptions\HttpConnectionException;
use Nexus\Http\Client\Exceptions\HttpRequestException;

/**
 * Primary HTTP Client for consuming external APIs fluently.
 */
class HttpClient
{
    protected ?HttpTransport $transport = null;
    protected ?string $baseUrl = null;
    protected array $headers = [];
    protected array $queryParams = [];
    protected ?string $bodyType = null; // 'json', 'form', or null
    protected array $options = [];
    protected int $retryAttempts = 0;
    protected int $retryDelayMs = 0;

    public function __construct(?HttpTransport $transport = null, array $defaultOptions = [])
    {
        $this->transport = $transport;
        $this->options = array_merge([
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify_ssl' => true,
        ], $defaultOptions);
    }

    public function setTransport(HttpTransport $transport): static
    {
        $this->transport = $transport;
        return $this;
    }

    public function getTransport(): HttpTransport
    {
        if ($this->transport === null) {
            $this->transport = new CurlTransport();
        }
        return $this->transport;
    }

    public function baseUrl(string $baseUrl): static
    {
        $clone = clone $this;
        $clone->baseUrl = rtrim($baseUrl, '/');
        return $clone;
    }

    public function withHeaders(array $headers): static
    {
        $clone = clone $this;
        foreach ($headers as $key => $val) {
            $clone->headers[$key] = $val;
        }
        return $clone;
    }

    public function withHeader(string $name, string $value): static
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    public function withToken(string $token, string $type = 'Bearer'): static
    {
        return $this->withHeader('Authorization', trim($type . ' ' . $token));
    }

    public function withBearerToken(string $token): static
    {
        return $this->withToken($token, 'Bearer');
    }

    public function withBasicAuth(string $username, string $password): static
    {
        $credentials = base64_encode("{$username}:{$password}");
        return $this->withHeader('Authorization', "Basic {$credentials}");
    }

    public function timeout(int $seconds): static
    {
        $clone = clone $this;
        $clone->options['timeout'] = $seconds;
        return $clone;
    }

    public function connectTimeout(int $seconds): static
    {
        $clone = clone $this;
        $clone->options['connect_timeout'] = $seconds;
        return $clone;
    }

    public function withQuery(array $query): static
    {
        $clone = clone $this;
        $clone->queryParams = array_merge($clone->queryParams, $query);
        return $clone;
    }

    public function asJson(): static
    {
        $clone = clone $this;
        $clone->bodyType = 'json';
        $clone->headers['Content-Type'] = 'application/json';
        $clone->headers['Accept'] = 'application/json';
        return $clone;
    }

    public function asForm(): static
    {
        $clone = clone $this;
        $clone->bodyType = 'form';
        $clone->headers['Content-Type'] = 'application/x-www-form-urlencoded';
        return $clone;
    }

    public function acceptJson(): static
    {
        return $this->withHeader('Accept', 'application/json');
    }

    public function retry(int $times, int $sleepMs = 100): static
    {
        $clone = clone $this;
        $clone->retryAttempts = max(0, $times);
        $clone->retryDelayMs = max(0, $sleepMs);
        return $clone;
    }

    public function get(string $url, array $query = []): HttpResponse
    {
        return $this->send('GET', $url, query: $query);
    }

    public function post(string $url, mixed $data = null): HttpResponse
    {
        return $this->send('POST', $url, $data);
    }

    public function put(string $url, mixed $data = null): HttpResponse
    {
        return $this->send('PUT', $url, $data);
    }

    public function patch(string $url, mixed $data = null): HttpResponse
    {
        return $this->send('PATCH', $url, $data);
    }

    public function delete(string $url, mixed $data = null): HttpResponse
    {
        return $this->send('DELETE', $url, $data);
    }

    public function head(string $url, array $query = []): HttpResponse
    {
        return $this->send('HEAD', $url, query: $query);
    }

    public function options(string $url, array $query = []): HttpResponse
    {
        return $this->send('OPTIONS', $url, query: $query);
    }

    public function send(string $method, string $url, mixed $data = null, array $query = []): HttpResponse
    {
        $method = strtoupper($method);
        $fullUrl = $this->buildUrl($url, $query);
        $headers = $this->headers;
        $formattedBody = $this->formatBody($data, $headers);

        $request = new HttpRequest($method, $fullUrl, $headers, $formattedBody, $this->options);

        $attempts = 0;
        $maxAttempts = 1 + $this->retryAttempts;

        while ($attempts < $maxAttempts) {
            $attempts++;
            try {
                return $this->getTransport()->send($request);
            } catch (HttpConnectionException $e) {
                // Retry only on connection failures or safe GET/HEAD/OPTIONS requests if specified
                $isSafe = in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);
                if ($attempts < $maxAttempts && $isSafe) {
                    if ($this->retryDelayMs > 0) {
                        usleep($this->retryDelayMs * 1000);
                    }
                    continue;
                }
                throw $e;
            }
        }

        throw new HttpConnectionException("Request failed after {$maxAttempts} attempts.");
    }

    protected function buildUrl(string $url, array $query = []): string
    {
        $fullUrl = $url;
        if ($this->baseUrl !== null && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            $fullUrl = $this->baseUrl . '/' . ltrim($url, '/');
        }

        $allQuery = array_merge($this->queryParams, $query);
        if (empty($allQuery)) {
            return $fullUrl;
        }

        $queryString = http_build_query($allQuery, '', '&', PHP_QUERY_RFC3986);
        $separator = str_contains($fullUrl, '?') ? '&' : '?';

        return $fullUrl . $separator . $queryString;
    }

    protected function formatBody(mixed $data, array &$headers): mixed
    {
        if ($data === null) {
            return null;
        }

        if (is_string($data)) {
            return $data;
        }

        if ($this->bodyType === 'form' || (!isset($headers['Content-Type']) && $this->bodyType !== 'json' && is_array($data))) {
            if (!isset($headers['Content-Type'])) {
                $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            }
            return http_build_query($data, '', '&', PHP_QUERY_RFC3986);
        }

        // Default or explicit asJson handling
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/json';
        }
        $encoded = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        return $encoded;
    }
}
