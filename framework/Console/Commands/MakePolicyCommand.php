<?php
declare(strict_types=1);

namespace Nexus\Console\Commands;

use Nexus\Console\Command;

class MakePolicyCommand extends Command
{
    protected string $name = 'make:policy';
    protected string $description = 'Create a new authorization policy class';

    public function execute(array $args): int
    {
        $name = $args[0] ?? null;
        if (!$name) {
            $this->error("Error: Missing policy name. Usage: php nexus make:policy <Name>");
            return 1;
        }

        $className = ucfirst($name);
        if (!str_ends_with($className, 'Policy')) {
            $className .= 'Policy';
        }

        $targetDir = $this->app->basePath('app/Policies');
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $targetFile = $targetDir . '/' . $className . '.php';
        if (file_exists($targetFile)) {
            $this->error("Error: Policy [{$className}] already exists.");
            return 1;
        }

        $template = <<<PHP
<?php
declare(strict_types=1);

namespace App\Policies;

class {$className}
{
    public function view(\$user, \$model): bool
    {
        return true;
    }

    public function create(\$user): bool
    {
        return true;
    }

    public function update(\$user, \$model): bool
    {
        return true;
    }

    public function delete(\$user, \$model): bool
    {
        return true;
    }
}
PHP;

        file_put_contents($targetFile, $template);
        $this->success("Policy [{$targetFile}] created successfully.");
        return 0;
    }
}
