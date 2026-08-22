<?php
declare(strict_types=1);

use Nexus\Http\JsonResponse;
use Nexus\Http\Request;

class HttpTest
{
    public function testHttpRequestResponseLifecycle(): void
    {
        $req = new Request('GET', '/api/home', ['Content-Type' => 'application/json'], [], [], [], [], '');
        if (!$req->isJson()) {
            throw new \RuntimeException("Request fails to recognize Content-Type headers.");
        }

        $res = new JsonResponse(['status' => 'ok'], 200);
        if ($res->getStatusCode() !== 200) {
            throw new \RuntimeException("Response status code mismatch.");
        }

        if (!str_contains($res->getContent(), '"status":"ok"')) {
            throw new \RuntimeException("JsonResponse content encoding failure.");
        }
    }
}
