<?php
declare(strict_types=1);

class InodeBudgetTest
{
    public function testInodeBudgetLimit(): void
    {
        $rootDir = realpath(__DIR__ . '/../../');
        $command = "find " . escapeshellarg($rootDir) . " -not -path '*/.*' | wc -l";
        $count = (int) trim((string) shell_exec($command));

        if ($count > 2000) {
            throw new \RuntimeException("Inode budget exceeded! Found {$count} nodes (hard limit 2000).");
        }

        if ($count === 0) {
            throw new \RuntimeException("Inode count check returned zero.");
        }
    }
}
