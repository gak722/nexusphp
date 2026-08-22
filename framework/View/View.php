<?php
declare(strict_types=1);

namespace Nexus\View;

/**
 * Layout & Section Aware View Object
 */
class View
{
    protected ?string $layout = null;
    protected array $sections = [];
    protected ?string $currentSection = null;

    public function __construct(
        protected Engine $engine,
        protected string $path,
        protected array $data = []
    ) {}

    public function layout(string $layoutName): static
    {
        $this->layout = $layoutName;
        return $this;
    }

    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->currentSection === null) {
            throw new \LogicException("Cannot end a section without starting one.");
        }
        $this->sections[$this->currentSection] = (string) ob_get_clean();
        $this->currentSection = null;
    }

    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function render(): string
    {
        $content = $this->engine->render($this->path, array_merge($this->data, ['view' => $this]));

        if ($this->layout !== null) {
            $baseDir = dirname($this->path);
            while (!str_ends_with($baseDir, 'views') && strlen($baseDir) > 1) {
                $baseDir = dirname($baseDir);
            }

            $layoutPath = $baseDir . '/' . str_replace('.', '/', $this->layout) . '.php';
            $layoutData = array_merge($this->data, [
                'content' => $content,
                'sections' => $this->sections,
                'view' => $this
            ]);
            return $this->engine->render($layoutPath, $layoutData);
        }

        return $content;
    }

    public function asset(string $path): string
    {
        $publicPath = dirname(__DIR__, 2) . '/public/' . ltrim($path, '/');
        if (file_exists($publicPath)) {
            $hash = substr(md5_file($publicPath) ?: '', 0, 8);
            return '/' . ltrim($path, '/') . '?v=' . $hash;
        }
        return '/' . ltrim($path, '/');
    }
}
