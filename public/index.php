<?php
declare(strict_types=1);

/**
 * NexusPHP Front Controller Entrypoint
 */

$app = require __DIR__ . '/../bootstrap/app.php';

// Verification check for Phase 0
if (php_sapi_name() === 'cli' || defined('STDIN')) {
    echo "NexusPHP Phase 0 Foundation initialized successfully.\n";
}
