<?php
declare(strict_types=1);

require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';

App\Middleware\RequireAuth::enforce();
start_secure_session();

$userId = (int)$_SESSION['user_id'];
$licenseService = new App\Services\LicenseService();
$cycleService = new App\Services\RenewalCycleService();
$calculator = new App\Services\ComplianceCalculator();

$license = $licenseService->getByUserId($userId);
$activeCycle = $license ? $cycleService->getActive((int)$license['id']) : null;
$compliance = ($license && $activeCycle) ? $calculator->evaluate((int)$license['id'], (int)$activeCycle['id']) : null;

function oaks_status_badge_class(?string $state): string
{
    return match ($state) {
        'green' => 'badge--success',
        'yellow' => 'badge--warning',
        'red' => 'badge--danger',
        default => 'badge--muted',
    };
}

$licenseName = $license
    ? trim((string)($license['licensee_first_name'] ?? '') . ' ' . (string)($license['licensee_last_name'] ?? ''))
    : '';

$overallState = (string)($compliance['requirement_status']['overall']['state'] ?? 'gray');
$totalState = (string)($compliance['requirement_status']['total']['state'] ?? 'gray');
$ethicsState = (string)($compliance['requirement_status']['ethics']['state'] ?? 'gray');
$coreState = (string)($compliance['requirement_status']['core']['state'] ?? 'gray');
$relatedState = (string)($compliance['requirement_status']['related']['state'] ?? 'gray');
$asyncState = (string)($compliance['requirement_status']['asynchronous']['state'] ?? 'gray');
$independentState = (string)($compliance['requirement_status']['independent']['state'] ?? 'gray');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Dashboard - The Oaks Therapy Compliance Tracker</title>
</head>
<body>
<?php require __DIR__ . '/_auth_nav.php'; ?>

