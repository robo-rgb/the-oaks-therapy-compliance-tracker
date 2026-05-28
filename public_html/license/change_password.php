<?php
declare(strict_types=1);

require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';

App\Middleware\RequireAuth::enforce();
start_secure_session();

$userId = (int) ($_SESSION['user_id'] ?? 0);

$errors = [];
$message = '';

if ($userId < 1) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
        $errors[] = 'Invalid request token.';
    } else {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($currentPassword === '') {
            $errors[] = 'Current password is required.';
        }

        if (strlen($newPassword) < 12) {
            $errors[] = 'New password must be at least 12 characters.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation do not match.';
        }

        if ($currentPassword !== '' && $newPassword !== '' && hash_equals($currentPassword, $newPassword)) {
            $errors[] = 'New password must be different from the current password.';
        }

        if (!$errors) {
            $pdo = App\Database\Connection::get();

            $stmt = $pdo->prepare(
                'SELECT id, password_hash
                 FROM users
                 WHERE id = :id
                   AND is_active = 1
                 LIMIT 1'
            );

            $stmt->execute([
                'id' => $userId,
            ]);

            $user = $stmt->fetch();

            if (!$user || !password_verify($currentPassword, (string) $user['password_hash'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

                $update = $pdo->prepare(
                    'UPDATE users
                     SET password_hash = :password_hash,
                         updated_at = :updated_at
                     WHERE id = :id'
                );

                $update->execute([
                    'password_hash' => $newHash,
                    'updated_at' => date('c'),
                    'id' => $userId,
                ]);

                $message = 'Password changed successfully.';
            }
        }
    }
}
?>
<!doctype html>
<html>
<body>
<?php require __DIR__ . '/_auth_nav.php'; ?>

<h1>Change Password</h1>

<?php if ($message !== ''): ?>
    <p style="color:green"><?= e($message) ?></p>
<?php endif; ?>

<?php foreach ($errors as $error): ?>
    <p style="color:red"><?= e($error) ?></p>
<?php endforeach; ?>

<form method="post" action="<?= e(app_base_path('change_password.php')) ?>">
    <?= csrf_input() ?>

    <p>
        <label>
            Current password<br>
            <input
                type="password"
                name="current_password"
                autocomplete="current-password"
                required
            >
        </label>
    </p>

    <p>
        <label>
            New password<br>
            <input
                type="password"
                name="new_password"
                autocomplete="new-password"
                minlength="12"
                required
            >
        </label>
    </p>

    <p>
        <label>
            Confirm new password<br>
            <input
                type="password"
                name="confirm_password"
                autocomplete="new-password"
                minlength="12"
                required
            >
        </label>
    </p>

    <p>
        <button type="submit">Change Password</button>
    </p>
</form>

</body>
</html>