<?php

declare(strict_types=1);

require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';

App\Middleware\RequireAuth::enforce();
start_secure_session();

$svc = new App\Services\ReminderService();
$emailSvc = new App\Services\EmailService();

$settings = $svc->getSettings();
$msg = '';
$err = '';

function save_app_setting(PDO $pdo, string $key, string $value): void
{
    $st = $pdo->prepare(
        'INSERT INTO app_settings (setting_key, setting_value)
         VALUES (:k, :v)
         ON CONFLICT(setting_key) DO UPDATE SET setting_value = :v'
    );

    $st->execute([
        'k' => $key,
        'v' => $value,
    ]);
}

function encrypt_secret_for_storage(string $plain): string
{
    $keySource = (string)config('app.encryption_key', '');

    if ($keySource === '') {
        throw new RuntimeException('Missing app.encryption_key in local.php.');
    }

    $iv = random_bytes(16);
    $key = hash('sha256', $keySource, true);
    $cipherText = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

    if ($cipherText === false) {
        throw new RuntimeException('Unable to encrypt SMTP password.');
    }

    return base64_encode($iv . $cipherText);
}

function current_setting(array $settings, string $key, string $fallback = ''): string
{
    $value = trim((string)($settings[$key] ?? ''));

    return $value !== '' ? $value : $fallback;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
        $err = 'Invalid request token.';
    } else {
        if (isset($_POST['send_test_email'])) {
            $settings = $svc->getSettings();

            $to = trim((string)($settings['admin_recipient_email'] ?? ''));

            if ($to === '') {
                $to = trim((string)($settings['licensee_recipient_email'] ?? ''));
            }

            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $err = 'No valid recipient configured.';
            } else {
                $res = $emailSvc->sendTest($to);

                if ($res['ok']) {
                    $msg = 'Test email sent.';
                } else {
                    $err = $res['error'] ?: 'Test email failed.';
                }

                $svc->log(
                    'settings_test_email',
                    'test_email',
                    0,
                    0,
                    $to,
                    'SMTP Test Email',
                    $res['ok'] ? 'sent' : 'failed',
                    $res['error'],
                    null
                );
            }
        } else {
            $data = [
                'app_name' => trim((string)($_POST['app_name'] ?? '')),
                'business_name' => trim((string)($_POST['business_name'] ?? '')),
                'admin_recipient_email' => trim((string)($_POST['admin_recipient_email'] ?? '')),
                'licensee_recipient_email' => trim((string)($_POST['licensee_recipient_email'] ?? '')),
                'reminder_schedule_enabled' => isset($_POST['reminder_schedule_enabled']) ? '1' : '0',
                'monthly_summary_enabled' => isset($_POST['monthly_summary_enabled']) ? '1' : '0',
                'reminder_days_before_deadline' => trim((string)($_POST['reminder_days_before_deadline'] ?? '')),

                'smtp_host' => trim((string)($_POST['smtp_host'] ?? '')),
                'smtp_port' => trim((string)($_POST['smtp_port'] ?? '')),
                'smtp_encryption' => strtolower(trim((string)($_POST['smtp_encryption'] ?? 'tls'))),
                'smtp_username' => trim((string)($_POST['smtp_username'] ?? '')),
                'smtp_from_name' => trim((string)($_POST['smtp_from_name'] ?? '')),
                'smtp_from_email' => trim((string)($_POST['smtp_from_email'] ?? '')),
                'smtp_reply_to_email' => trim((string)($_POST['smtp_reply_to_email'] ?? '')),
            ];

            // Compatibility with any older code still checking sender_email.
            $data['sender_email'] = $data['smtp_from_email'];

            if ($data['admin_recipient_email'] !== '' && !filter_var($data['admin_recipient_email'], FILTER_VALIDATE_EMAIL)) {
                $err = 'Admin recipient email invalid.';
            }

            if ($data['licensee_recipient_email'] !== '' && !filter_var($data['licensee_recipient_email'], FILTER_VALIDATE_EMAIL)) {
                $err = 'Licensee recipient email invalid.';
            }

            if ($data['smtp_from_email'] !== '' && !filter_var($data['smtp_from_email'], FILTER_VALIDATE_EMAIL)) {
                $err = 'From email invalid.';
            }

            if ($data['smtp_reply_to_email'] !== '' && !filter_var($data['smtp_reply_to_email'], FILTER_VALIDATE_EMAIL)) {
                $err = 'Reply-to email invalid.';
            }

            if ($data['smtp_username'] !== '' && !filter_var($data['smtp_username'], FILTER_VALIDATE_EMAIL)) {
                $err = 'SMTP username should be the full sending email address.';
            }

            if (!ctype_digit($data['smtp_port']) || (int)$data['smtp_port'] < 1 || (int)$data['smtp_port'] > 65535) {
                $err = 'SMTP port invalid.';
            }

            if (!in_array($data['smtp_encryption'], ['tls', 'ssl', 'none'], true)) {
                $err = 'SMTP encryption invalid.';
            }

            $parsedDays = $svc->parseReminderDays($data['reminder_days_before_deadline']);

            if ($parsedDays === []) {
                $err = 'Reminder days list invalid.';
            }

            $newPassword = trim((string)($_POST['smtp_password'] ?? ''));

            if (!$err) {
                try {
                    $pdo = App\Database\Connection::get();

                    foreach ($data as $k => $v) {
                        save_app_setting($pdo, $k, $v);
                    }

                    if ($newPassword !== '') {
                        save_app_setting($pdo, 'smtp_password_encrypted', encrypt_secret_for_storage($newPassword));
                    }

                    $settings = $svc->getSettings();
                    $msg = 'Settings saved.';
                } catch (Throwable $e) {
                    error_log('Settings save failed: ' . $e->getMessage());
                    $err = $e->getMessage();
                }
            }
        }
    }
}

