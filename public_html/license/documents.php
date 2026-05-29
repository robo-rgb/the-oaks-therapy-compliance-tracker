<?php
declare(strict_types=1);

require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';

App\Middleware\RequireAuth::enforce();
start_secure_session();

$userId = (int)$_SESSION['user_id'];
$license = (new App\Services\LicenseService())->getByUserId($userId);

if (!$license) {
    redirect('license_edit.php');
}

$cycles = (new App\Services\RenewalCycleService())->listByLicense((int)$license['id']);
$cycleId = (int)($_GET['renewal_cycle_id'] ?? 0);
$type = trim((string)($_GET['document_type'] ?? ''));
$svc = new App\Services\DocumentService();
$rows = $svc->list((int)$license['id'], $cycleId ?: null, $type ?: null);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Documents - The Oaks Therapy Compliance Tracker</title>
</head>
<body>
<?php require __DIR__ . '/_auth_nav.php'; ?>

<main id="main-content" class="page-shell">
    <header class="page-header">
        <div class="page-header__content">
            <p class="page-eyebrow">Documentation</p>
            <h1>Documents</h1>
            <p class="page-subtitle">Store CE certificates, renewal documents, and supporting compliance files.</p>
        </div>
        <div class="page-header__actions">
            <a class="button" href="<?= e(app_base_path('document_upload.php')) ?>">Upload Document</a>
        </div>
    </header>

    <section class="card">
        <div class="card__header">
            <div>
                <h2 class="card__title">Document library</h2>
                <p class="card__subtitle"><?= count($rows) ?> <?= count($rows) === 1 ? 'document' : 'documents' ?> found.</p>
            </div>
        </div>

        <form class="filter-form" method="get">
            <div class="field">
                <label for="document_type">Document type</label>
                <select id="document_type" name="document_type">
                    <option value="">All types</option>
                    <?php foreach (App\Services\DocumentService::TYPES as $v): ?>
                        <option value="<?= e($v) ?>" <?= $type === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="renewal_cycle_id">Renewal cycle</label>
                <select id="renewal_cycle_id" name="renewal_cycle_id">
                    <option value="">All cycles</option>
                    <?php foreach ($cycles as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $cycleId === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= e($c['cycle_start'] . ' to ' . $c['cycle_end']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="toolbar-actions">
                <button type="submit">Filter</button>
                <?php if ($type !== '' || $cycleId !== 0): ?>
                    <a class="button button--secondary" href="<?= e(app_base_path('documents.php')) ?>">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!$rows): ?>
            <div class="empty-state">
                <h3>No documents found</h3>
                <p>No documents match the selected filters.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Associated CE</th>
                            <th>Renewal cycle</th>
                            <th>Upload date</th>
                            <th>Original filename</th>
                            <th>File size</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><?= e((string)$r['title']) ?></td>
                                <td><span class="badge badge--muted"><?= e((string)$r['document_type']) ?></span></td>
                                <td><?= e((string)($r['course_title'] ?? '')) ?></td>
                                <td><?= e((string)($r['renewal_cycle_id'] ?? '')) ?></td>
                                <td><?= e((string)$r['uploaded_at']) ?></td>
                                <td><?= e((string)$r['original_filename']) ?></td>
                                <td><?= e((string)$r['file_size']) ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="button button--secondary button--small" href="<?= e(app_base_path('download.php')) . '?id=' . (int)$r['id'] ?>">Download</a>
                                        <form
                                            class="inline-form"
                                            method="post"
                                            action="<?= e(app_base_path('document_delete.php')) . '?id=' . (int)$r['id'] ?>"
                                            onsubmit="return confirm('Are you sure you want to delete this document? This cannot be undone.');"
                                        >
                                            <?= csrf_input() ?>
                                            <button class="button--danger button--small" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
