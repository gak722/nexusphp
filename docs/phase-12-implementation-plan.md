# Phase 12: Test Suite, Benchmarks & Sample Apps

**Duration:** Week 14

---

## 1. What to Build

Phase 12 validates total framework stability, enforces strict inode budget compliance, runs PHPStan Level 8 static analysis, and provides zero-dependency end-to-end sample applications (CMS, API, and Chat).

### Core Deliverables:

- **`tests/TestRunner.php`** — Zero-dependency native PHP test runner executing unit and integration assertion suites.
- **`tests/Feature/HttpTest.php`** — End-to-end HTTP pipeline test.
- **`tests/Feature/OrmTest.php`** — Database active record and relationship integration tests.
- **`tests/Feature/InodeBudgetTest.php`** — Automated assertion confirming total framework + app skeleton inode count strictly under 2,000 nodes.
- **`app/Http/Controllers/HomeController.php`** — Production sample application demonstrating routing, ORM, views, and security.

---

## 2. How Current Implementation Fits with Previous Phase Implementation

- **Comprehensive Verification:** Exercises all framework subsystems developed across Phases 0 to 11.
- **Production Readiness Check:** Asserts adherence to non-functional metrics (throughput < 3.5ms, memory allocation < 2.0MB, total inodes < 2000).

---

## 3. How to Build

### Step-by-Step Implementation:

1. **`tests/TestRunner.php`**
   ```php
   <?php
   declare(strict_types=1);

   require __DIR__ . '/../bootstrap/app.php';

   class TestRunner
   {
       protected int $passed = 0;
       protected int $failed = 0;

       public function run(): void
       {
           echo "Running NexusPHP Test Suite...\n\n";

           $files = glob(__DIR__ . '/*/*Test.php');
           foreach ($files as $file) {
               require_once $file;
               $class = basename($file, '.php');
               $test = new $class();

               foreach (get_class_methods($test) as $method) {
                   if (str_starts_with($method, 'test')) {
                       try {
                           $test->$method();
                           echo "  ✔ {$class}::{$method}\n";
                           $this->passed++;
                       } catch (\Throwable $e) {
                           echo "  ✖ {$class}::{$method} failed: {$e->getMessage()}\n";
                           $this->failed++;
                       }
                   }
               }
           }

           echo "\nResults: {$this->passed} passed, {$this->failed} failed.\n";
           exit($this->failed > 0 ? 1 : 0);
       }
   }

   (new TestRunner())->run();
   ```

2. **`tests/Feature/InodeBudgetTest.php`**
   ```php
   <?php
   declare(strict_types=1);

   class InodeBudgetTest
   {
       public function testInodeBudgetLimit(): void
       {
           $rootDir = realpath(__DIR__ . '/../../');
           $command = "find " . escapeshellarg($rootDir) . " -not -path '*/.*' | wc -l";
           $count = (int) trim(shell_exec($command));

           if ($count > 2000) {
               throw new \RuntimeException("Inode budget exceeded! Found {$count} nodes (limit 2000).");
           }

           if ($count === 0) {
               throw new \RuntimeException("Inode count check failed.");
           }
       }
   }
   ```

---

## 4. Success Criteria

- [ ] `php tests/TestRunner.php` executes and passes 100% of unit/integration test assertions.
- [ ] Automated inode verification passes (`find . -type f -o -type d | wc -l` ≤ 2000).
- [ ] Benchmark test confirms baseline "Hello World" request latency is under 3.5ms with memory footprint under 2MB.
- [ ] Code passes PHPStan Level 8 static analysis with 100% type coverage.
