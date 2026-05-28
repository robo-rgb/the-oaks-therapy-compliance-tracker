<?php
declare(strict_types=1);

require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';

App\Middleware\RequireAuth::enforce();
start_secure_session();

$userId = (int) $_SESSION['user_id'];

$licenseService = new App\Services\LicenseService();
$license = $licenseService->getByUserId($userId);

if (!$license) {
    redirect('license_edit.php');
}

$service = new App\Services\RenewalCycleService();

$data = [
    'cycle_start' => '2024-10-01',
    'cycle_end' => '2026-09-30',
    'renewal_deadline' => '2026-09-30',
    'late_renewal_deadline' => '2026-10-31',
    'is_active' => 1,
    'renewal_submitted' => 0,
    'renewal_submitted_date' => '',
    'renewal_fee_paid' => 0,
    'renewal_fee_paid_date' => '',
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
        $errors[] = 'Invalid request token.';
    } else {
        $data = array_merge($data, $_POST);
        $errors = $service->save((int) $license['id'], $data, null);

        if (!$errors) {
            redirect('cycles.php');
        }
    }
}
?>
<!doctype html>
<html>
<body>
<?php require __DIR__ . '/_auth_nav.php'; ?>

<h1>Create Renewal Cycle</h1>

<?php foreach ($errors as $error): ?>
    <p style="color:red"><?= e($error) ?></p>
<?php endforeach; ?>

<form method="post" action="<?= e(app_base_path('cycle_create.php')) ?>">
    <?= csrf_input() ?>

    <p>
        <label>
            Cycle start date<br>
            <input
                name="cycle_start"
                type="date"
                value="<?= e((string) $data['cycle_start']) ?>"
                required
            >
        </label>
    </p>

    <p>
        <label>
            Cycle end date<br>
            <input
                name="cycle_end"
                type="date"
                value="<?= e((string) $data['cycle_end']) ?>"
                required
            >
        </label>
    </p>

    <p>
        <label>
            Renewal deadline<br>
            <input
                name="renewal_deadline"
                type="date"
                value="<?= e((string) $data['renewal_deadline']) ?>"
                required
            >
        </label>
    </p>

    <p>
        <label>
            Late renewal deadline<br>
            <input
                name="late_renewal_deadline"
                type="date"
                value="<?= e((string) $data['late_renewal_deadline']) ?>"
                required
            >
        </label>
    </p>

    <p>
        <label>
            <input
                type="checkbox"
                name="is_active"
                value="1"
                <?= !empty($data['is_active']) ? 'checked' : '' ?>
            >
            Active renewal cycle
        </label>
    </p>

    <p>
        <label>
            <input
                type="checkbox"
                name="renewal_submitted"
                value="1"
                <?= !empty($data['renewal_submitted']) ? 'checked' : '' ?>
            >
            Renewal submitted
        </label>
    </p>

    <p>
        <label>
            Renewal submitted date<br>
            <input
                name="renewal_submitted_date"
                type="date"
                value="<?= e((string) $data['renewal_submitted_date']) ?>"
            >
        </label>
    </p>

    <p>
        <label>
            <input
                type="checkbox"
                name="renewal_fee_paid"
                value="1"
                <?= !empty($data['renewal_fee_paid']) ? 'checked' : '' ?>
            >
            Renewal fee paid
        </label>
    </p>

    <p>
        <label>
            Renewal fee paid date<br>
            <input
                name="renewal_fee_paid_date"
                type="date"
                value="<?= e((string) $data['renewal_fee_paid_date']) ?>"
            >
        </label>
    </p>

    <p>
        <button type="submit">Save</button>
    </p>
</form>

</body>
</html>