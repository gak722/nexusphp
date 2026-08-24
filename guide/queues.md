# Queues

NexusPHP includes a powerful, zero-dependency Queue subsystem designed to execute time-consuming tasks (like sending emails or processing uploaded images) asynchronously in the background. This dramatically speeds up web requests sent to your application.

---

## Configuration

Queue configuration is driven primarily by the `config/queue.php` configuration file or environment variables.

### Available Drivers

1. **`database`**: Stores serialized jobs in a `jobs` database table. Excellent for simple to medium-scale applications without external dependencies.
2. **`redis`**: Uses the `phpredis` extension to store jobs in Redis lists. Ideal for high-throughput, enterprise-scale applications.
3. **`sync`**: A fallback driver that executes jobs immediately and synchronously during the current web request. Useful for testing or local development without a worker process.

---

## Defining Jobs

Jobs are simple POJO classes that implement the `Nexus\Queue\Job` contract, requiring a single `handle()` method.

```php
namespace App\Jobs;

use Nexus\Queue\Job;
use Nexus\Mail\MailManager;

class ProcessPodcast implements Job
{
    public function __construct(public int $podcastId) {}

    public function handle(): void
    {
        // Complex, slow processing logic here...
        
        $mail = app(MailManager::class);
        $mail->raw('Podcast processing complete!')
             ->to('admin@example.com')
             ->send();
    }
}
```

---

## Dispatching Jobs

To push a job onto the queue, resolve the `Nexus\Queue\QueueManager` and call the `push` method:

```php
use Nexus\Queue\QueueManager;
use App\Jobs\ProcessPodcast;

$queue = app(QueueManager::class)->driver();

// Push the job onto the default queue
$queue->push(new ProcessPodcast(42));
```

### Specifying Queues

You can specify which queue channel a job should be dispatched to. This allows you to prioritize certain background workers (e.g., dedicating a worker solely to 'emails').

```php
$queue->push(new SendWelcomeEmail($user), 'emails');
```

---

## Running the Queue Worker

NexusPHP includes a robust CLI queue worker that acts as a long-running background process, popping jobs off the queue and executing their `handle()` methods.

To start the worker, use the `queue:work` command:

```bash
php nexus queue:work
```

### Worker Options

You can specify the specific queue channel the worker should process:

```bash
php nexus queue:work --queue=emails
```

The worker continuously loops in the background. It employs exponential backoff and sleep mechanisms when the queue is empty to reduce CPU usage. 

### Production Deployment

In a production environment, you should use a process monitor like **Supervisor** or **systemd** to ensure the `queue:work` process keeps running indefinitely and restarts automatically if it crashes.

---

## Database Queue Preparation

If you are using the `database` driver, you will need a table to hold the jobs. You should create a migration (`php nexus make:migration create_jobs_table`) with the following schema structure:

```php
use Nexus\Database\Schema;
use Nexus\Database\Schema\TableBuilder;

Schema::create('jobs', function (TableBuilder $table) {
    $table->id();
    $table->string('queue')->index();
    $table->text('payload');
    $table->integer('attempts')->default(0);
    $table->integer('reserved_at')->nullable();
    $table->integer('available_at');
    $table->timestamps();
});
```

---

## Exception Handling & Failed Jobs

If a job throws an exception during execution, the Queue Worker catches it. The worker tracks the number of `attempts`. If you build retry logic into your jobs, the worker will continue processing them. Currently, jobs that completely fail remain tracked in logs, allowing you to debug them natively.
