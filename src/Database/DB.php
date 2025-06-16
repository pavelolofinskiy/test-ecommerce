<?php
namespace Src\Database;

use PDO;

class DB {
    private static $pdo;

    public static function connect(): PDO {
        if (!self::$pdo) {
            $config = require __DIR__ . '/../../config/db.php';
            self::$pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8", $config['user'], $config['pass']);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$pdo;
    }
}