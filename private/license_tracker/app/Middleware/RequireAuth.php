<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\AuthService;

final class RequireAuth
{
    public static function enforce(): void
    {
        $auth = new AuthService();
        if (!$auth->check()) {
            redirect('login.php');
        }
    }
}
