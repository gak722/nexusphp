<?php
declare(strict_types=1);

namespace Nexus\Events;

use Nexus\Foundation\Application;

/**
 * Event Pub/Sub Dispatcher
 */
class Dispatcher
{
    protected array $listeners = [];

    public function __construct(protected ?Application $app = null) {}

    public function listen(string $eventClass, callable|string|object $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    public function dispatch(object $event): void
    {
        $eventClass = get_class($event);
        if (!isset($this->listeners[$eventClass])) {
            return;
        }

        foreach ($this->listeners[$eventClass] as $listener) {
            try {
                if (is_string($listener) && class_exists($listener)) {
                    $instance = $this->app ? $this->app->make($listener) : new $listener();
                    if ($instance instanceof ListenerInterface) {
                        $instance->handle($event);
                    }
                } elseif ($listener instanceof ListenerInterface) {
                    $listener->handle($event);
                } elseif (is_callable($listener)) {
                    $listener($event);
                }
            } catch (\Throwable $e) {
                $this->logException($event, $listener, $e);
            }
        }
    }

    protected function logException(object $event, mixed $listener, \Throwable $e): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $listenerName = is_string($listener) ? $listener : (is_object($listener) ? get_class($listener) : 'Closure');

        @file_put_contents(
            $logDir . '/events.log',
            sprintf(
                "[%s] Listener [%s] failed on event [%s]: %s in %s:%d\nStack trace:\n%s\n\n",
                date('Y-m-d H:i:s'),
                $listenerName,
                get_class($event),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            ),
            FILE_APPEND | LOCK_EX
        );
    }
}
