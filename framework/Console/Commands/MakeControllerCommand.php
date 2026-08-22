<?php
declare(strict_types=1);

namespace Nexus\Console\Commands;

use Nexus\Console\Command;

class MakeControllerCommand extends Command
{
    protected string $name = 'make:controller';
    protected string $description = 'Create a new HTTP controller class';

    public function execute(array $args): int
    {
        $name = $args[0] ?? null;
        if (!$name) {
            $this->error("Error: Missing controller name. Usage: php nexus make:controller <Name>");
            return 1;
        }

        $className = ucfirst($name);
        if (!str_ends_with($className, 'Controller')) {
            $className .= 'Controller';
        }

        $targetDir = $this->app->basePath('app/Http/Controllers');
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $targetFile = $targetDir . '/' . $className . '.php';
        if (file_exists($targetFile)) {
            $this->error("Error: Controller [{$className}] already exists.");
            return 1;
        }

        $template = <<<PHP
<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Nexus\Http\Request;
use Nexus\Http\Response;
use Nexus\Http\JsonResponse;

class {$className}
{
    public function index(Request \$request): Response
    {
        return new JsonResponse([
            'message' => 'Welcome to {$className}',
        ]);
    }
}
PHP;

        file_put_contents($targetFile, $template);
        $this->success("Controller [{$targetFile}] created successfully.");
        return 0;
    }
}
