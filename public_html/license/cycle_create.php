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
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create Renewal Cycle - The Oaks Therapy</title>
</head>
<body>
<?php require __DIR__ . '/_auth_nav.php'; ?>

<main id="main-content" class="page-shell page-shell--narrow">
    <div class="page-header">
        <div class="page-header__content">
            <p class="page-eyebrow">Renewal cycle</p>
            <h1>Create Renewal Cycle</h1>
            <p class="page-subtitle">Add a renewal period and deadline dates so CE hours can be tracked against the correct cycle.</p>
        </div>
        <div class="page-header__actions">
            <a class="button button--secondary" href="<?= e(app_base_path('cycles.php')) ?>">Back to Cycles</a>
        </div>
    </div>

    <?php foreach ($errors as $error): ?>
        <p class="error"><?= e($error) ?></p>
    <?php endforeach; ?>

    <section class="panel">
        <div class="card__header">
            <div>
                <h2 class="card__title">Cycle details</h2>
                <p class="card__subtitle">Dates should match the licensing board renewal period.</p>
            </div>
        </div>

        <form method="post" action="<?= e(app_base_path('cycle_create.php')) ?>">
            <?= csrf_input() ?>

            <div class="grid">
                <div class="field">
                    <label for="cycle_start">Cycle start date</label>
                    <input
                        id="cycle_start"
                        name="cycle_start"
                        type="date"
                        value="<?= e((string) $data['cycle_start']) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="cycle_end">Cycle end date</label>
                    <input
                        id="cycle_end"
                        name="cycle_end"
                        type="date"
                        value="<?= e((string) $data['cycle_end']) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="renewal_deadline">Renewal deadline</label>
                    <input
                        id="renewal_deadline"
                        name="renewal_deadline"
                        type="date"
                        value="<?= e((string) $data['renewal_deadline']) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="late_renewal_deadline">Late renewal deadline</label>
                    <input
                        id="late_renewal_deadline"
                        name="late_renewal_deadline"
                        type="date"
                        value="<?= e((string) $data['late_renewal_deadline']) ?>"
                        required
                    >
                </div>
            </div>

            <div class="panel">
                <h3>Status tracking</h3>

                <label class="checkbox-row">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        <?= !empty($data['is_active']) ? 'checked' : '' ?>
                    >
                    <span>Active renewal cycle</span>
                </label>

                <label class="checkbox-row">
                    <input
                        type="checkbox"
                        name="renewal_submitted"
                        value="1"
                        <?= !empty($data['renewal_submitted']) ? 'checked' : '' ?>
                    >
                    <span>Renewal submitted</span>
                </label>

                <div class="field">
                    <label for="renewal_submitted_date">Renewal submitted date</label>
                    <input
                        id="renewal_submitted_date"
                        name="renewal_submitted_date"
                        type="date"
                        value="<?= e((string) $data['renewal_submitted_date']) ?>"
                    >
                </div>

                <label class="checkbox-row">
                    <input
                        type="checkbox"
                        name="renewal_fee_paid"
                        value="1"
                        <?= !empty($data['renewal_fee_paid']) ? 'checked' : '' ?>
                    >
                    <span>Renewal fee paid</span>
                </label>

                <div class="field">
                    <label for="renewal_fee_paid_date">Renewal fee paid date</label>
                    <input
                        id="renewal_fee_paid_date"
                        name="renewal_fee_paid_date"
                        type="date"
                        value="<?= e((string) $data['renewal_fee_paid_date']) ?>"
                    >
                </div>
            </div>

            <div class="button-row">
                <button type="submit">Create Cycle</button>
                <a class="button button--secondary" href="<?= e(app_base_path('cycles.php')) ?>">Cancel</a>
            </div>
        </form>
    </section>
</main>

</body>
</html>
