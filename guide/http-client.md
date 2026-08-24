# HTTP Client

NexusPHP provides a minimal, expressive, zero-dependency HTTP client wrapper around PHP's `cURL` extension. It allows you to rapidly consume external APIs and handle responses seamlessly.

---

## Basic Usage

You can resolve the `HttpClient` via dependency injection or instantiate it directly.

```php
use Nexus\Http\Client\HttpClient;

$http = new HttpClient();
```

### Making Requests

You may make requests using any of the standard HTTP verbs (`get`, `post`, `put`, `patch`, `delete`):

```php
$response = $http->get('https://api.github.com/users/octocat');

$response = $http->post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

### Query Parameters

To append query parameters, use the `withQuery()` method:

```php
$response = $http->withQuery(['page' => 2, 'limit' => 50])
                 ->get('https://api.example.com/products');
```

---

## Request Configuration

The HTTP Client provides a highly fluent API for configuring outgoing requests.

### Base URLs

If you are making multiple requests to the same service, you can configure a base URL:

```php
$api = (new HttpClient())->baseUrl('https://api.example.com/v1');

$response1 = $api->get('/users');    // Hits https://api.example.com/v1/users
$response2 = $api->post('/orders');  // Hits https://api.example.com/v1/orders
```

### Headers & Authentication

You can add custom headers or authentication tokens fluently:

```php
$response = $http->withHeaders(['X-Custom-Header' => 'value'])
                 ->withBearerToken('your_api_token_here')
                 ->acceptJson()
                 ->get('https://api.example.com/secure');
```

If you need Basic Authentication, use the `withBasicAuth()` helper:

```php
$http->withBasicAuth('username', 'password')->get('...');
```

### Payloads & Content Types

By default, the `HttpClient` automatically JSON-encodes your payload arrays and sets `Content-Type: application/json`. 

If you need to send form data (`application/x-www-form-urlencoded`), use the `asForm()` modifier:

```php
$response = $http->asForm()->post('https://api.example.com/login', [
    'username' => 'admin',
    'password' => 'secret',
]);
```

### Retries & Timeouts

If an API is flaky, you can automatically retry failed connection attempts:

```php
// Retry 3 times, with a 100ms delay between attempts
$response = $http->retry(3, 100)->get('https://flaky-api.com');
```

You can also adjust the connection and response timeouts:

```php
$http->timeout(60) // Maximum total request duration (seconds)
     ->connectTimeout(5) // Maximum connection wait time (seconds)
     ->get('https://slow-api.com');
```

---

## Handling Responses

All requests return a `Nexus\Http\Client\HttpResponse` object which provides several helper methods to inspect the result.

```php
$response = $http->get('https://api.github.com/users/octocat');

$status = $response->status(); // 200
$isSuccess = $response->successful(); // true
$isError = $response->serverError(); // false
$headers = $response->headers(); // array of response headers
```

### Retrieving Data

To retrieve the raw response body as a string, use `$response->body()`. 

To automatically JSON-decode the response body into an array, use `$response->json()`:

```php
$data = $response->json();
echo $data['login']; // "octocat"
```

### Error Handling

By default, the HTTP client does **not** throw exceptions on 4xx or 5xx HTTP response codes. You must inspect `$response->successful()` manually.

If you prefer exceptions on failure, use the `throw()` method:

```php
// Throws a HttpRequestException if the status code is >= 400
$response = $http->get('https://api.example.com')->throw();
```

> [!NOTE]
> `HttpConnectionException` will still be thrown natively if the DNS fails to resolve or the server actively refuses the connection.
