<?php
declare(strict_types=1);

namespace Nexus\Queue;

/**
 * Abstract Background Job Base Class
 */
abstract class Job
{
    public mixed $id = null;
    public int $attempts = 0;
    public int $maxTries = 3;

    abstract public function handle(): void;
}
