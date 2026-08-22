<?php
declare(strict_types=1);

namespace Nexus\Foundation;

/**
 * Main Application container and framework bootstrap entry.
 */
class Application extends Container
{
    protected static ?Application $instance = null;

    protected string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ? rtrim($basePath, '\/') : dirname(__DIR__, 2);
        static::$instance = $this;
        $this->instance(Container::class, $this);
        $this->instance(Application::class, $this);
        $this->registerCoreBindings();
    }

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '\/') : '');
    }

    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '\/') : ''));
    }

    public function configPath(string $path = ''): string
    {
        return $this->basePath('config' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '\/') : ''));
    }

    public function publicPath(string $path = ''): string
    {
        return $this->basePath('public' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '\/') : ''));
    }

    public function environment(): string
    {
        return $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'production';
    }

    /**
     * Register dependency injection services configured in `config/services.php`.
     */
    public function registerConfiguredServices(): void
    {
        try {
            $config = $this->make(Config::class);
            $servicesConfig = $config->get('services', []);

            if (!is_array($servicesConfig)) {
                return;
            }

            // 1. Singletons
            foreach ((array) ($servicesConfig['singletons'] ?? []) as $abstract => $concrete) {
                if (is_int($abstract)) {
                    $this->addSingleton($concrete);
                } else {
                    $this->addSingleton((string) $abstract, $concrete);
                }
            }

            // 2. Transients
            foreach ((array) ($servicesConfig['transients'] ?? []) as $abstract => $concrete) {
                if (is_int($abstract)) {
                    $this->addTransient($concrete);
                } else {
                    $this->addTransient((string) $abstract, $concrete);
                }
            }

            // 3. Scoped
            foreach ((array) ($servicesConfig['scoped'] ?? []) as $abstract => $concrete) {
                if (is_int($abstract)) {
                    $this->addScoped($concrete);
                } else {
                    $this->addScoped((string) $abstract, $concrete);
                }
            }

            // 4. Callback / Closure registration
            if (isset($servicesConfig['register']) && is_callable($servicesConfig['register'])) {
                $servicesConfig['register']($this);
            }
        } catch (\Throwable $e) {
            // Silently ignore or fallback gracefully during bootstrap
        }
    }

    protected function registerCoreBindings(): void
    {
        // Register core bindings
        $this->singleton(Config::class, fn () => new Config());
    }
}
