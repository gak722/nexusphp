# Nexus Console (CLI)

NexusPHP includes a lightweight command-line interface (CLI) tool available via the `php nexus` executable. It provides code generators, database migration runners, database seeders, task scheduling, and system health inspection utilities.

---

## Running Commands

To run any Nexus console command, execute `php nexus` from the root directory of your application:

```bash
php nexus <command> [arguments]
```

To list all available commands:

```bash
php nexus help
```

---

## Code Scaffolding Generators

NexusPHP provides CLI commands to rapidly generate boilerplate files following standard directory conventions:

### `make:controller`
Creates a new HTTP controller class in `app/Http/Controllers/`.

```bash
php nexus make:controller UserController
```

### `make:model`
Creates a new ActiveRecord model in `app/Models/`.

```bash
php nexus make:model Post
```

### `make:migration`
Generates a timestamped database migration file in `database/migrations/`.

```bash
php nexus make:migration create_posts_table
```

### `make:seeder`
Creates a database seeder in `database/seeders/`.

```bash
php nexus make:seeder UserSeeder
```

### `make:factory`
Creates a model factory in `database/factories/`.

```bash
php nexus make:factory UserFactory
```

### `make:resource`
Creates an API `JsonResource` in `app/Http/Resources/`.

```bash
php nexus make:resource UserResource
```

### `make:middleware`
Creates an HTTP middleware class in `app/Http/Middleware/`.

```bash
php nexus make:middleware Authenticate
```

### `make:policy`
Creates an authorization policy in `app/Policies/`.

```bash
php nexus make:policy PostPolicy
```

---

## Database Management

### `migrate`
Executes all pending database migrations within atomic transactions.

```bash
php nexus migrate
```

### `migrate:rollback`
Rolls back the previous batch of executed migrations.

```bash
php nexus migrate:rollback
```

### `migrate:status`
Displays execution status and file checksum integrity for all migrations.

```bash
php nexus migrate:status
```

### `db:seed`
Runs database seeders to populate initial or test data.

```bash
php nexus db:seed
php nexus db:seed Database\\Seeders\\UserSeeder
```

---

## Task Scheduling & System Health

### `schedule:run`
Executes scheduled tasks defined in `app/Console/Kernel.php` that are due.

```bash
php nexus schedule:run
```

### `health`
Inspects system health, database connection, storage directory write permissions, and memory usage.

```bash
php nexus health
```

---

## Local Development Server

### `serve`
Starts the native PHP development web server.

```bash
php nexus serve
```
