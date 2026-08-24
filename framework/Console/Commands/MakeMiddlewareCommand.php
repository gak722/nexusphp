<?php
declare(strict_types=1);

namespace Nexus\Console\Commands;

use Nexus\Console\Command;

class MakeMiddlewareCommand extends Command
{
    protected string $name = 'make:middleware';
    protected string $description = 'Create a new HTTP middleware class';

    public function execute(array $args): int
    {
        $name = $args[0] ?? null;
        if (!$name) {
            $this->error("Error: Missing middleware name. Usage: php nexus make:middleware <Name>");
            return 1;
        }

        $className = ucfirst($name);
        $targetDir = $this->app->basePath('app/Http/Middleware');
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $targetFile = $targetDir . '/' . $className . '.php';
        if (file_exists($targetFile)) {
            $this->error("Error: Middleware [{$className}] already exists.");
            return 1;
        }

        $template = <<<PHP
<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Nexus\Http\MiddlewareInterface;
use Nexus\Http\Request;
use Nexus\Http\Response;

class {$className} implements MiddlewareInterface
{
    public function handle(Request \$request, Closure \$next): Response
    {
        return \$next(\$request);
    }
}
PHP;

        file_put_contents($targetFile, $template);
        $this->success("Middleware [{$targetFile}] created successfully.");
        return 0;
    }
}
