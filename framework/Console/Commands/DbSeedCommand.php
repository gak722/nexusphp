<?php
declare(strict_types=1);

namespace Nexus\Console\Commands;

use Nexus\Console\Command;

class DbSeedCommand extends Command
{
    protected string $name = 'db:seed';
    protected string $description = 'Seed the database with records';

    public function execute(array $args): int
    {
        $class = $args[0] ?? 'Database\\Seeders\\DatabaseSeeder';

        if (!class_exists($class)) {
            $this->error("Error: Seeder class [{$class}] not found.");
            return 1;
        }

        $this->info("Seeding database using [{$class}]...");

        try {
            $seeder = new $class();
            if (method_exists($seeder, 'run')) {
                $seeder->run();
                $this->success("Database seeded successfully.");
                return 0;
            }

            $this->error("Error: Method 'run' not found on seeder [{$class}].");
            return 1;
        } catch (\Throwable $e) {
            $this->error("Seeding failed: " . $e->getMessage());
            return 1;
        }
    }
}
