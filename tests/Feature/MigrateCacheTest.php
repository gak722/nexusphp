<?php
declare(strict_types=1);

namespace Nexus\Tests\Feature;

use PHPUnit\Framework\TestCase;

class MigrateCacheTest extends TestCase
{
    public function testFileCacheMigrationDryRunAndApply(): void
    {
        $tmp = sys_get_temp_dir() . '/nexus_cache_migrate_test_' . uniqid();
        @mkdir($tmp, 0755, true);

        $file = $tmp . '/legacy.cache';
        $payload = serialize(['expires_at' => 0, 'value' => ['secret' => 's']]);
        file_put_contents($file, $payload, LOCK_EX);

        // include migration functions
        require_once __DIR__ . '/../../scripts/migrate_cache_to_json.php';

        $reportDry = migrateFileCache($tmp, false);
        $this->assertSame(1, $reportDry['scanned']);
        $this->assertSame(1, $reportDry['legacy']);

        // ensure apply requires env var
        putenv('MIGRATE_CACHE_ALLOW=');
        $reportFail = migrateFileCache($tmp, true);
        $this->assertStringContainsString('MIGRATE_CACHE_ALLOW', implode(' ', $reportFail['errors']));

        // permit apply
        putenv('MIGRATE_CACHE_ALLOW=true');
        $report = migrateFileCache($tmp, true);
        $this->assertSame(1, $report['converted']);

        $newContent = file_get_contents($file);
        $decoded = json_decode($newContent, true);
        $this->assertIsArray($decoded);
        $this->assertSame(1, $decoded['v']);
        $this->assertSame(['secret' => 's'], $decoded['value']);

        $this->assertFileExists($file . '.bak');

        // cleanup
        @unlink($file);
        @unlink($file . '.bak');
        @rmdir($tmp);
    }
}
