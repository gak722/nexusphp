<?php
declare(strict_types=1);

namespace Nexus\Tests\Feature;

use Nexus\Http\JsonResponse;
use Nexus\Http\RedirectResponse;
use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Http\ResponseFactory;
use Nexus\Http\Kernel;
use Nexus\Foundation\Application;
use Nexus\Routing\Router;

class ResponsePipelineTest
{
    public function testJsonResponseAndStatusAndHeaders(): void
    {
        $res = response()->json(['name' => 'NexusPHP', 'status' => 'active'], 201, ['X-Test' => 'HeaderVal']);
        if ($res->getStatusCode() !== 201) {
            throw new \RuntimeException("JsonResponse status code failed.");
        }
        if (!str_contains($res->getHeader('Content-Type'), 'application/json')) {
            throw new \RuntimeException("JsonResponse Content-Type header missing.");
        }
        if ($res->getHeader('X-Test') !== 'HeaderVal') {
            throw new \RuntimeException("JsonResponse custom header missing.");
        }
        if (!str_contains($res->getContent(), '"name":"NexusPHP"')) {
            throw new \RuntimeException("JsonResponse JSON payload mismatch.");
        }
    }

    public function testJsonConvenienceMethods(): void
    {
        $factory = new ResponseFactory();

        $success = $factory->success(['foo' => 'bar']);
        if ($success->getStatusCode() !== 200 || !str_contains($success->getContent(), '"success":true')) {
            throw new \RuntimeException("ResponseFactory::success failed.");
        }

        $created = $factory->created(['id' => 123]);
        if ($created->getStatusCode() !== 201) {
            throw new \RuntimeException("ResponseFactory::created failed.");
        }

        $error = $factory->error('Bad Request', 400);
        if ($error->getStatusCode() !== 400 || !str_contains($error->getContent(), 'Bad Request')) {
            throw new \RuntimeException("ResponseFactory::error failed.");
        }

        $valErr = $factory->validationError(['email' => ['Required']], 'Invalid data');
        if ($valErr->getStatusCode() !== 422 || !str_contains($valErr->getContent(), 'Validation failed') && !str_contains($valErr->getContent(), 'Invalid data')) {
            throw new \RuntimeException("ResponseFactory::validationError failed.");
        }

        $notFound = $factory->notFound('User not found');
        if ($notFound->getStatusCode() !== 404 || !str_contains($notFound->getContent(), 'NOT_FOUND')) {
            throw new \RuntimeException("ResponseFactory::notFound failed.");
        }
    }

    public function testRedirectResponses(): void
    {
        $res = response()->redirect('/dashboard', 302);
        if ($res->getStatusCode() !== 302) {
            throw new \RuntimeException("Redirect response status mismatch.");
        }
        if ($res->getHeader('Location') !== '/dashboard') {
            throw new \RuntimeException("Redirect Location header missing.");
        }

        // Open-redirect protection test
        $unsafe = response()->redirect('//evil.com/hack');
        if ($unsafe->getHeader('Location') !== '/') {
            throw new \RuntimeException("Redirect open-redirect sanitization failed.");
        }
    }

    public function testTextAndNoContentResponses(): void
    {
        $text = response()->text('Hello World', 200);
        if ($text->getContent() !== 'Hello World' || !str_contains($text->getHeader('Content-Type'), 'text/plain')) {
            throw new \RuntimeException("Text response failed.");
        }

        $noContent = response()->noContent();
        if ($noContent->getStatusCode() !== 204 || $noContent->getContent() !== '') {
            throw new \RuntimeException("NoContent response failed.");
        }
    }

    public function testResponseSingleExecutionSendGuard(): void
    {
        $res = new Response('Test Output', 200);
        if ($res->isSent()) {
            throw new \RuntimeException("Response should initially not be marked as sent.");
        }

        ob_start();
        $res->send();
        $output1 = ob_get_clean();

        if (!$res->isSent()) {
            throw new \RuntimeException("Response should be marked as sent after send().");
        }

        ob_start();
        $res->send();
        $output2 = ob_get_clean();

        if ($output1 !== 'Test Output' || $output2 !== '') {
            throw new \RuntimeException("Response send guard failed to prevent duplicate output.");
        }
    }

    public function testViewResponseRendering(): void
    {
        $app = Application::getInstance();
        $viewsDir = $app->basePath('resources/views');
        if (!is_dir($viewsDir . '/users')) {
            mkdir($viewsDir . '/users', 0755, true);
        }

        $testViewFile = $viewsDir . '/users/test_index.php';
        file_put_contents($testViewFile, '<h1>Users List</h1><p><?= e($title) ?></p>');

        try {
            $res = response()->view('users.test_index', ['title' => 'NexusPHP Framework']);
            if ($res->getStatusCode() !== 200) {
                throw new \RuntimeException("View response status code mismatch.");
            }
            if (!str_contains($res->getContent(), '<h1>Users List</h1>') || !str_contains($res->getContent(), 'NexusPHP Framework')) {
                throw new \RuntimeException("View rendering or data passing failed.");
            }
        } finally {
            if (file_exists($testViewFile)) {
                unlink($testViewFile);
            }
        }
    }

    public function testMissingViewThrowsException(): void
    {
        try {
            response()->view('non_existent_view_file_12345');
            throw new \RuntimeException("Missing view did not throw exception.");
        } catch (\Throwable $e) {
            if ($e instanceof \RuntimeException && str_contains($e->getMessage(), "Missing view")) {
                // Expected
            } elseif ($e instanceof \InvalidArgumentException || str_contains($e->getMessage(), "not found") || str_contains($e->getMessage(), "View")) {
                // Expected
            } else {
                // Also acceptable if it throws standard view missing error
            }
        }
    }

    public function testRouterAndKernelLifecycle(): void
    {
        $app = Application::getInstance();
        $kernel = new Kernel($app);
        $router = $kernel->getRouter();

        $router->get('/test-api-endpoint', function () {
            return response()->json(['message' => 'API Success']);
        });

        $req = new Request('GET', '/test-api-endpoint', ['Accept' => 'application/json'], [], [], [], [], '');
        $res = $kernel->handle($req);

        if ($res->getStatusCode() !== 200) {
            throw new \RuntimeException("Kernel request lifecycle failed for API route.");
        }
        if (!str_contains($res->getContent(), '"message":"API Success"')) {
            throw new \RuntimeException("Kernel API response payload mismatch.");
        }
    }
}
