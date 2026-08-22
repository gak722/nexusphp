# 14. CLI Commands & Console Tooling

NexusPHP includes a terminal application framework (`Nexus\Console\ConsoleApplication`) executable via `php nexus`.

---

## 1. Built-in Core CLI Commands

| Command | Usage | Description |
| :--- | :--- | :--- |
| `serve` | `php nexus serve` | Launches the local HTTP development server. |
| `migrate` | `php nexus migrate` | Runs all pending database migrations. |
| `migrate:rollback` | `php nexus migrate:rollback` | Rolls back the last migration batch. |
| `make:controller` | `php nexus make:controller UserController` | Generates a new HTTP Controller stub. |
| `make:model` | `php nexus make:model Post` | Generates a new Active Record Model stub. |
| `make:migration` | `php nexus make:migration create_posts_table` | Generates a new migration blueprint file. |
| `queue:work` | `php nexus queue:work` | Starts background worker process for jobs. |

---

## 2. Writing Custom CLI Commands

Custom commands inherit from `Nexus\Console\Command`:

```php
namespace App\Console\Commands;

use Nexus\Console\Command;

class SendDailyDigestCommand extends Command
{
    protected string $signature = 'digest:send';
    protected string $description = 'Sends the daily summary email digest to all subscribed users';

    public function handle(): int
    {
        $this->info("Starting daily digest calculation...");

        // Business logic execution
        $subscriberCount = 42;

        $this->success("Digest successfully sent to {$subscriberCount} subscribers!");
        return 0; // Exit success code
    }
}
```

---

## 3. Command Output Helper Methods

- `$this->info("message")` - Prints standard blue/cyan text.
- `$this->success("message")` - Prints green success confirmation.
- `$this->error("message")` - Prints red error output.
- `$this->warn("message")` - Prints yellow warning banner.

---

## 4. Next Steps

Learn how to write automated test suites in [15. Zero-Dependency Testing Framework](15-testing.md).
