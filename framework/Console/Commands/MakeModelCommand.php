<?php
declare(strict_types=1);

namespace Nexus\Console\Commands;

use Nexus\Console\Command;

class MakeModelCommand extends Command
{
    protected string $name = 'make:model';
    protected string $description = 'Create a new ActiveRecord Model class';

    public function execute(array $args): int
    {
        $name = $args[0] ?? null;
        if (!$name) {
            $this->error("Error: Missing model name. Usage: php nexus make:model <Name>");
            return 1;
        }

        $className = ucfirst($name);
        $tableName = strtolower($className) . 's';

        $targetDir = $this->app->basePath('app/Models');
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $targetFile = $targetDir . '/' . $className . '.php';
        if (file_exists($targetFile)) {
            $this->error("Error: Model [{$className}] already exists.");
            return 1;
        }

        $template = <<<PHP
<?php
declare(strict_types=1);

namespace App\Models;

use Nexus\Database\Model;

class {$className} extends Model
{
    protected static string \$table = '{$tableName}';
    protected static string \$primaryKey = 'id';
}
PHP;

        file_put_contents($targetFile, $template);
        $this->success("Model [{$targetFile}] created successfully.");
        return 0;
    }
}
