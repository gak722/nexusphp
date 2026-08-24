<?php
declare(strict_types=1);

namespace Nexus\Console\Commands;

use Nexus\Console\Command;

class MakeSeederCommand extends Command
{
    protected string $name = 'make:seeder';
    protected string $description = 'Create a new database seeder class';

    public function execute(array $args): int
    {
        $name = $args[0] ?? null;
        if (!$name) {
            $this->error("Error: Missing seeder name. Usage: php nexus make:seeder <Name>");
            return 1;
        }

        $className = ucfirst($name);
        if (!str_ends_with($className, 'Seeder')) {
            $className .= 'Seeder';
        }

        $targetDir = $this->app->basePath('database/seeders');
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $targetFile = $targetDir . '/' . $className . '.php';
        if (file_exists($targetFile)) {
            $this->error("Error: Seeder [{$className}] already exists.");
            return 1;
        }

        $template = <<<PHP
<?php
declare(strict_types=1);

namespace Database\Seeders;

use Nexus\Database\Seeding\Seeder;

class {$className} extends Seeder
{
    public function run(): void
    {
        // Add database seeding logic here...
    }
}
PHP;

        file_put_contents($targetFile, $template);
        $this->success("Seeder [{$targetFile}] created successfully.");
        return 0;
    }
}
