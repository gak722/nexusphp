<?php
declare(strict_types=1);

namespace Nexus\Console;

use Nexus\Foundation\Application;

/**
 * CLI Command Registry & Dispatcher
 */
class ConsoleApplication
{
    protected array $commands = [];

    public function __construct(protected Application $app)
    {
        $this->registerDefaultCommands();
    }

    protected function registerDefaultCommands(): void
    {
        $this->add(new Commands\MigrateCommand($this->app));
        $this->add(new Commands\MigrateRollbackCommand($this->app));
        $this->add(new Commands\MigrateStatusCommand($this->app));
        $this->add(new Commands\QueueWorkCommand($this->app));
        $this->add(new Commands\ServeCommand($this->app));
        $this->add(new Commands\MakeControllerCommand($this->app));
        $this->add(new Commands\MakeModelCommand($this->app));
        $this->add(new Commands\MakeMigrationCommand($this->app));
    }

    public function add(Command $command): void
    {
        $this->commands[$command->getName()] = $command;
    }

    public function run(array $argv): int
    {
        $name = $argv[1] ?? 'help';

        if ($name === 'help' || $name === '--help' || $name === '-h' || !isset($this->commands[$name])) {
            echo "\033[32mNexusPHP Console Tools v1.0.0\033[0m\n\nAvailable commands:\n";
            foreach ($this->commands as $cmd) {
                echo "  \033[36m" . str_pad($cmd->getName(), 22) . "\033[0m" . $cmd->getDescription() . "\n";
            }
            return 0;
        }

        try {
            $command = $this->commands[$name];
            $args = array_slice($argv, 2);

            return $command->execute($args);
        } catch (\Throwable $e) {
            echo "\033[31mCommand execution failed: " . $e->getMessage() . "\033[0m\n";
            return 1;
        }
    }
}
