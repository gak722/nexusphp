<?php
declare(strict_types=1);

use App\Http\Controllers\DocsController;
use Nexus\Http\RedirectResponse;

/** @var \Nexus\Routing\Router $router */

// Redirect root to /docs/01-introduction
$router->get('/', function () {
    return new RedirectResponse('/docs/01-introduction');
});

// Docs root redirect or handler
$router->get('/docs', [DocsController::class, 'show']);

// Docs slug route
$router->get('/docs/{slug}', [DocsController::class, 'show']);
