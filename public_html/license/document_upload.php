<?php
declare(strict_types=1);

require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';

App\Middleware\RequireAuth::enforce();
start_secure_session();

$userId = (int) $_SESSION['user_id'];
$license = (new App\Services\LicenseService())->getByUserId($userId);

if (!$license) {
    redirect('license_edit.php');
}

$cycleSvc = new App\Services\RenewalCycleService();
$ceService = new App\Services\CeCourseService();
$documentService = new App\Services\DocumentService();

$cycles = $cycleSvc->listByLicense((int) $license['id']);
$active = $cycleSvc->getActive((int) $license['id']);
$prefillCe = (int) ($_GET['ce_course_id'] ?? 0);
$selectedCycleId = (int) ($_POST['renewal_cycle_id'] ?? ($active['id'] ?? 0));

if ($prefillCe > 0) {
    $prefillCourse = $ceService->getById($prefillCe, (int) $license['id']);

    if ($prefillCourse) {
        $selectedCycleId = (int) $prefillCourse['renewal_cycle_id'];
    }
}

$ceRows = $ceService->list([
    'license_id' => (int) $license['id'],
    'renewal_cycle_id' => $selectedCycleId,
    'category' => '',
    'delivery_mode' => '',
    'q' => '',
]);

$data = [
    'license_id' => (int) $license['id'],
    'renewal_cycle_id' => $selectedCycleId,
    'ce_course_id' => $prefillCe ?: '',
    'document_type' => 'ce_certificate',
    'title' => '',
    'notes' => '',
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
        $errors[] = 'Invalid request token.';
    } else {
        $data = array_merge($data, $_POST, ['license_id' => (int) $license['id']]);
        $result = $documentService->upload($data, $_FILES['document_file'] ?? []);
        $errors = $result['errors'];

        if (!$errors) {
            redirect('documents.php');
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Upload Document - The Oaks Therapy</title>
</head>
<body>
<?php require __DIR__ . '/_auth_nav.php'; ?>

<main id="main-content" class="page-shell page-shell--narrow">
    <div class="page-header">
        <div class="page-header__content">
            <p class="page-eyebrow">Documents</p>
            <h1>Upload Document</h1>
            <p class="page-subtitle">Attach CE certificates, audit records, renewal documents, and related files to the correct cycle.</p>
        </div>
        <div class="page-header__actions">
            <a class="button button--secondary" href="<?= e(app_base_path('documents.php')) ?>">Back to Documents</a>
        </div>
    </div>

    <?php foreach ($errors as $e1): ?>
        <p class="error"><?= e($e1) ?></p>
    <?php endforeach; ?>

    <section class="panel">
        <div class="card__header">
            <div>
                <h2 class="card__title">Document details</h2>
                <p class="card__subtitle">Choose the correct document type and association so reports stay organized.</p>
            </div>
        </div>

        <form method="post" enctype="multipart/form-data">
            <?= csrf_input() ?>

            <div class="grid">
                <div class="field">
                    <label for="document_type">Document type</label>
                    <select id="document_type" name="document_type">
                        <?php foreach (App\Services\DocumentService::TYPES as $v): ?>
                            <option value="<?= e($v) ?>" <?= $data['document_type'] === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="title">Document title</label>
                    <input id="title" name="title" value="<?= e((string) $data['title']) ?>" required>
                </div>

                <div class="field">
                    <label for="renewal_cycle_id">Renewal cycle</label>
                    <select id="renewal_cycle_id" name="renewal_cycle_id">
                        <option value="">No cycle</option>
                        <?php foreach ($cycles as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= (int) $data['renewal_cycle_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                                <?= e($c['cycle_start'] . ' to ' . $c['cycle_end']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="ce_course_id">Associated CE course</label>
                    <select id="ce_course_id" name="ce_course_id">
                        <option value="">No CE course</option>
                        <?php foreach ($ceRows as $ce): ?>
                            <option value="<?= (int) $ce['id'] ?>" <?= (int) $data['ce_course_id'] === (int) $ce['id'] ? 'selected' : '' ?>>
                                <?= e($ce['course_title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$ceRows): ?>
                        <p class="hint">Certificate-style documents require an associated CE course. Create the CE course first, or upload the certificate from the CE course page.</p>
                    <?php endif; ?>
                </div>

                <div class="field full">
                    <label for="document_file">File</label>
                    <input id="document_file" type="file" name="document_file" required>
                </div>

                <div class="field full">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes"><?= e((string) $data['notes']) ?></textarea>
                </div>
            </div>

            <div class="button-row">
                <button type="submit">Upload Document</button>
                <a class="button button--secondary" href="<?= e(app_base_path('documents.php')) ?>">Cancel</a>
            </div>
        </form>
    </section>
</main>

</body>
</html>
