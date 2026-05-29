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
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Change Password - The Oaks Therapy</title>
</head>
<body>
<?php require __DIR__ . '/_auth_nav.php'; ?>

<main id="main-content" class="page-shell page-shell--narrow">
    <div class="page-header">
        <div class="page-header__content">
            <p class="page-eyebrow">Account security</p>
            <h1>Change Password</h1>
            <p class="page-subtitle">Update the password used to access the compliance tracker.</p>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <p class="notice"><?= e($message) ?></p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <p class="error"><?= e($error) ?></p>
    <?php endforeach; ?>

    <section class="panel">
        <div class="card__header">
            <div>
                <h2 class="card__title">Password details</h2>
                <p class="card__subtitle">Use a strong password with at least 12 characters.</p>
            </div>
        </div>

        <form method="post" action="<?= e(app_base_path('change_password.php')) ?>">
            <?= csrf_input() ?>

            <div class="grid">
                <div class="field full">
                    <label for="current_password">Current password</label>
                    <input
                        id="current_password"
                        type="password"
                        name="current_password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="field">
                    <label for="new_password">New password</label>
                    <input
                        id="new_password"
                        type="password"
                        name="new_password"
                        autocomplete="new-password"
                        minlength="12"
                        required
                    >
                </div>

                <div class="field">
                    <label for="confirm_password">Confirm new password</label>
                    <input
                        id="confirm_password"
                        type="password"
                        name="confirm_password"
                        autocomplete="new-password"
                        minlength="12"
                        required
                    >
                </div>
            </div>

            <div class="button-row">
                <button type="submit">Change Password</button>
                <a class="button button--secondary" href="<?= e(app_base_path('settings.php')) ?>">Back to Settings</a>
            </div>
        </form>
    </section>
</main>

</body>
</html>
