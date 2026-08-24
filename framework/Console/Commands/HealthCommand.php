<?php
declare(strict_types=1);

namespace Nexus\Console\Commands;

use Nexus\Console\Command;
use Nexus\Health\HealthCheck;

class HealthCommand extends Command
{
    protected string $name = 'health';
    protected string $description = 'Check application health and system status';

    public function execute(array $args): int
    {
        $result = HealthCheck::check();

        if ($result['status'] === 'healthy') {
            $this->success("System Status: HEALTHY (" . $result['timestamp'] . ")");
        } else {
            $this->error("System Status: UNHEALTHY (" . $result['timestamp'] . ")");
        }

        $this->line();
        foreach ($result['checks'] as $component => $info) {
            $statusStr = strtoupper($info['status'] ?? 'OK');
            $msg = $info['message'] ?? ("Memory usage: " . ($info['usage_mb'] ?? 0) . "MB");
            
            if ($statusStr === 'OK') {
                $this->info("  [✔] " . str_pad($component, 12) . " : " . $msg);
            } else {
                $this->error("  [✖] " . str_pad($component, 12) . " : " . $msg);
            }
        }

        return $result['status'] === 'healthy' ? 0 : 1;
    }
}
