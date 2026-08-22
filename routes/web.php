<?php
declare(strict_types=1);

use Nexus\Http\JsonResponse;

/** @var \Nexus\Routing\Router $router */

$router->get('/', function () {
    return new JsonResponse([
        'framework' => 'NexusPHP',
        'status' => 'online',
        'documentation' => 'https://nexusphp.dev/docs',
    ]);
});
