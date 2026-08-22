# 02. Installation & Environment Setup

Setting up a NexusPHP application is instantaneous thanks to its zero-dependency architecture. You do not need `composer install` or external library downloads.

---

## 1. System Requirements

Ensure your server environment meets the following baseline requirements:

- **PHP Version:** PHP 8.4 or higher.
- **PHP Extensions:** `pdo`, `pdo_sqlite` (or `pdo_mysql`), `mbstring`, `openssl`, `apcu` (optional for APCu cache).
- **Web Server:** Built-in PHP CLI development server, Nginx, or Apache.

---

## 2. Directory Layout

The standard NexusPHP directory tree is lightweight, self-contained, and intuitive:

```
nexusphp/
├── app/                  # Application Code (Controllers, Models, Middleware)
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   ├── Models/
│   └── Support/
├── bootstrap/            # Application Bootstrap & Helpers
│   ├── app.php
│   └── helpers.php
├── config/               # Application Configuration Files
│   ├── app.php
│   ├── database.php
│   └── cache.php
├── framework/            # NexusPHP Core Framework Engine
├── public/               # Web Root (index.php, CSS, JS, Assets)
├── resources/            # Views & Documentation Files
│   ├── docs/
│   └── views/
├── routes/               # Route Definitions (web.php, api.php)
├── storage/              # SQLite Database, Logs, Session Files
├── tests/                # Zero-Dependency Test Suite
├── nexus                 # Nexus CLI Executable Tool
└── .env                  # Environment Configuration
```

---

## 3. Environment Configuration

Application environment variables are managed via the `.env` file at the project root.

```env
APP_NAME="NexusPHP Documentation"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=base64:c2VjcmV0a2V5bmV4dXNwaHAxMjM0NTY3ODkwMTIzNDU2

DB_DRIVER=sqlite
DB_DATABASE=storage/database.sqlite

CACHE_DRIVER=file
QUEUE_CONNECTION=database
```

> [!TIP]
> Never commit your production `.env` file to source control. Use `.env.example` as a template for team deployment.

---

## 4. Serving the Application

To launch the built-in development server, run the `nexus` CLI command:

```bash
php nexus serve
```

You will see output confirming the application is listening:

```text
NexusPHP Development Server started: http://localhost:8000
Press Ctrl+C to stop the server.
```

Open your browser and navigate to `http://localhost:8000/docs/01-introduction`.

---

## 5. Next Steps

Learn how NexusPHP handles dependency injection and lifecycle orchestration in [03. Application Architecture & Service Container](03-architecture.md).
