<?php
declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

$app = Nexus\Foundation\Application::getInstance();
$kernel = $app->make(Nexus\Http\Kernel::class);

$request = Nexus\Http\Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
