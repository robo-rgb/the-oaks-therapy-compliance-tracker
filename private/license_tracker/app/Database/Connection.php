<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

final class Connection
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $databasePath = (string) config('database.path');

        $directory = dirname($databasePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0750, true);
        }

        self::$pdo = new PDO('sqlite:' . $databasePath);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$pdo->exec('PRAGMA foreign_keys = ON');

        return self::$pdo;
    }
}
