<?php
declare(strict_types=1);

/**
 * NexusPHP Bootstrap File
 *
 * Registers the PSR-4 autoloader, loads support helpers,
 * configuration files, environment variables, and initializes Kernel & Router.
 */

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

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

// Load all Support helpers & Validation rules
foreach (glob(__DIR__ . '/../framework/Support/*.php') as $file) {
    require $file;
}
foreach (glob(__DIR__ . '/../framework/Validation/Rules/*.php') as $file) {
    require_once $file;
}

// Load bootstrap helpers
if (file_exists(__DIR__ . '/helpers.php')) {
    require __DIR__ . '/helpers.php';
}

// Load environment variables
Nexus\Support\Env::load(__DIR__ . '/../.env');

// Create Application instance
$app = new Nexus\Foundation\Application();

// Load configuration files into the shared Config repository.
// Each file in /config returns an array and is namespaced by its filename,
// e.g. config/security.php -> $config->get('security.headers')
$config = $app->make(Nexus\Foundation\Config::class);
$configDir = __DIR__ . '/../config';
if (is_dir($configDir)) {
    foreach (glob($configDir . '/*.php') as $file) {
        $value = require $file;
        if (is_array($value)) {
            $config->set(basename($file, '.php'), $value);
        }
    }
}

$appKey = $config->get('app.key', env('APP_KEY'));
if (empty($appKey) || strlen((string)$appKey) < 16 || $appKey === 'default_secret_key_32_bytes_len_!!') {
    throw new \RuntimeException("SECURITY FATAL: Application key [APP_KEY] is missing, insecurely configured, or under 16 characters long. Please update your .env file.");
}

// Bind Kernel and Router singletons
$kernel = new Nexus\Http\Kernel($app);
$app->addSingleton(Nexus\Http\Kernel::class, fn() => $kernel);

$router = $kernel->getRouter();
$app->addSingleton(Nexus\Routing\Router::class, fn() => $router);

// Auto-register configured services from config/services.php
$app->registerConfiguredServices();

// Load application routes: include all PHP files in the /routes directory
$routesDir = __DIR__ . '/../routes';
if (is_dir($routesDir)) {
    $files = glob($routesDir . '/*.php');
    sort($files, SORT_STRING);
    foreach ($files as $file) {
        (static function (\Nexus\Routing\Router $router) use ($file) {
            require $file;
        })($router);
    }
}

return $app;