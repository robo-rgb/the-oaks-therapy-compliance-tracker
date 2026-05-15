<?php

declare(strict_types=1);

require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';

start_secure_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['_csrf_token'] ?? null)) {
    http_response_code(405);
    exit('Invalid request.');
}

$auth = new App\Auth\AuthService();
$auth->logout();

redirect('login.php');
