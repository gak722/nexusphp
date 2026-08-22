<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Nexus\Http\JsonResponse;
use Nexus\Http\Request;
use Nexus\Http\Response;

class HomeController
{
    public function index(Request $request): Response
    {
        return new JsonResponse([
            'framework' => 'NexusPHP',
            'version' => '1.0.0',
            'status' => 'operational',
            'timestamp' => time(),
        ]);
    }
}
