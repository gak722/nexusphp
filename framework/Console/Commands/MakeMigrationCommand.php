<?php
declare(strict_types=1);

namespace Nexus\Console\Commands;

use Nexus\Console\Command;

class MakeMigrationCommand extends Command
{
    protected string $name = 'make:migration';
    protected string $description = 'Create a new database migration file';

    public function execute(array $args): int
    {
        $name = $args[0] ?? null;
        if (!$name) {
            $this->error("Error: Missing migration name. Usage: php nexus make:migration <create_users_table>");
            return 1;
        }

        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}.php";

        $targetDir = $this->app->basePath('database/migrations');
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $targetFile = $targetDir . '/' . $fileName;
        if (file_exists($targetFile)) {
            $this->error("Error: Migration [{$fileName}] already exists.");
            return 1;
        }

        $tableName = 'example_table';
        if (preg_match('/create_(.*)_table/', $name, $matches)) {
            $tableName = $matches[1];
        }

        $template = <<<PHP
<?php
declare(strict_types=1);

use Nexus\Database\Blueprint;
use Nexus\Database\Schema;

return new class {
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
PHP;

        file_put_contents($targetFile, $template);
        $this->success("Migration [{$fileName}] created successfully.");
        return 0;
    }
}
