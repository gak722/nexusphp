<?php
declare(strict_types=1);

namespace Nexus\Events;

/**
 * Event Listener Interface Contract
 */
interface ListenerInterface
{
    public function handle(object $event): void;
}
