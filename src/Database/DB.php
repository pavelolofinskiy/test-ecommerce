<?php

namespace Src\Database;

use PDO;

/**
 * Класс для подключения к базе данных с использованием PDO.
 */
class DB
{
    /**
     * Экземпляр PDO.
     *
     * @var PDO|null
     */
    private static ?PDO $pdo = null;

    /**
     * Создает и возвращает PDO подключение к базе данных.
     * Использует паттерн Singleton, чтобы создать подключение один раз.
     *
     * @return PDO
     * @throws \PDOException В случае ошибки подключения.
     */
    public static function connect(): PDO
    {
        if (self::$pdo === null) {
            $config = require __DIR__ . '/../../config/db.php';

            self::$pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
                $config['user'],
                $config['pass']
            );
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return self::$pdo;
    }
}