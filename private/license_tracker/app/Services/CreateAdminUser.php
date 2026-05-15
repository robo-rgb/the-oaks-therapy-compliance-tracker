<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;

final class CreateAdminUser
{
    public function run(string $email, string $fullName, string $password): void
    {
        $email = strtolower(trim($email));

        $pdo = Connection::get();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            throw new \RuntimeException('Admin user already exists for that email.');
        }

        $insert = $pdo->prepare(
            'INSERT INTO users (email, full_name, password_hash, role, is_active) VALUES (:email, :full_name, :password_hash, :role, :is_active)'
        );
        $insert->execute([
            'email' => $email,
            'full_name' => $fullName,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'admin',
            'is_active' => 1,
        ]);
    }
}
