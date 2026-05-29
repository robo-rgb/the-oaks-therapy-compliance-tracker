<?php
declare(strict_types=1);

require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';

App\Middleware\RequireAuth::enforce();
start_secure_session();

$userId = (int) $_SESSION['user_id'];

$service = new App\Services\LicenseService();
$license = $service->getByUserId($userId);

$errors = [];

$data = $license ?: [
    'licensee_first_name' => '',
    'licensee_last_name' => '',
    'license_type' => 'CSW',
    'state' => 'GA',
    'license_number' => '',
    'original_issue_date' => '',
    'status' => 'active',
    'notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
        $errors[] = 'Invalid request token.';
    } else {
        $data = array_merge($data, $_POST);
        $errors = $service->save($userId, $data, $license['id'] ?? null);

        if (!$errors) {
            redirect('license.php');
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $license ? 'Edit' : 'Create' ?> License Profile - The Oaks Therapy</title>
</head>
<body>
<?php require __DIR__ . '/_auth_nav.php'; ?>

<main id="main-content" class="page-shell page-shell--narrow">
    <div class="page-header">
        <div class="page-header__content">
            <p class="page-eyebrow">License profile</p>
            <h1><?= $license ? 'Edit' : 'Create' ?> License Profile</h1>
            <p class="page-subtitle">Maintain the licensee details used throughout renewal tracking, CE reports, and reminders.</p>
        </div>
        <div class="page-header__actions">
            <a class="button button--secondary" href="<?= e(app_base_path('license.php')) ?>">Back to Profile</a>
        </div>
    </div>

    <?php foreach ($errors as $error): ?>
        <p class="error"><?= e($error) ?></p>
    <?php endforeach; ?>

    <section class="panel">
        <div class="card__header">
            <div>
                <h2 class="card__title">Licensee information</h2>
                <p class="card__subtitle">Required fields are used for compliance summaries and audit-ready records.</p>
            </div>
        </div>

        <form method="post" action="<?= e(app_base_path('license_edit.php')) ?>">
            <?= csrf_input() ?>

            <div class="grid">
                <div class="field">
                    <label for="licensee_first_name">First name</label>
                    <input
                        id="licensee_first_name"
                        name="licensee_first_name"
                        value="<?= e((string) $data['licensee_first_name']) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="licensee_last_name">Last name</label>
                    <input
                        id="licensee_last_name"
                        name="licensee_last_name"
                        value="<?= e((string) $data['licensee_last_name']) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="license_type">License type</label>
                    <input
                        id="license_type"
                        name="license_type"
                        value="<?= e((string) $data['license_type']) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="state">State</label>
                    <input
                        id="state"
                        name="state"
                        value="<?= e((string) $data['state']) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="license_number">License number</label>
                    <input
                        id="license_number"
                        name="license_number"
                        value="<?= e((string) $data['license_number']) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="original_issue_date">Original issue date</label>
                    <input
                        id="original_issue_date"
                        name="original_issue_date"
                        type="date"
                        value="<?= e((string) $data['original_issue_date']) ?>"
                    >
                </div>

                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="active" <?= (string) $data['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= (string) $data['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="expired" <?= (string) $data['status'] === 'expired' ? 'selected' : '' ?>>Expired</option>
                    </select>
                </div>

                <div class="field full">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes"><?= e((string) $data['notes']) ?></textarea>
                </div>
            </div>

            <div class="button-row">
                <button type="submit">Save License Profile</button>
                <a class="button button--secondary" href="<?= e(app_base_path('license.php')) ?>">Cancel</a>
            </div>
        </form>
    </section>
</main>

</body>
</html>
