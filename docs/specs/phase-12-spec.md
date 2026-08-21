# Copilot Spec: Phase 12 — Zero-Dependency Test Suite, Benchmarks & Sample Apps

## Objective
Implement zero-dependency PHP assertion test runner (`TestRunner`), end-to-end HTTP/ORM feature tests, strict automated inode budget verification, performance benchmark suite, and sample CMS/API controllers.

## Target Files to Create / Modify
- `tests/TestRunner.php`
- `tests/Feature/HttpTest.php`
- `tests/Feature/OrmTest.php`
- `tests/Feature/InodeBudgetTest.php`
- `app/Http/Controllers/HomeController.php`

---

## Detailed Specifications

### 1. `tests/TestRunner.php`
- Scans `tests/*/*Test.php`.
- Executes methods starting with `test`.
- Prints colored status (✔ pass, ✖ fail) and summary counts.
- Exits with status code 0 on success, 1 on failure.

### 2. `tests/Feature/InodeBudgetTest.php`
- Executes shell command `find . -not -path '*/.*' | wc -l` on root folder.
- Fails assertion if node count exceeds hard limit 2,000.

---

## Copilot Validation Rules
- [ ] Test runner MUST NOT require PHPUnit or any external composer binary.
- [ ] Inode count limit strictly enforced <= 2000 nodes.
