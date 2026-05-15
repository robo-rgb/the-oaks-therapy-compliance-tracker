<?php

declare(strict_types=1);

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        static $config = null;

        if ($config === null) {
            $config = require __DIR__ . '/../config/config.php';
        }

        if ($key === null) {
            return $config;
        }

        $segments = explode('.', $key);
        $value = $config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('app_base_path')) {
    function app_base_path(string $path = ''): string
    {
        $base = rtrim((string) config('app.base_path', '/license'), '/');

        if ($path === '') {
            return $base;
        }

        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . app_base_path($path));
        exit;
    }
}

if (!function_exists('start_secure_session')) {
    function start_secure_session(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name((string) config('app.session_name', 'oaks_compliance_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        start_secure_session();

        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_input')) {
    function csrf_input(): string
    {
        $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(?string $token): bool
    {
        start_secure_session();

        if (!is_string($token) || empty($_SESSION['_csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['_csrf_token'], $token);
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
