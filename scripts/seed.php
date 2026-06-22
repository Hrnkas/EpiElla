#!/usr/bin/env php
<?php

$root = dirname(__DIR__);
chdir($root);

require_once $root . '/api/vendor/epiphany/src/Epi.php';
Epi::setPath('base', $root . '/api/vendor/epiphany/src');
Epi::init('database');

require_once $root . '/api/vendor/composer/autoload.php';
require_once $root . '/api/lib/DatabaseFactory.php';

DatabaseFactory::loadEnv();
DatabaseFactory::connectFromEnv();

$email = $_ENV['ADMIN_EMAIL'] ?? 'admin@localhost';
$password = $_ENV['ADMIN_PASSWORD'] ?? 'changeme';
$hash = password_hash($password, PASSWORD_DEFAULT);

$db = DatabaseFactory::getDb();
$driver = DatabaseFactory::driver();

if ($driver === 'sqlite') {
    $existing = $db->one('SELECT id FROM users WHERE email = :email', [':email' => $email]);
    if ($existing) {
        echo "Admin user already exists: {$email}\n";
        exit(0);
    }
    $db->execute(
        'INSERT INTO users (email, password_hash) VALUES (:email, :hash)',
        [':email' => $email, ':hash' => $hash]
    );
} else {
    $db->execute(
        'INSERT IGNORE INTO users (email, password_hash) VALUES (:email, :hash)',
        [':email' => $email, ':hash' => $hash]
    );
}

echo "Seeded admin user: {$email}\n";
