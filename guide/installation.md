# Installation & Configuration

## System Requirements

Before you install NexusPHP, ensure your server meets the following requirements. 

**Required Software:**
- **PHP**: `>= 8.2` (as defined in `composer.json`)
- **Composer**: Recommended for managing the project structure, though NexusPHP contains zero third-party dependencies.

**Required PHP Extensions:**
*Note: `composer.json` does not strictly enforce extensions, but based on the framework's features, the following are implicitly required:*
- `PDO` (and driver for your database of choice, e.g., `pdo_sqlite`, `pdo_mysql`) - *used by the Database connection.*
- `sodium` - *used by the built-in `libsodium` secretbox encryption.*
- `json` - *used by HTTP requests and responses.*
> [TODO: Verify exact extension list by auditing codebase usages, as `composer.json` does not explicitly list them.]

## Installation

### Local Development Setup

The easiest way to install NexusPHP is via Composer or Git.

**Using Composer:**
```bash
composer create-project Zkr/nexusphp my-application
cd my-application
```

**Using Git:**
```bash
git clone https://github.com/Zkr/nexusphp.git my-application
cd my-application
composer install
```

**Post-Installation Steps:**
1. **Environment Configuration**: Copy the example configuration (if not already done).
   ```bash
   cp .env.example .env
   ```
   > [TODO: Verify if `.env.example` exists in the repository stub, as currently only `.env` is present in the root.]

2. **Run Migrations**: Initialize your database schema.
   ```bash
   php nexus migrate
   ```

3. **Start the Local Server**: NexusPHP includes a built-in development server via the `nexus` CLI.
   ```bash
   php nexus serve
   ```
   Your application will be accessible at `http://127.0.0.1:8000`.

### Production Deployment

When deploying your NexusPHP application to a production environment, follow these best practices:

1. **Document Root**: Ensure your web server (Nginx or Apache) directs all incoming requests to your application's `public` directory. The `public/index.php` file acts as the front controller. **Never** expose the root directory of your application to the web.

2. **Environment Variables**: Update your `.env` file for production:
   - `APP_ENV=production`
   - `APP_DEBUG=false`

3. **Directory Permissions**: Ensure the `storage` directory and its subdirectories (like `storage/logs`) are writable by your web server or PHP-FPM user.

4. **URL Rewriting**: 
   Since NexusPHP routes all traffic through `public/index.php`, you need proper rewrite rules.
   - **Nginx**: 
     ```nginx
     location / {
         try_files $uri $uri/ /index.php?$query_string;
     }
     ```
   - **Apache**: 
     Ensure your Apache configuration has `AllowOverride All` enabled and manually add an `.htaccess` file in the `public` directory with appropriate rewrite rules.
     > [TODO: Check if a default `public/.htaccess` should be added to the repository, as one does not currently exist.]

## Configuration

### Environment Variables

NexusPHP uses an `.env` file at the root of your project to manage environment-specific variables. Based on the current `.env` file, here are the key variables:

- `APP_NAME`: The name of your application.
- `APP_ENV`: The current environment (e.g., `development`, `production`).
- `APP_DEBUG`: When `true`, detailed stack traces are rendered for exceptions.
- `APP_URL`: The base URL of your application.
- `APP_KEY`: A 32-character string used for encryption and sessions. (e.g., `base64:c2VjcmV0a2V5bmV4dXNwaHAxMjM0NTY3ODkwMTIzNDU2`)
- `DB_DRIVER` / `DB_CONNECTION`: Defines the default database driver (e.g., `sqlite`, `mysql`).
- `DB_DATABASE`: The database name or path (e.g., `storage/database.sqlite`).
- `CACHE_DRIVER`: The caching mechanism to use (e.g., `file`).
- `QUEUE_CONNECTION`: The queue driver (e.g., `database`).

### Configuration Files

All configuration files are stored in the `config/` directory. Notable files include:
- `config/app.php`: Defines `env`, `debug`, `key`, and `log_path`.
- `config/database.php`: Configures the default connection and holds settings for `sqlite`, `mysql`, and `pgsql`. It utilizes variables like `DB_HOST`, `DB_PORT`, `DB_USERNAME`, and `DB_PASSWORD`.
- `config/security.php`: Manages security headers, CSRF, and CORS settings.
- `config/services.php`: Registers dependency injection services for the container.
- *Other files*: `broadcasting.php`, `cache.php`, `cors.php`, `http.php`, `mail.php`, `queue.php`.

### Configuration Caching

Due to NexusPHP's strict zero-dependency architecture and micro-footprint, configuration files are loaded extremely fast natively. There is no `config:cache` command in the `nexus` CLI, as it is unnecessary for this framework's design.

## Troubleshooting

If you encounter issues, check these common areas based on the framework's architecture:

- **Detailed Errors**: If you are getting a generic error page, ensure `APP_DEBUG=true` is set in your `.env` file to render the detailed stack trace.
- **Log Files**: Check the framework logs located in `storage/logs/`:
  - `storage/logs/nexus.log` (HTTP & Runtime Exceptions)
  - `storage/logs/events.log` (Event Listener Exceptions)
  - `storage/logs/queue_failed.log` (Queue Worker Job Failures)
- **Database Connection Failed**: Double-check your `.env` variables (`DB_DRIVER`, `DB_DATABASE`, etc.). For MySQL/PostgreSQL, check that `DB_HOST`, `DB_PORT`, `DB_USERNAME`, and `DB_PASSWORD` are correctly mapped in `config/database.php`.
- **"Route Not Found" on Sub-pages**: Your web server is likely not routing traffic through `public/index.php`. Review the URL Rewriting instructions.

---

**Next Steps:** Ready to explore how the framework is organized? Head over to the [Directory Structure](directory-structure.md) guide.
