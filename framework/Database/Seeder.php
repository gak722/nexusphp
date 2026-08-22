<?php
declare(strict_types=1);

namespace Nexus\Database;

/**
 * Seeder Abstract Base
 */
abstract class Seeder
{
    abstract public function run(): void;
}
