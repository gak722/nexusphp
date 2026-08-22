# 12. Queue Workers & Background Jobs

Offload time-consuming tasks (such as sending emails, processing file uploads, or third-party webhooks) out of the request-response cycle using the NexusPHP Queue system (`Nexus\Queue\QueueManager`).

---

## 1. Defining a Job Class (`Nexus\Queue\Job`)

Background jobs extend `Nexus\Queue\Job`:

```php
namespace App\Jobs;

use Nexus\Queue\Job;

class SendWelcomeEmailJob extends Job
{
    public function __construct(
        protected string $userEmail,
        protected string $userName
    ) {}

    public function handle(): void
    {
        // Execute background task logic
        $mailer = app(\App\Services\MailerService::class);
        $mailer->send($this->userEmail, "Welcome {$this->userName}!", "Thanks for registering.");
    }
}
```

---

## 2. Dispatching Jobs

Dispatch jobs from anywhere in your application:

```php
use App\Jobs\SendWelcomeEmailJob;

// Dispatch job to default database or redis queue
SendWelcomeEmailJob::dispatch('user@example.com', 'Jane Doe');
```

---

## 3. Running the Queue Worker

Process queued jobs continuously in the background using the `nexus` CLI:

```bash
php nexus queue:work
```

Output:

```text
[2026-08-22 14:00:01] Processing: App\Jobs\SendWelcomeEmailJob ...
[2026-08-22 14:00:02] Processed:  App\Jobs\SendWelcomeEmailJob (1.12s)
```

---

## 4. Failed Jobs & Retry Strategy

If a job throws an unhandled exception during execution:
- The worker catches the throwable and records the error details in `storage/logs/queue_failed.log`.
- Maximum attempt counts can be configured per job (`public int $tries = 3;`).

---

## 5. Next Steps

Explore event dispatching and real-time streaming in [13. Event Dispatcher & Server-Sent Events (SSE)](13-events-sse.md).
