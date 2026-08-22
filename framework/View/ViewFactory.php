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
        $relativePath = str_replace('.', '/', $name) . '.php';
        $fullPath = $this->basePath . '/' . $relativePath;

        return new View($this->engine, $fullPath, $data);
    }
}
