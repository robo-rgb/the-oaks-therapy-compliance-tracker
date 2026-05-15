<?php

declare(strict_types=1);

namespace App\Database;

use PDOException;

final class Migrator
{
    public function run(): void
    {
        $pdo = Connection::get();
        $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration TEXT NOT NULL UNIQUE,
            applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        $migrationDir = __DIR__ . '/../../database/migrations';
        $files = glob($migrationDir . '/*.php') ?: [];
        sort($files);

        foreach ($files as $file) {
            $name = basename($file);

            $check = $pdo->prepare('SELECT id FROM migrations WHERE migration = :migration LIMIT 1');
            $check->execute(['migration' => $name]);
            if ($check->fetch()) {
                continue;
            }

            $queries = require $file;

            try {
                $pdo->beginTransaction();
                foreach ($queries as $query) {
                    try {
                        $pdo->exec($query);
                    } catch (\PDOException $exception) {
                        if (!str_contains(strtolower($exception->getMessage()), "duplicate column name")) {
                            throw $exception;
                        }
                    }
                }
                $insert = $pdo->prepare('INSERT INTO migrations (migration) VALUES (:migration)');
                $insert->execute(['migration' => $name]);
                $pdo->commit();
            } catch (\Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $exception;
            }

            echo "Applied migration: {$name}\n";
        }
    }
}
