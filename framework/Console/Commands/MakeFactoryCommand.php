<?php
declare(strict_types=1);

namespace Nexus\Console\Commands;

use Nexus\Console\Command;

class MakeFactoryCommand extends Command
{
    protected string $name = 'make:factory';
    protected string $description = 'Create a new model factory class';

    public function execute(array $args): int
    {
        $name = $args[0] ?? null;
        if (!$name) {
            $this->error("Error: Missing factory name. Usage: php nexus make:factory <Name>");
            return 1;
        }

        $className = ucfirst($name);
        if (!str_ends_with($className, 'Factory')) {
            $className .= 'Factory';
        }

        $modelName = str_replace('Factory', '', $className);

        $targetDir = $this->app->basePath('database/factories');
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $targetFile = $targetDir . '/' . $className . '.php';
        if (file_exists($targetFile)) {
            $this->error("Error: Factory [{$className}] already exists.");
            return 1;
        }

        $template = <<<PHP
<?php
declare(strict_types=1);

namespace Database\Factories;

use Nexus\Database\Factory;
use App\Models\\{$modelName};

class {$className} extends Factory
{
    protected string \$model = {$modelName}::class;

    public function definition(): array
    {
        return [
            // Define model default state attributes here...
        ];
    }
}
PHP;

        file_put_contents($targetFile, $template);
        $this->success("Factory [{$targetFile}] created successfully.");
        return 0;
    }
}
