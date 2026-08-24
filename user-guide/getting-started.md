# Getting Started with NexusPHP

Welcome to NexusPHP! This guide will help you get up and running quickly.

## Prerequisites

Before you begin, ensure you have the following installed:

- **PHP** >= 8.1
- **Composer** (dependency manager for PHP)
- **Node.js** & **npm** (for frontend assets)
- **Git** for version control
- **Database** (MySQL, PostgreSQL, SQLite, or SQL Server)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/your-org/nexusphp.git
cd nexusphp
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Configuration

Copy the example environment file and configure it:

```bash
cp .env.example .env
```

Generate an application key:

```bash
php artisan key:generate
```

### 4. Configure Your Database

Update your `.env` file with your database credentials:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexusphp
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Run Migrations

```bash
php artisan migrate
```

### 6. Seed the Database (Optional)

```bash
php artisan db:seed
```

### 7. Link Storage

```bash
php artisan storage:link
```

### 8. Start the Development Server

```bash
php artisan serve
```

Your application will be available at `http://localhost:8000`.

## Quick Start

### Basic Commands

| Command | Description |
|---------|-------------|
| `php artisan list` | List all available Artisan commands |
| `php artisan make:model Post` | Create a new model |
| `php artisan make:controller PostController` | Create a new controller |
| `php artisan make:middleware Authenticate` | Create a new middleware |

### Directory Structure

```
app/
  Http/
    Controllers/
    Middleware/
    Requests/
  Models/
  Services/
config/
  app.php
  database.php
  cache.php
  ...
database/
  migrations/
  seeders/
routes/
  web.php
  api.php
```

### Configuration Files

Key configuration files in the `config/` directory:

- `app.php` - Application configuration
- `database.php` - Database connection settings
- `cache.php` - Cache configuration
- `auth.php` - Authentication settings
- `session.php` - Session configuration

### Routes

Routes are defined in `routes/web.php` (web routes) and `routes/api.php` (API routes). Typical resource routes:

```php
use App\Http\Controllers\PostController;

Route::resource('posts', PostController::class);
```

### Authentication

NexusPHP includes built-in authentication scaffolding:

```bash
composer require laravel/breeze --dev
php artisan breeze:install
npm install && npm run dev
php artisan migrate
```

## Documentation

For more detailed information, refer to:

- **API Documentation**: Check the `docs/` directory
- **Phase Specifications**: Review `specs/` for phase-by-phase implementation details
- **Implementation Plans**: Review `phase-*.md` files for roadmap information

## Troubleshooting

Common issues and their solutions:

1. **Permission errors**: Ensure `storage/` and `bootstrap/cache/` directories are writable
   ```bash
   chmod -R 775 storage/ bootstrap/cache/
   chown -R www-data:www-data storage/ bootstrap/cache/
   ```

2. **Class not found**: Run Composer autoload optimization
   ```bash
   composer dump-autoload
   ```

3. **Database connection issues**: Verify your `.env` configuration and ensure your database is running

## Getting Help

- **GitHub Issues**: Report bugs and feature requests
- **Discord**: Join the community server
- **Documentation**: Check the `docs/` folder for detailed guides

---

*Happy coding with NexusPHP!*