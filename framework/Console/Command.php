<?php
declare(strict_types=1);

namespace Nexus\Console;

use Nexus\Foundation\Application;

/**
 * Abstract Console Command Base Class
 * ANSI terminal output formatting and argument processing.
 */
abstract class Command
{
    protected string $name = '';
    protected string $description = '';

    public function __construct(protected Application $app) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    abstract public function execute(array $args): int;

    protected function info(string $message): void
    {
        echo "\033[36m" . $message . "\033[0m\n";
    }

    protected function success(string $message): void
    {
        echo "\033[32m" . $message . "\033[0m\n";
    }

    protected function warning(string $message): void
    {
        echo "\033[33m" . $message . "\033[0m\n";
    }

    protected function error(string $message): void
    {
        echo "\033[31m" . $message . "\033[0m\n";
    }

    protected function line(string $message = ''): void
    {
        echo $message . "\n";
    }
}
