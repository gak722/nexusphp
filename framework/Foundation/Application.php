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

    protected function registerCoreBindings(): void
    {
        // Register core bindings
    }
}
