<?php
declare(strict_types=1);

namespace Nexus\View;

/**
 * Isolated Output Buffering Native Rendering Engine
 */
class Engine
{
    public function render(string $path, array $data = []): string
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("View file not found: [{$path}]");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include $path;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }
}
