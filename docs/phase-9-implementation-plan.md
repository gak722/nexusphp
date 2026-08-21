# Phase 9: Queue Subsystem & Worker CLI

**Duration:** Week 11

---

## 1. What to Build

Phase 9 establishes the asynchronous queue processing engine, allowing background task execution (emails, data processing, notifications) off the main HTTP request-response cycle.

### Core Deliverables:

- **`framework/Queue/Job.php`** — Abstract job base class enforcing `handle()` contract and serialization helpers.
- **`framework/Queue/QueueInterface.php`** — Contract for queue drivers (`push()`, `pop()`, `delete()`, `failed()`).
- **`framework/Queue/DatabaseQueue.php`** — Database-backed queue driver storing jobs in a `jobs` table.
- **`framework/Queue/RedisQueue.php`** — High-performance Redis list driver using `rpush` / `lpop`.
- **`framework/Queue/Worker.php`** — Long-running CLI worker executing queued jobs with error retry and timeout mechanisms.
- **`framework/Queue/QueueManager.php`** — Factory managing default queue connections.

---

## 2. How Current Implementation Fits with Previous Phase Implementation

- **Database Connection Integration:** `DatabaseQueue` uses Phase 4 `Connection` to insert, reserve, and delete job records.
- **Redis Driver Integration:** `RedisQueue` reuses connection parameters configured in Phase 8 (`config/cache.php` or `config/queue.php`).
- **CLI Runner Integration:** `Worker` is invoked via `php nexus queue:work` provided by Phase 11's CLI console engine.

---

## 3. How to Build

### Step-by-Step Implementation:

1. **`framework/Queue/Job.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Queue;

   abstract class Job
   {
       public int $attempts = 0;
       public int $maxTries = 3;

       abstract public function handle(): void;
   }
   ```

2. **`framework/Queue/DatabaseQueue.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Queue;

   use Nexus\Database\Connection;

   class DatabaseQueue implements QueueInterface
   {
       public function __construct(protected Connection $connection, protected string $table = 'jobs') {}

       public function push(Job $job, string $queue = 'default'): bool
       {
           $payload = serialize($job);
           return $this->connection->statement(
               "INSERT INTO {$this->table} (queue, payload, attempts, reserved_at, created_at) VALUES (?, ?, 0, NULL, ?)",
               [$queue, $payload, time()]
           );
       }

       public function pop(string $queue = 'default'): ?Job
       {
           $jobRecord = $this->connection->select(
               "SELECT * FROM {$this->table} WHERE queue = ? AND reserved_at IS NULL ORDER BY id ASC LIMIT 1",
               [$queue]
           );

           if (empty($jobRecord)) return null;

           $record = $jobRecord[0];
           $this->connection->statement(
               "UPDATE {$this->table} SET reserved_at = ?, attempts = attempts + 1 WHERE id = ?",
               [time(), $record['id']]
           );

           $job = unserialize($record['payload']);
           $job->id = $record['id'];
           return $job;
       }

       public function delete(Job $job): bool
       {
           return $this->connection->statement("DELETE FROM {$this->table} WHERE id = ?", [$job->id]);
       }
   }
   ```

3. **`framework/Queue/Worker.php`**
   ```php
   <?php
   declare(strict_types=1);

   namespace Nexus\Queue;

   class Worker
   {
       public function __construct(protected QueueInterface $queue) {}

       public function work(string $queueName = 'default', int $sleep = 2): void
       {
           echo "nexus worker listening on [{$queueName}]...\n";

           while (true) {
               $job = $this->queue->pop($queueName);

               if ($job === null) {
                   sleep($sleep);
                   continue;
               }

               try {
                   echo "Processing job: " . get_class($job) . "\n";
                   $job->handle();
                   $this->queue->delete($job);
                   echo "Processed job: " . get_class($job) . "\n";
               } catch (\Throwable $e) {
                   echo "Failed job: " . get_class($job) . " - " . $e->getMessage() . "\n";
               }
           }
       }
   }
   ```

---

## 4. Success Criteria

- [ ] Jobs serialize into database or Redis backends correctly.
- [ ] Worker polls queues, reserves job items, and executes `handle()` methods.
- [ ] Completed jobs are pruned from storage; failed jobs track attempt counts and max retries.
- [ ] Non-blocking execution returns immediate response to HTTP clients while work executes asynchronously.
