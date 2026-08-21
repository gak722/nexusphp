# Copilot Spec: Phase 9 — Queue Subsystem & Worker CLI

## Objective
Implement asynchronous background job serialization, queue interfaces, Database queue driver, Redis list driver, and long-running worker CLI process (`Worker`).

## Target Files to Create / Modify
- `framework/Queue/Job.php`
- `framework/Queue/QueueInterface.php`
- `framework/Queue/DatabaseQueue.php`
- `framework/Queue/RedisQueue.php`
- `framework/Queue/Worker.php`
- `framework/Queue/QueueManager.php`

---

## Detailed Specifications

### 1. `framework/Queue/Job.php`
- Abstract base class with `public int $attempts = 0`, `public int $maxTries = 3`.
- Requires `abstract public function handle(): void`.

### 2. `framework/Queue/DatabaseQueue.php`
- Stores serialized payload into `jobs` table (`queue`, `payload`, `attempts`, `reserved_at`, `created_at`).

### 3. `framework/Queue/Worker.php`
- Infinite polling loop `work(string $queueName, int $sleep)` with attempt tracking and exception logging.

---

## Copilot Validation Rules
- [ ] Worker processes MUST handle database disconnects gracefully.
- [ ] Reserved jobs MUST update `reserved_at` timestamp immediately to prevent double execution across parallel worker instances.
