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
<html>
<body>
<?php require __DIR__ . '/_auth_nav.php'; ?>

<h1><?= $license ? 'Edit' : 'Create' ?> License Profile</h1>

<?php foreach ($errors as $error): ?>
    <p style="color:red"><?= e($error) ?></p>
<?php endforeach; ?>

<form method="post" action="<?= e(app_base_path('license_edit.php')) ?>">
    <?= csrf_input() ?>

    <p>
        <label>
            First name<br>
            <input
                name="licensee_first_name"
                value="<?= e((string) $data['licensee_first_name']) ?>"
                required
            >
        </label>
    </p>

    <p>
        <label>
            Last name<br>
            <input
                name="licensee_last_name"
                value="<?= e((string) $data['licensee_last_name']) ?>"
                required
            >
        </label>
    </p>

    <p>
        <label>
            License type<br>
            <input
                name="license_type"
                value="<?= e((string) $data['license_type']) ?>"
                required
            >
        </label>
    </p>

    <p>
        <label>
            State<br>
            <input
                name="state"
                value="<?= e((string) $data['state']) ?>"
                required
            >
        </label>
    </p>

    <p>
        <label>
            License number<br>
            <input
                name="license_number"
                value="<?= e((string) $data['license_number']) ?>"
                required
            >
        </label>
    </p>

    <p>
        <label>
            Original issue date<br>
            <input
                name="original_issue_date"
                type="date"
                value="<?= e((string) $data['original_issue_date']) ?>"
            >
        </label>
    </p>

    <p>
        <label>
            Status<br>
            <select name="status" required>
                <option value="active" <?= (string) $data['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= (string) $data['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                <option value="expired" <?= (string) $data['status'] === 'expired' ? 'selected' : '' ?>>Expired</option>
            </select>
        </label>
    </p>

    <p>
        <label>
            Notes<br>
            <textarea name="notes"><?= e((string) $data['notes']) ?></textarea>
        </label>
    </p>

    <p>
        <button type="submit">Save</button>
    </p>
</form>

</body>
</html>