<main id="main-content" class="page-shell">
    <header class="page-header">
        <div class="page-header__content">
            <p class="page-eyebrow">Compliance overview</p>
            <h1>Dashboard</h1>
            <p class="page-subtitle">A quick view of license status, renewal deadlines, CE progress, and missing documentation.</p>
        </div>
    </header>

    <?php if (!$license): ?>
        <section class="empty-state">
            <h2>No license profile exists</h2>
            <p>Create the license profile first so the tracker can evaluate renewal cycles and continuing education.</p>
            <p><a class="button" href="<?= e(app_base_path('license_edit.php')) ?>">Create License Profile</a></p>
        </section>
    <?php else: ?>
        <section class="content-grid content-grid--3">
            <article class="stat-card">
                <p class="stat-label">Licensee</p>
                <p class="stat-value"><?= e($licenseName !== '' ? $licenseName : 'Not set') ?></p>
                <p class="muted"><?= e((string)$license['license_type']) ?> · <?= e((string)($license['state'] ?? '')) ?></p>
            </article>

            <article class="stat-card">
                <p class="stat-label">License number</p>
                <p class="stat-value"><?= e((string)($license['license_number'] ?? '')) ?></p>
                <p><span class="badge <?= strtolower((string)$license['status']) === 'active' ? 'badge--success' : 'badge--warning' ?>"><?= e((string)$license['status']) ?></span></p>
            </article>

            <article class="stat-card">
                <p class="stat-label">Overall compliance</p>
                <?php if ($compliance): ?>
                    <p class="stat-value"><?= !empty($compliance['compliant']) ? 'Compliant' : 'Needs review' ?></p>
                    <p><span class="badge <?= e(oaks_status_badge_class($overallState)) ?>"><?= e(ucfirst($overallState)) ?></span></p>
                <?php else: ?>
                    <p class="stat-value">Not ready</p>
                    <p class="muted">Add an active renewal cycle.</p>
                <?php endif; ?>
            </article>
        </section>

        <?php if (!$activeCycle): ?>
            <section class="empty-state" style="margin-top:1rem;">
                <h2>No active renewal cycle</h2>
                <p>Create or activate a renewal cycle to calculate CE progress and reminder deadlines.</p>
                <p><a class="button" href="<?= e(app_base_path('cycle_create.php')) ?>">Create Renewal Cycle</a></p>
            </section>
        <?php else: ?>
            <section class="card" style="margin-top:1rem;">
                <div class="card__header">
                    <div>
                        <h2 class="card__title">Active renewal cycle</h2>
                        <p class="card__subtitle"><?= e((string)$activeCycle['cycle_start']) ?> to <?= e((string)$activeCycle['cycle_end']) ?></p>
                    </div>
                </div>

                <div class="detail-grid">
                    <div class="detail-item">
                        <p class="detail-label">Renewal deadline</p>
                        <p class="detail-value"><?= e((string)$activeCycle['renewal_deadline']) ?></p>
                    </div>
                    <div class="detail-item">
                        <p class="detail-label">Late renewal deadline</p>
                        <p class="detail-value"><?= e((string)$activeCycle['late_renewal_deadline']) ?></p>
                    </div>
                    <div class="detail-item">
                        <p class="detail-label">Renewal submitted</p>
                        <p class="detail-value"><?= (int)$activeCycle['renewal_submitted'] === 1 ? 'Yes' : 'No' ?></p>
                    </div>
                    <div class="detail-item">
                        <p class="detail-label">Renewal fee paid</p>
                        <p class="detail-value"><?= (int)$activeCycle['renewal_fee_paid'] === 1 ? 'Yes' : 'No' ?></p>
                    </div>
                </div>
            </section>

            <section class="card" style="margin-top:1rem;">
                <div class="card__header">
                    <div>
                        <h2 class="card__title">Continuing education progress</h2>
                        <p class="card__subtitle">Current totals against Georgia LCSW renewal requirements.</p>
                    </div>
                    <span class="badge <?= e(oaks_status_badge_class($overallState)) ?>"><?= !empty($compliance['compliant']) ? 'Compliant' : 'Noncompliant' ?></span>
                </div>

                <div class="content-grid content-grid--3">
                    <div class="metric-card">
                        <p class="metric-label">Total CE</p>
                        <p class="metric-value"><?= e((string)($compliance['total_hours'] ?? '0')) ?> / 35</p>
                        <span class="badge <?= e(oaks_status_badge_class($totalState)) ?>"><?= e(ucfirst($totalState)) ?></span>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Ethics</p>
                        <p class="metric-value"><?= e((string)($compliance['ethics_hours'] ?? '0')) ?> / 5</p>
                        <span class="badge <?= e(oaks_status_badge_class($ethicsState)) ?>">Synchronous</span>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Core</p>
                        <p class="metric-value"><?= e((string)($compliance['core_hours'] ?? '0')) ?> / 15</p>
                        <span class="badge <?= e(oaks_status_badge_class($coreState)) ?>">Minimum</span>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Related</p>
                        <p class="metric-value"><?= e((string)($compliance['related_hours'] ?? '0')) ?> / 15</p>
                        <span class="badge <?= e(oaks_status_badge_class($relatedState)) ?>">Maximum</span>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Asynchronous</p>
                        <p class="metric-value"><?= e((string)($compliance['asynchronous_hours'] ?? '0')) ?> / 10</p>
                        <span class="badge <?= e(oaks_status_badge_class($asyncState)) ?>">Maximum</span>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Independent study</p>
                        <p class="metric-value"><?= e((string)($compliance['independent_study_hours'] ?? '0')) ?> / 5</p>
                        <span class="badge <?= e(oaks_status_badge_class($independentState)) ?>">Maximum</span>
                    </div>
                </div>

                <div class="detail-grid" style="margin-top:1rem;">
                    <div class="detail-item">
                        <p class="detail-label">Missing certificates</p>
                        <p class="detail-value"><?= e((string)($compliance['missing_document_count'] ?? 0)) ?></p>
                    </div>
                    <div class="detail-item">
                        <p class="detail-label">Overall status</p>
                        <p class="detail-value"><?= !empty($compliance['compliant']) ? 'Compliant' : 'Noncompliant' ?></p>
                    </div>
                </div>
            </section>

            <?php if (!empty($compliance['warnings'])): ?>
                <section class="card" style="margin-top:1rem;">
                    <h2>Warnings</h2>
                    <ul>
                        <?php foreach ($compliance['warnings'] as $w): ?>
                            <li><?= e((string)$w) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php if (!empty($compliance['errors'])): ?>
                <section class="card" style="margin-top:1rem;">
                    <h2>Errors</h2>
                    <ul>
                        <?php foreach ($compliance['errors'] as $er): ?>
                            <li><?= e((string)$er) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</main>
</body>
</html>
