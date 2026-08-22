<?php
declare(strict_types=1);

namespace Nexus\View;

/**
 * Reusable View Component Contract
 */
abstract class Component
{
    abstract public function render(): string;

    public function __toString(): string
    {
        return $this->render();
    }
}
