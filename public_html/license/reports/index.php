<?php
declare(strict_types=1);

require __DIR__ . '/../../../private/license_tracker/vendor/autoload.php';

App\Middleware\RequireAuth::enforce();
start_secure_session();

$userId = (int)$_SESSION['user_id'];
$ls = new App\Services\LicenseService();
$rs = new App\Services\RenewalCycleService();
$rep = new App\Services\ReportService();

$license = $ls->getByUserId($userId);

if (!$license) {
    redirect('license_edit.php');
}

$cycles = $rs->listByLicense((int)$license['id']);
$resolved = $rep->resolveCycleForLicense((int)$license['id'], (int)($_GET['cycle_id'] ?? 0));
$cycleId = (int)($resolved['id'] ?? 0);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reports - The Oaks Therapy Compliance Tracker</title>
</head>
<body>
<?php require __DIR__ . '/../_auth_nav.php'; ?>

<main id="main-content" class="page-shell">
    <header class="page-header">
        <div class="page-header__content">
            <p class="page-eyebrow">Exports and audit support</p>
            <h1>Reports</h1>
            <p class="page-subtitle">Generate printable summaries, PDFs, CSV exports, and audit packets for a selected renewal cycle.</p>
        </div>
    </header>

    <section class="card">
        <div class="card__header">
            <div>
                <h2 class="card__title">Report cycle</h2>
                <p class="card__subtitle">Choose which renewal cycle to use for generated reports.</p>
            </div>
        </div>

        <form class="filter-form" method="get">
            <div class="field">
                <label for="cycle_id">Renewal cycle</label>
                <select id="cycle_id" name="cycle_id">
                    <?php foreach ($cycles as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $cycleId === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= e($c['cycle_start'] . ' to ' . $c['cycle_end']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="toolbar-actions">
                <button type="submit">Set Cycle</button>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="card__header">
            <div>
                <h2 class="card__title">Available reports</h2>
                <p class="card__subtitle">Use these exports for review, recordkeeping, or audit preparation.</p>
            </div>
        </div>

        <ul class="report-list content-grid--2">
            <li>
                <a class="report-card" href="<?= e(app_base_path('reports/ce-summary.php')) . '?cycle_id=' . $cycleId ?>">
                    <strong>Printable CE Summary HTML</strong>
                    <span>Browser-friendly summary of CE progress and requirements.</span>
                </a>
            </li>
            <li>
                <a class="report-card" href="<?= e(app_base_path('reports/ce-summary-pdf.php')) . '?cycle_id=' . $cycleId ?>">
                    <strong>PDF CE Report</strong>
                    <span>Formatted PDF version for records or sharing.</span>
                </a>
            </li>
            <li>
                <a class="report-card" href="<?= e(app_base_path('reports/ce-export-csv.php')) . '?cycle_id=' . $cycleId ?>">
                    <strong>CSV Export</strong>
                    <span>Spreadsheet-friendly export of CE course data.</span>
                </a>
            </li>
            <li>
                <a class="report-card" href="<?= e(app_base_path('reports/audit-packet.php')) . '?cycle_id=' . $cycleId ?>">
                    <strong>Audit Packet ZIP</strong>
                    <span>Bundled files for audit preparation.</span>
                </a>
            </li>
        </ul>
    </section>
</main>
</body>
</html>
