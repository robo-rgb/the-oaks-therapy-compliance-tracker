<?php
declare(strict_types=1);

require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';

App\Middleware\RequireAuth::enforce();
start_secure_session();

$userId = (int)$_SESSION['user_id'];
$service = new App\Services\LicenseService();
$license = $service->getByUserId($userId);

$licenseName = $license
    ? trim((string)($license['licensee_first_name'] ?? '') . ' ' . (string)($license['licensee_last_name'] ?? ''))
    : '';

$status = strtolower((string)($license['status'] ?? ''));
$statusClass = $status === 'active' ? 'badge--success' : ($status === '' ? 'badge--muted' : 'badge--warning');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>License Profile - The Oaks Therapy Compliance Tracker</title>
</head>
<body>
<?php require __DIR__ . '/_auth_nav.php'; ?>

<main id="main-content" class="page-shell">
    <header class="page-header">
        <div class="page-header__content">
            <p class="page-eyebrow">Licensure</p>
            <h1>License Profile</h1>
            <p class="page-subtitle">Core license information used to evaluate renewal cycles, continuing education, and compliance status.</p>
        </div>

        <div class="page-header__actions">
            <a class="button" href="<?= e(app_base_path('license_edit.php')) ?>">
                <?= $license ? 'Edit Profile' : 'Create License Profile' ?>
            </a>
        </div>
    </header>

    <?php if (!$license): ?>
        <section class="empty-state">
            <h2>No license profile found</h2>
            <p>Create the license profile first so the tracker can connect renewal cycles, CE courses, documents, and reminders.</p>
            <p><a class="button" href="<?= e(app_base_path('license_edit.php')) ?>">Create License Profile</a></p>
        </section>
    <?php else: ?>
        <section class="card">
            <div class="card__header">
                <div>
                    <h2 class="card__title"><?= e($licenseName !== '' ? $licenseName : 'Licensee') ?></h2>
                    <p class="card__subtitle"><?= e((string)($license['license_type'] ?? 'License')) ?> · <?= e((string)($license['state'] ?? '')) ?></p>
                </div>
                <span class="badge <?= e($statusClass) ?>"><?= e((string)($license['status'] ?? 'Unknown')) ?></span>
            </div>

            <div class="detail-grid">
                <div class="detail-item">
                    <p class="detail-label">Name</p>
                    <p class="detail-value"><?= e($licenseName) ?></p>
                </div>
                <div class="detail-item">
                    <p class="detail-label">License type</p>
                    <p class="detail-value"><?= e((string)($license['license_type'] ?? '')) ?></p>
                </div>
                <div class="detail-item">
                    <p class="detail-label">State</p>
                    <p class="detail-value"><?= e((string)($license['state'] ?? '')) ?></p>
                </div>
                <div class="detail-item">
                    <p class="detail-label">License number</p>
                    <p class="detail-value"><?= e((string)($license['license_number'] ?? '')) ?></p>
                </div>
                <div class="detail-item">
                    <p class="detail-label">Original issue date</p>
                    <p class="detail-value"><?= e((string)($license['original_issue_date'] ?? '')) ?></p>
                </div>
                <div class="detail-item">
                    <p class="detail-label">Status</p>
                    <p class="detail-value"><?= e((string)($license['status'] ?? '')) ?></p>
                </div>
                <div class="detail-item field full">
                    <p class="detail-label">Notes</p>
                    <p class="detail-value"><?= e((string)($license['notes'] ?? '')) ?></p>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