$smtpHost = current_setting($settings, 'smtp_host', (string)config('smtp.host', 'smtp.gmail.com'));
$smtpPort = current_setting($settings, 'smtp_port', (string)config('smtp.port', '587'));
$smtpEncryption = current_setting($settings, 'smtp_encryption', (string)config('smtp.encryption', 'tls'));
$smtpUsername = current_setting($settings, 'smtp_username', (string)config('smtp.username', ''));
$smtpFromName = current_setting($settings, 'smtp_from_name', (string)config('smtp.from_name', 'The Oaks Therapy | Compliance Tracker'));
$smtpFromEmail = current_setting(
    $settings,
    'smtp_from_email',
    current_setting($settings, 'sender_email', (string)config('smtp.from_email', $smtpUsername))
);
$smtpReplyToEmail = current_setting($settings, 'smtp_reply_to_email', $smtpFromEmail);

$passwordSaved = trim((string)($settings['smtp_password_encrypted'] ?? '')) !== ''
    || trim((string)config('smtp.password', '')) !== '';

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Settings - The Oaks Therapy Compliance Tracker</title>
</head>
<body>
<?php require __DIR__ . '/_auth_nav.php'; ?>

<main id="main-content" class="page-shell page-shell--narrow">
    <header class="page-header">
        <div class="page-header__content">
            <p class="page-eyebrow">System configuration</p>
            <h1>Settings</h1>
            <p class="page-subtitle">Manage app labels, SMTP authentication, sender display fields, and reminder automation.</p>
        </div>
        <div class="page-header__actions">
            <a class="button button--secondary" href="<?= e(app_base_path('change_password.php')) ?>">Change password</a>
        </div>
    </header>

    <?php if ($msg): ?>
        <p class="notice"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if ($err): ?>
        <p class="error"><?= e($err) ?></p>
    <?php endif; ?>

    <section class="card">
        <div class="detail-grid">
            <div class="detail-item">
                <p class="detail-label">SMTP status</p>
                <p class="detail-value"><?= $emailSvc->smtpPresent() ? 'Configured' : 'Incomplete' ?></p>
            </div>
            <div class="detail-item">
                <p class="detail-label">Password storage</p>
                <p class="detail-value"><?= $passwordSaved ? 'Saved and hidden' : 'Not saved' ?></p>
            </div>
        </div>
    </section>

    <form method="post">
        <?= csrf_input() ?>

        <section class="panel">
            <div class="card__header">
                <div>
                    <h2 class="card__title">Application Settings</h2>
                    <p class="card__subtitle">Names and recipients used by the tracker.</p>
                </div>
            </div>

            <div class="grid">
                <div class="field">
                    <label for="app_name">App name</label>
                    <input id="app_name" name="app_name" value="<?= e((string)($settings['app_name'] ?? '')) ?>">
                </div>

                <div class="field">
                    <label for="business_name">Business name</label>
                    <input id="business_name" name="business_name" value="<?= e((string)($settings['business_name'] ?? '')) ?>">
                </div>

                <div class="field">
                    <label for="admin_recipient_email">Admin recipient email</label>
                    <input id="admin_recipient_email" name="admin_recipient_email" type="email" value="<?= e((string)($settings['admin_recipient_email'] ?? '')) ?>">
                </div>

                <div class="field">
                    <label for="licensee_recipient_email">Licensee recipient email</label>
                    <input id="licensee_recipient_email" name="licensee_recipient_email" type="email" value="<?= e((string)($settings['licensee_recipient_email'] ?? '')) ?>">
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="card__header">
                <div>
                    <h2 class="card__title">SMTP Authentication</h2>
                    <p class="card__subtitle">Credentials used to send reminder and test emails.</p>
                </div>
            </div>

            <div class="grid">
                <div class="field">
                    <label for="smtp_host">SMTP host</label>
                    <input id="smtp_host" name="smtp_host" value="<?= e($smtpHost) ?>" placeholder="smtp.gmail.com">
                </div>

                <div class="field">
                    <label for="smtp_port">SMTP port</label>
                    <input id="smtp_port" name="smtp_port" inputmode="numeric" value="<?= e($smtpPort) ?>" placeholder="587">
                </div>

                <div class="field">
                    <label for="smtp_encryption">Encryption</label>
                    <select id="smtp_encryption" name="smtp_encryption">
                        <option value="tls" <?= $smtpEncryption === 'tls' ? 'selected' : '' ?>>TLS</option>
                        <option value="ssl" <?= $smtpEncryption === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        <option value="none" <?= $smtpEncryption === 'none' ? 'selected' : '' ?>>None</option>
                    </select>
                </div>

                <div class="field">
                    <label for="smtp_username">SMTP username</label>
                    <input id="smtp_username" name="smtp_username" type="email" value="<?= e($smtpUsername) ?>" placeholder="maylin@theoakstherapy.com">
                </div>

                <div class="field full">
                    <label for="smtp_password">SMTP app password</label>
                    <input
                        id="smtp_password"
                        name="smtp_password"
                        type="password"
                        autocomplete="new-password"
                        placeholder="<?= $passwordSaved ? 'Password saved. Leave blank to keep current password.' : 'Paste Google app password' ?>"
                    >
                    <p class="hint">Use a Google app password, not the normal Google account password.</p>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="card__header">
                <div>
                    <h2 class="card__title">Email Display Fields</h2>
                    <p class="card__subtitle">How messages appear to recipients.</p>
                </div>
            </div>

            <div class="grid">
                <div class="field">
                    <label for="smtp_from_name">From name</label>
                    <input id="smtp_from_name" name="smtp_from_name" value="<?= e($smtpFromName) ?>" placeholder="The Oaks Therapy | Compliance Tracker">
                </div>

                <div class="field">
                    <label for="smtp_from_email">From email</label>
                    <input id="smtp_from_email" name="smtp_from_email" type="email" value="<?= e($smtpFromEmail) ?>" placeholder="maylin@theoakstherapy.com">
                </div>

                <div class="field full">
                    <label for="smtp_reply_to_email">Reply-to email</label>
                    <input id="smtp_reply_to_email" name="smtp_reply_to_email" type="email" value="<?= e($smtpReplyToEmail) ?>" placeholder="maylin@theoakstherapy.com">
                    <p class="hint">For Gmail SMTP, this should usually match the SMTP username unless the alternate sender address is verified.</p>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="card__header">
                <div>
                    <h2 class="card__title">Reminder Settings</h2>
                    <p class="card__subtitle">Automation controls for scheduled reminder emails and monthly summaries.</p>
                </div>
            </div>

            <label class="checkbox-row">
                <input type="checkbox" name="reminder_schedule_enabled" value="1" <?= (($settings['reminder_schedule_enabled'] ?? '1') === '1') ? 'checked' : '' ?>>
                <span>Reminder schedule enabled</span>
            </label>

            <label class="checkbox-row">
                <input type="checkbox" name="monthly_summary_enabled" value="1" <?= (($settings['monthly_summary_enabled'] ?? '1') === '1') ? 'checked' : '' ?>>
                <span>Monthly summary enabled</span>
            </label>

            <div class="field">
                <label for="reminder_days_before_deadline">Reminder days before deadline</label>
                <input id="reminder_days_before_deadline" name="reminder_days_before_deadline" value="<?= e((string)($settings['reminder_days_before_deadline'] ?? '')) ?>" placeholder="90,60,30,14,7,1">
                <p class="hint">Comma-separated list of days before a deadline.</p>
            </div>
        </section>

        <div class="button-row">
            <button type="submit">Save Settings</button>
        </div>
    </form>

    <form method="post" class="button-row">
        <?= csrf_input() ?>
        <button class="secondary" type="submit" name="send_test_email" value="1">Send test email</button>
    </form>
</main>
</body>
</html>
