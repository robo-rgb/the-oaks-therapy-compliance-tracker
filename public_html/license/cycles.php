<?php
declare(strict_types=1);

require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';

App\Middleware\RequireAuth::enforce();
start_secure_session();

$userId = (int)$_SESSION['user_id'];
$licenseService = new App\Services\LicenseService();
$license = $licenseService->getByUserId($userId);
$cycles = [];

if ($license) {
    $svc = new App\Services\RenewalCycleService();
    $cycles = $svc->listByLicense((int)$license['id']);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Renewal Cycles - The Oaks Therapy Compliance Tracker</title>
</head>
<body>
<?php require __DIR__ . '/_auth_nav.php'; ?>

<main id="main-content" class="page-shell">
    <header class="page-header">
        <div class="page-header__content">
            <p class="page-eyebrow">Compliance timeline</p>
            <h1>Renewal Cycles</h1>
            <p class="page-subtitle">Track license renewal periods, deadlines, and whether each cycle is active.</p>
        </div>

        <?php if ($license): ?>
            <div class="page-header__actions">
                <a class="button" href="<?= e(app_base_path('cycle_create.php')) ?>">Create Cycle</a>
            </div>
        <?php endif; ?>
    </header>

    <?php if (!$license): ?>
        <section class="empty-state">
            <h2>License profile required</h2>
            <p>Create a license profile before adding renewal cycles.</p>
            <p><a class="button" href="<?= e(app_base_path('license_edit.php')) ?>">Create License Profile</a></p>
        </section>
    <?php elseif (!$cycles): ?>
        <section class="empty-state">
            <h2>No renewal cycles found</h2>
            <p>Add the current license renewal period to begin tracking CE requirements and reminder deadlines.</p>
            <p><a class="button" href="<?= e(app_base_path('cycle_create.php')) ?>">Create Cycle</a></p>
        </section>
    <?php else: ?>
        <section class="card">
            <div class="card__header">
                <div>
                    <h2 class="card__title">Tracked cycles</h2>
                    <p class="card__subtitle"><?= count($cycles) ?> <?= count($cycles) === 1 ? 'cycle' : 'cycles' ?> on file.</p>
                </div>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Cycle start</th>
                            <th>Cycle end</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cycles as $c): ?>
                            <?php $isActive = (int)$c['is_active'] === 1; ?>
                            <tr>
                                <td><?= e((string)$c['cycle_start']) ?></td>
                                <td><?= e((string)$c['cycle_end']) ?></td>
                                <td><span class="badge <?= $isActive ? 'badge--success' : 'badge--muted' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span></td>
                                <td><a class="button button--secondary button--small" href="<?= e(app_base_path('cycle_edit.php')) . '?id=' . (int)$c['id'] ?>">Edit</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
