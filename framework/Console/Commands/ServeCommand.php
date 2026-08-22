<?php
declare(strict_types=1);

namespace Nexus\Console\Commands;

use Nexus\Console\Command;

class ServeCommand extends Command
{
    protected string $name = 'serve';
    protected string $description = 'Start local PHP development server';

    public function execute(array $args): int
    {
        $host = '127.0.0.1';
        $port = '8000';

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--host=')) {
                $host = substr($arg, 7);
            } elseif (str_starts_with($arg, '--port=')) {
                $port = substr($arg, 7);
            }
        }

        $publicDir = $this->app->basePath('public');

        $this->info("NexusPHP development server started at http://{$host}:{$port}");
        $this->info("Press Ctrl+C to stop the server.");

        passthru("php -S {$host}:{$port} -t {$publicDir}");

        return 0;
    }
}
