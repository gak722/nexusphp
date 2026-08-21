# Phase 11: Nexus CLI Console Tooling

**Duration:** Week 13

---

## 1. What to Build

Phase 11 delivers the `nexus` CLI executable binary and command ecosystem for generating scaffolding (controllers, models, migrations) and executing administrative operations (database migrations, queue worker loop, dev server).

### Core Deliverables:

- **`nexus`** — Standard executable CLI binary (`#!/usr/bin/env php`) in project root.
- **`framework/Console/ConsoleApplication.php`** — Command registry, argument/option parser, and command dispatcher.
- **`framework/Console/Command.php`** — Abstract command base class providing terminal output formatting (`info()`, `error()`, `success()`).
- **`framework/Console/Commands/MakeControllerCommand.php`** — Scaffolding generator for HTTP controllers.
- **`framework/Console/Commands/MakeModelCommand.php`** — Scaffolding generator for ActiveRecord models.
- **`framework/Console/Commands/MakeMigrationCommand.php`** — Generator for database migrations.
- **`framework/Console/Commands/MigrateCommand.php`** — Command invoking Phase 5 `Migrator`.
- **`framework/Console/Commands/QueueWorkCommand.php`** — Command invoking Phase 9 `Worker`.
- **`framework/Console/Commands/ServeCommand.php`** — PHP built-in web server launcher (`php -S localhost:8000 -t public`).

---

## 2. How Current Implementation Fits with Previous Phase Implementation

- **Foundation Bootstrap:** Loads Phase 0 `bootstrap/app.php` to initialize autoloader, environment variables, and container.
- **Subsystem Integration:** Directly dispatches into Phase 5 `Migrator` and Phase 9 `Worker`.

---

## 3. How to Build

### Step-by-Step Implementation:

1. **`nexus` (CLI Binary)**
   ```php
   #!/usr/bin/env php
   <?php
   declare(strict_types=1);

   $app = require __DIR__ . '/bootstrap/app.php';

   $console = new Nexus\Console\ConsoleApplication($app);
   $status = $console->run($_SERVER['argv']);
   exit($status);
   ```

2. **`framework/Console/ConsoleApplication.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Console;

   use Nexus\Foundation\Application;

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
           $this->add(new Commands\QueueWorkCommand($this->app));
           $this->add(new Commands\ServeCommand($this->app));
           $this->add(new Commands\MakeControllerCommand($this->app));
       }

       public function add(Command $command): void
       {
           $this->commands[$command->getName()] = $command;
       }

       public function run(array $argv): int
       {
           $name = $argv[1] ?? 'help';

           if ($name === 'help' || !isset($this->commands[$name])) {
               echo "NexusPHP Console Tools v1.0.0\n\nAvailable commands:\n";
               foreach ($this->commands as $cmd) {
                   echo "  " . str_pad($cmd->getName(), 20) . $cmd->getDescription() . "\n";
               }
               return 0;
           }

           $command = $this->commands[$name];
           $args = array_slice($argv, 2);

           return $command->execute($args);
       }
   }
   ```

---

## 4. Success Criteria

- [ ] Executable `php nexus` lists available commands cleanly.
- [ ] Generators create correctly namespaced PHP class files compliant with PSR-12 and strict types.
- [ ] `php nexus serve` boots local development server serving `public/index.php`.
