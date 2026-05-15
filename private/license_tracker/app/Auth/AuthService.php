<?php

declare(strict_types=1);

namespace App\Auth;

use App\Database\Connection;
use PDO;

final class AuthService
{
    public function attemptLogin(string $email, string $password): bool
    {
        $pdo = Connection::get();
        $stmt = $pdo->prepare('SELECT id, password_hash, is_active FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email))]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || (int) $user['is_active'] !== 1) {
            return false;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        start_secure_session();
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];

        return true;
    }

    public function logout(): void
    {
        start_secure_session();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }

    public function check(): bool
    {
        start_secure_session();
        return isset($_SESSION['user_id']) && is_int($_SESSION['user_id']);
    }
}
