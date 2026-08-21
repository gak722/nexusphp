# Copilot Spec: Phase 11 — Nexus CLI Binary & Generator Tooling

## Objective
Deliver root `nexus` CLI executable binary, command routing framework, colored terminal output helper, code generators (`make:controller`, `make:model`, `make:migration`), and framework administrative CLI actions (`migrate`, `queue:work`, `serve`).

## Target Files to Create / Modify
- `nexus` (executable binary)
- `framework/Console/ConsoleApplication.php`
- `framework/Console/Command.php`
- `framework/Console/Commands/MakeControllerCommand.php`
- `framework/Console/Commands/MakeModelCommand.php`
- `framework/Console/Commands/MakeMigrationCommand.php`
- `framework/Console/Commands/MigrateCommand.php`
- `framework/Console/Commands/QueueWorkCommand.php`
- `framework/Console/Commands/ServeCommand.php`

---

## Detailed Specifications

### 1. Root `nexus` Binary
- Shebang line `#!/usr/bin/env php`.
- Requires `bootstrap/app.php`, instantiates `ConsoleApplication`, executes `run($argv)`, and exits with status code.

### 2. Generators (`make:*`)
- Outputs PSR-12 compliant, strictly typed PHP template files into target directories (`app/Http/Controllers/`, `app/Models/`, `database/migrations/`).

---

## Copilot Validation Rules
- [ ] Code generator templates MUST include `declare(strict_types=1);` by default.
- [ ] Generated file paths MUST fail safely if file already exists (prevent accidental overwriting).
