<?php
declare(strict_types=1);

namespace Nexus\Tests\Feature;

use Nexus\Http\Client\Exceptions\HttpConnectionException;
use Nexus\Http\Client\Exceptions\HttpRequestException;
use Nexus\Http\Client\Exceptions\HttpTimeoutException;
use Nexus\Http\Client\FakeHttpTransport;
use Nexus\Http\Client\HttpClient;
use Nexus\Http\Client\HttpResponse;
use Nexus\Support\Http;

use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase
{
    public function testBasicGetRequest(): void
    {
        $fake = new FakeHttpTransport([
            'https://api.example.com/users' => [
                'status' => 200,
                'body' => ['users' => [['id' => 1, 'name' => 'Alice']]],
                'headers' => ['Content-Type' => 'application/json']
            ]
        ]);

        $client = new HttpClient($fake);
        $response = $client->get('https://api.example.com/users');

        if (!$response->successful()) {
            throw new \RuntimeException("Expected response to be successful.");
        }
        if ($response->status() !== 200) {
            throw new \RuntimeException("Expected status code 200.");
        }
        if ($response->json('users.0.name') !== 'Alice') {
            throw new \RuntimeException("Expected user name to be Alice.");
        }
    }

    public function testPostJson(): void
    {
        $fake = new FakeHttpTransport([
            'https://api.example.com/users' => [
                'status' => 201,
                'body' => ['id' => 42, 'name' => 'John', 'email' => 'john@example.com'],
            ]
        ]);

        $client = new HttpClient($fake);
        $response = $client->asJson()->post('https://api.example.com/users', [
            'name' => 'John',
            'email' => 'john@example.com'
        ]);

        if ($response->status() !== 201) {
            throw new \RuntimeException("Expected status 201.");
        }
        if ($response->json('id') !== 42) {
            throw new \RuntimeException("Expected id 42.");
        }

        $recorded = $fake->getRecordedRequests();
        if (count($recorded) !== 1) {
            throw new \RuntimeException("Expected 1 recorded request.");
        }
        $req = $recorded[0];
        if ($req->headers['Content-Type'] !== 'application/json') {
            throw new \RuntimeException("Expected Content-Type application/json header.");
        }
        if (json_decode($req->body, true)['name'] !== 'John') {
            throw new \RuntimeException("Expected JSON body containing name John.");
        }
    }

    public function testFluentConfigurationAndBaseUrl(): void
    {
        $fake = new FakeHttpTransport([
            'https://api.example.com/v1/profile' => [
                'status' => 200,
                'body' => ['user' => 'admin'],
            ]
        ]);

        $client = (new HttpClient($fake))
            ->baseUrl('https://api.example.com/v1/')
            ->withBearerToken('secret_token_123')
            ->acceptJson();

        $response = $client->get('/profile');

        if (!$response->ok()) {
            throw new \RuntimeException("Expected response to be OK.");
        }

        $recorded = $fake->getRecordedRequests()[0];
        if ($recorded->url !== 'https://api.example.com/v1/profile') {
            throw new \RuntimeException("URL build incorrect: {$recorded->url}");
        }
        if ($recorded->headers['Authorization'] !== 'Bearer secret_token_123') {
            throw new \RuntimeException("Authorization header missing or incorrect.");
        }
    }

    public function testQueryParameters(): void
    {
        $fake = new FakeHttpTransport([
            'https://api.example.com/search?page=2&limit=10' => [
                'status' => 200,
                'body' => ['items' => []],
            ]
        ]);

        $client = new HttpClient($fake);
        $response = $client->withQuery(['page' => 2])->get('https://api.example.com/search', ['limit' => 10]);

        $recorded = $fake->getRecordedRequests()[0];
        if ($recorded->url !== 'https://api.example.com/search?page=2&limit=10') {
            throw new \RuntimeException("Query string formatting failed: {$recorded->url}");
        }
    }

    public function testThrowOnFailedResponse(): void
    {
        $fake = new FakeHttpTransport([
            'https://api.example.com/404' => [
                'status' => 404,
                'body' => ['error' => 'Not Found'],
            ]
        ]);

        $client = new HttpClient($fake);
        $response = $client->get('https://api.example.com/404');

        if (!$response->failed()) {
            throw new \RuntimeException("Expected response to be failed.");
        }

        $caught = false;
        try {
            $response->throw();
        } catch (HttpRequestException $e) {
            $caught = true;
            if ($e->getResponse()->status() !== 404) {
                throw new \RuntimeException("Exception status mismatch.");
            }
        }

        if (!$caught) {
            throw new \RuntimeException("Expected HttpRequestException to be thrown.");
        }
    }

    public function testHttpFacadeFake(): void
    {
        Http::fake([
            'https://api.example.com/*' => [
                'status' => 200,
                'body' => ['status' => 'ok'],
            ]
        ]);

        $response = Http::withToken('test-token')->get('https://api.example.com/status');
        if ($response->json('status') !== 'ok') {
            throw new \RuntimeException("Http facade fake failed.");
        }

        Http::restore();
    }
}
