<?php
declare(strict_types=1);

/**
 * NexusPHP Bootstrap File
 *
 * Registers the PSR-4 autoloader, loads support helpers,
 * configuration files, and environment variables.
 */

// PSR-4 Autoloader for Nexus\ namespace
spl_autoload_register(function (string $class): void {
    $prefix = 'Nexus\\';
    $baseDir = __DIR__ . '/../framework/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// PSR-4 Autoloader for App\ namespace
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load all Support helpers
foreach (glob(__DIR__ . '/../framework/Support/*.php') as $file) {
    require $file;
}

// Load bootstrap helpers
if (file_exists(__DIR__ . '/helpers.php')) {
    require __DIR__ . '/helpers.php';
}

// Load configuration files
$configDir = __DIR__ . '/../config';
if (is_dir($configDir)) {
    foreach (glob($configDir . '/*.php') as $file) {
        require $file;
    }
}

// Load environment variables
Nexus\Support\Env::load(__DIR__ . '/../.env');

// Create and return Application instance
return new Nexus\Foundation\Application();