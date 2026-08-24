<?php
declare(strict_types=1);

namespace Nexus\Console\Commands;

use Nexus\Console\Command;

class MakeResourceCommand extends Command
{
    protected string $name = 'make:resource';
    protected string $description = 'Create a new API JsonResource class';

    public function execute(array $args): int
    {
        $name = $args[0] ?? null;
        if (!$name) {
            $this->error("Error: Missing resource name. Usage: php nexus make:resource <Name>");
            return 1;
        }

        $className = ucfirst($name);
        if (!str_ends_with($className, 'Resource')) {
            $className .= 'Resource';
        }

        $targetDir = $this->app->basePath('app/Http/Resources');
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $targetFile = $targetDir . '/' . $className . '.php';
        if (file_exists($targetFile)) {
            $this->error("Error: Resource [{$className}] already exists.");
            return 1;
        }

        $template = <<<PHP
<?php
declare(strict_types=1);

namespace App\Http\Resources;

use Nexus\Http\Resources\JsonResource;

class {$className} extends JsonResource
{
    public function toArray(): array
    {
        return [
            'id' => \$this->id,
        ];
    }
}
PHP;

        file_put_contents($targetFile, $template);
        $this->success("Resource [{$targetFile}] created successfully.");
        return 0;
    }
}
