<?php
declare(strict_types=1);

namespace Nexus\Support;

use Nexus\Log\Logger;
use Nexus\Foundation\Application;

/**
 * Static accessor for the application logger.
 */
class Log
{
    protected static ?Logger $logger = null;

    protected static function getLogger(): Logger
    {
        if (static::$logger === null) {
            $app = Application::getInstance();
            if ($app->has(Logger::class)) {
                static::$logger = $app->make(Logger::class);
            } else {
                $path = $app->storagePath('logs/app.log');
                static::$logger = new Logger($path);
                $app->instance(Logger::class, static::$logger);
            }
        }
        return static::$logger;
    }

    public static function emergency(string $message, array $context = []): void
    {
        static::getLogger()->emergency($message, $context);
    }

    public static function alert(string $message, array $context = []): void
    {
        static::getLogger()->alert($message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        static::getLogger()->critical($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        static::getLogger()->error($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        static::getLogger()->warning($message, $context);
    }

    public static function notice(string $message, array $context = []): void
    {
        static::getLogger()->notice($message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        static::getLogger()->info($message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        static::getLogger()->debug($message, $context);
    }
}
