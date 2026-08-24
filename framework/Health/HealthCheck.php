<?php
declare(strict_types=1);

namespace Nexus\Health;

use Nexus\Database\Connection;
use Nexus\Foundation\Application;

class HealthCheck
{
    public static function check(): array
    {
        $status = 'healthy';
        $checks = [];

        // 1. Storage writable check
        $storagePath = Application::getInstance()->storagePath('app');
        $isWritable = is_dir($storagePath) && is_writable($storagePath);
        $checks['storage'] = [
            'status' => $isWritable ? 'ok' : 'error',
            'message' => $isWritable ? 'Storage directory is writable' : 'Storage directory is not writable',
        ];

        // 2. Database connection check
        try {
            /** @var Connection $db */
            $db = app(Connection::class);
            $db->statement('SELECT 1');
            $checks['database'] = [
                'status' => 'ok',
                'message' => 'Database connection successful',
            ];
        } catch (\Throwable $e) {
            $checks['database'] = [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
            $status = 'unhealthy';
        }

        // 3. System Memory Usage
        $checks['memory'] = [
            'status' => 'ok',
            'usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];

        if (array_key_exists('status', $checks['storage']) && $checks['storage']['status'] !== 'ok') {
            $status = 'unhealthy';
        }

        return [
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s'),
            'checks' => $checks,
        ];
    }
}
