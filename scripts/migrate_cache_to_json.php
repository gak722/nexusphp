<?php
declare(strict_types=1);

/**
 * Safe cache migration helper: migrate PHP-serialized file cache entries
 * to the JSON envelope format used by the new cache drivers.
 *
 * Usage:
 *   php scripts/migrate_cache_to_json.php --path=storage/framework/cache --apply
 *
 * To prevent accidental runs, set environment variable MIGRATE_CACHE_ALLOW=true
 * before using --apply.
 */

function migrateFileCache(string $dir, bool $apply = false, array $opts = []): array
{
    $report = ['scanned' => 0, 'legacy' => 0, 'converted' => 0, 'skipped' => 0, 'errors' => []];
    if (!is_dir($dir)) {
        $report['errors'][] = "Directory not found: {$dir}";
        return $report;
    }

    $files = glob(rtrim($dir, '/') . '/*.cache');
    foreach ($files as $file) {
        $report['scanned']++;
        $content = @file_get_contents($file);
        if ($content === false) {
            $report['errors'][] = "Could not read {$file}";
            continue;
        }

        $decoded = @json_decode($content, true);
        if (is_array($decoded) && isset($decoded['v'])) {
            $report['skipped']++;
            continue;
        }

        // Attempt safe unserialize
        $legacy = @unserialize($content, ['allowed_classes' => false]);
        if (!is_array($legacy) || !array_key_exists('value', $legacy) || !array_key_exists('expires_at', $legacy)) {
            $report['errors'][] = "Unrecognized format: {$file}";
            continue;
        }

        $report['legacy']++;
        $envelope = ['v' => 1, 'value' => $legacy['value'], 'type' => gettype($legacy['value']), 'expires_at' => (int)$legacy['expires_at']];

        if ($apply) {
            // Safety guard
            if (getenv('MIGRATE_CACHE_ALLOW') !== 'true') {
                $report['errors'][] = "MIGRATE_CACHE_ALLOW not set to true; aborting apply";
                return $report;
            }

            $bak = $file . '.bak';
            if (@file_exists($bak) === false) {
                @copy($file, $bak);
            }
            try {
                $payload = json_encode($envelope, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $report['errors'][] = "JSON encode failed for {$file}: " . $e->getMessage();
                continue;
            }
            if (@file_put_contents($file, $payload, LOCK_EX) === false) {
                $report['errors'][] = "Failed to write converted file {$file}";
                continue;
            }
            $report['converted']++;
        }
    }

    return $report;
}

// CLI handling
if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0] ?? '')) {
    $opts = getopt('', ['path::', 'apply', 'help']);
    $path = $opts['path'] ?? __DIR__ . '/../storage/framework/cache';
    $apply = isset($opts['apply']);

    if (isset($opts['help'])) {
        echo file_get_contents(__FILE__);
        exit(0);
    }

    echo "Scanning cache files in: {$path}\n";
    if ($apply) {
        echo "Apply mode enabled. Ensure MIGRATE_CACHE_ALLOW=true is set in environment.\n";
    } else {
        echo "Dry-run mode. No files will be modified. Use --apply to convert.\n";
    }

    $res = migrateFileCache($path, $apply);
    echo "Scanned: {$res['scanned']}, legacy: {$res['legacy']}, converted: {$res['converted']}, skipped: {$res['skipped']}\n";
    if (!empty($res['errors'])) {
        echo "Errors:\n" . implode("\n", $res['errors']) . "\n";
    }
}

return null;
