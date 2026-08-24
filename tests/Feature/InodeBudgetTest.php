<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class InodeBudgetTest extends TestCase
{
    public function testInodeBudgetLimit(): void
    {
        $rootDir = realpath(__DIR__ . '/../../');
        $command = "find " . escapeshellarg($rootDir) . " -not -path '*/.*' -not -path '*/vendor/*' -not -path '*/storage/*' | wc -l";
        $count = (int) trim((string) shell_exec($command));

        if ($count > 2000) {
            throw new \RuntimeException("Inode budget exceeded for framework code! Found {$count} nodes (hard limit 2000).");
        }

        if ($count === 0) {
            throw new \RuntimeException("Inode count check returned zero.");
        }
    }
}
