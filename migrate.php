<?php
$config = require __DIR__ . '/config/db.php';

$pdo = new PDO(
    "mysql:host={$config['host']};charset=utf8mb4",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$migrationsDir = __DIR__ . '/migrations';
$migrationFiles = glob($migrationsDir . '/*.php');

foreach ($migrationFiles as $file) {
    require $file;
    echo "✅ Applied: " . basename($file) . PHP_EOL;
}