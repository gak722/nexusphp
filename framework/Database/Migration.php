<?php
declare(strict_types=1);

namespace Nexus\Database;

/**
 * Migration Abstract Contract
 */
abstract class Migration
{
    abstract public function up(): void;
    abstract public function down(): void;
}
