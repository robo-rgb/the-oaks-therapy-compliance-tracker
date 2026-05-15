<?php

declare(strict_types=1);

require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';

start_secure_session();

$auth = new App\Auth\AuthService();

if ($auth->check()) {
    redirect('dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
        $error = 'Invalid request token.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($auth->attemptLogin($email, $password)) {
            redirect('dashboard.php');
        }

        $error = 'Invalid credentials.';
    }
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Login - The Oaks Compliance Tracker</title></head>
<body>
<h1>Login</h1>
<?php if ($error): ?>
<p style="color:red;"><?= e($error) ?></p>
<?php endif; ?>
<form method="post" action="<?= e(app_base_path('login.php')) ?>">
    <?= csrf_input() ?>
    <label>Email <input type="email" name="email" required></label><br>
    <label>Password <input type="password" name="password" required></label><br>
    <button type="submit">Sign In</button>
</form>
</body>
</html>
