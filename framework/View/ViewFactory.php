<?php
declare(strict_types=1);

namespace Nexus\View;

/**
 * View Instance Resolver Factory
 */
class ViewFactory
{
    protected Engine $engine;
    protected string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->engine = new Engine();
        if ($basePath === null) {
            $app = \Nexus\Foundation\Application::getInstance();
            $basePath = $app->basePath('resources/views');
        }
        $this->basePath = rtrim($basePath, '/\\');
    }

    public function make(string $name, array $data = []): View
    {
        // Strip path traversal characters
        $cleanName = str_replace(['..', "\0"], '', $name);
        $relativePath = str_replace('.', '/', $cleanName) . '.php';
        $fullPath = $this->basePath . '/' . $relativePath;

        $realBase = realpath($this->basePath) ?: $this->basePath;
        $realFull = realpath($fullPath);

        if ($realFull !== false && !str_starts_with($realFull, $realBase)) {
            throw new \InvalidArgumentException("Unauthorized view path traversal attempted: [{$name}]");
        }

        return new View($this->engine, $fullPath, $data);
    }
}
