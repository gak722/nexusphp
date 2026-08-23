<?php
declare(strict_types=1);

namespace Nexus\Http\Client;

use Nexus\Http\Client\Exceptions\HttpConnectionException;
use Nexus\Http\Client\Exceptions\HttpTimeoutException;

class CurlTransport implements HttpTransport
{
    public function send(HttpRequest $request): HttpResponse
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('The cURL PHP extension is required to use CurlTransport.');
        }

        $ch = curl_init();

        $url = $request->url;
        $headers = $request->headers;
        $options = $request->options;

        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($request->method),
            CURLOPT_TIMEOUT => $options['timeout'] ?? 30,
            CURLOPT_CONNECTTIMEOUT => $options['connect_timeout'] ?? 10,
            CURLOPT_SSL_VERIFYPEER => $options['verify_ssl'] ?? true,
            CURLOPT_SSL_VERIFYHOST => ($options['verify_ssl'] ?? true) ? 2 : 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => $options['max_redirects'] ?? 5,
        ];

        if (!empty($options['user_agent'])) {
            $curlOptions[CURLOPT_USERAGENT] = $options['user_agent'];
        }

        // Format Headers
        $formattedHeaders = [];
        foreach ($headers as $name => $val) {
            if (is_array($val)) {
                foreach ($val as $subVal) {
                    $formattedHeaders[] = "{$name}: {$subVal}";
                }
            } else {
                $formattedHeaders[] = "{$name}: {$val}";
            }
        }
        $curlOptions[CURLOPT_HTTPHEADER] = $formattedHeaders;

        // Attach Body
        if ($request->body !== null) {
            $curlOptions[CURLOPT_POSTFIELDS] = $request->body;
        }

        curl_setopt_array($ch, $curlOptions);

        $rawResponse = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        if ($errno !== 0) {
            curl_close($ch);
            if (in_array($errno, [CURLE_OPERATION_TIMEDOUT, CURLE_OPERATION_TIMEOUTED], true)) {
                throw new HttpTimeoutException("cURL request timed out: {$error}", $errno);
            }
            throw new HttpConnectionException("cURL transport error ({$errno}): {$error}", $errno);
        }

        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $rawHeader = substr((string)$rawResponse, 0, $headerSize);
        $body = substr((string)$rawResponse, $headerSize);

        $parsedHeaders = $this->parseHeaders($rawHeader);

        return new HttpResponse($statusCode, $parsedHeaders, $body);
    }

    protected function parseHeaders(string $rawHeaders): array
    {
        $headers = [];
        $lines = explode("\r\n", trim($rawHeaders));

        foreach ($lines as $line) {
            if (str_contains($line, ':')) {
                [$key, $val] = explode(':', $line, 2);
                $key = trim($key);
                $val = trim($val);
                if (isset($headers[$key])) {
                    if (!is_array($headers[$key])) {
                        $headers[$key] = [$headers[$key]];
                    }
                    $headers[$key][] = $val;
                } else {
                    $headers[$key] = $val;
                }
            }
        }

        return $headers;
    }
}
