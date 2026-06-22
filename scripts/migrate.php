#!/usr/bin/env php
<?php

$root = dirname(__DIR__);
chdir($root);

require_once $root . '/api/vendor/epiphany/src/Epi.php';
Epi::setPath('base', $root . '/api/vendor/epiphany/src');
Epi::init('database');

require_once $root . '/api/vendor/composer/autoload.php';
require_once $root . '/api/lib/DatabaseFactory.php';
require_once $root . '/api/lib/Migrator.php';

$command = $argv[1] ?? 'apply';

try {
    $migrator = new Migrator();

    if ($command === 'status') {
        $status = $migrator->status();
        echo "Driver: {$status['driver']}\n\n";
        echo "Applied:\n";
        foreach ($status['applied'] as $v) {
            echo "  ✓ {$v}\n";
        }
        echo "\nPending:\n";
        if (empty($status['pending'])) {
            echo "  (none)\n";
        } else {
            foreach ($status['pending'] as $v) {
                echo "  ○ {$v}\n";
            }
        }
        exit(empty($status['pending']) ? 0 : 1);
    }

    if ($command === 'apply') {
        $count = $migrator->apply();
        echo $count > 0 ? "Applied {$count} migration(s).\n" : "No pending migrations.\n";
        exit(0);
    }

    fwrite(STDERR, "Usage: php scripts/migrate.php [apply|status]\n");
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: {$e->getMessage()}\n");
    exit(1);
}
