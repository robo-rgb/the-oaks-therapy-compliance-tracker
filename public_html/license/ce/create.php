<?php
declare(strict_types=1);

require __DIR__ . '/../../../private/license_tracker/vendor/autoload.php';

App\Middleware\RequireAuth::enforce();
start_secure_session();

$userId = (int) $_SESSION['user_id'];

$licenseService = new App\Services\LicenseService();
$cycleService = new App\Services\RenewalCycleService();
$ceService = new App\Services\CeCourseService();
$documentService = new App\Services\DocumentService();

$license = $licenseService->getByUserId($userId);
if (!$license) {
    redirect('license_edit.php');
}

$activeCycle = $cycleService->getActive((int) $license['id']);
if (!$activeCycle) {
    redirect('cycle_create.php');
}

$cycles = $cycleService->listByLicense((int) $license['id']);
$data = $ceService->defaults((int) $license['id'], (int) $activeCycle['id']);
$documentData = ['document_type' => 'ce_certificate', 'title' => '', 'notes' => ''];
$errors = [];
$warnings = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
        $errors[] = 'Invalid request token.';
    } else {
        $data = array_merge($data, $_POST, ['license_id' => (int) $license['id']]);
        $documentData = [
            'document_type' => (string) ($_POST['document_type'] ?? 'ce_certificate'),
            'title' => trim((string) ($_POST['document_title'] ?? '')),
            'notes' => trim((string) ($_POST['document_notes'] ?? '')),
        ];

        $result = $ceService->create($data);
        $errors = $result['errors'];
        $warnings = $result['warnings'];

        if (!$errors) {
            $courseId = (int) ($result['id'] ?? 0);
            $file = $_FILES['document_file'] ?? [];
            $hasFile = isset($file['error']) && (int) $file['error'] !== UPLOAD_ERR_NO_FILE;

            if ($courseId > 0 && $hasFile) {
                $uploadResult = $documentService->upload([
                    'license_id' => (int) $license['id'],
                    'renewal_cycle_id' => (int) $data['renewal_cycle_id'],
                    'ce_course_id' => $courseId,
                    'document_type' => $documentData['document_type'],
                    'title' => $documentData['title'] !== '' ? $documentData['title'] : (string) $data['course_title'],
                    'notes' => $documentData['notes'],
                ], $file);

                if ($uploadResult['errors']) {
                    $message = rawurlencode('CE course was saved, but the document upload failed: ' . implode(' ', $uploadResult['errors']));
                    redirect('ce/edit.php?id=' . $courseId . '&notice=' . $message);
                }
            }

            redirect('ce/edit.php?id=' . $courseId);
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create CE Course - The Oaks Therapy</title>
</head>
<body>
<?php require __DIR__ . '/../_auth_nav.php'; ?>

<main id="main-content" class="page-shell">
    <div class="page-header">
        <div class="page-header__content">
            <p class="page-eyebrow">Continuing education</p>
            <h1>Create CE Course</h1>
            <p class="page-subtitle">Add a completed continuing education course and optionally upload the certificate now.</p>
        </div>
        <div class="page-header__actions">
            <a class="button button--secondary" href="<?= e(app_base_path('ce/index.php')) ?>">Back to CE Courses</a>
        </div>
    </div>

    <?php foreach ($errors as $m): ?>
        <p class="error"><?= e($m) ?></p>
    <?php endforeach; ?>

    <?php foreach ($warnings as $m): ?>
        <p class="notice"><?= e($m) ?></p>
    <?php endforeach; ?>

    <form method="post" action="<?= e(app_base_path('ce/create.php')) ?>" enctype="multipart/form-data">
        <?= csrf_input() ?>

        <section class="panel">
            <div class="card__header">
                <div>
                    <h2 class="card__title">Course details</h2>
                    <p class="card__subtitle">Use the certificate or provider record as the source for these fields.</p>
                </div>
            </div>

            <div class="grid">
                <div class="field full">
                    <label for="course_title">Course title</label>
                    <input id="course_title" name="course_title" value="<?= e((string) $data['course_title']) ?>" required>
                </div>

                <div class="field">
                    <label for="provider_name">Provider</label>
                    <input id="provider_name" name="provider_name" value="<?= e((string) $data['provider_name']) ?>" required>
                </div>

                <div class="field">
                    <label for="date_completed">Date completed</label>
                    <input id="date_completed" name="date_completed" type="date" value="<?= e((string) $data['date_completed']) ?>" required>
                </div>

                <div class="field">
                    <label for="hours">Hours / CE credits</label>
                    <input id="hours" name="hours" type="number" step="0.25" min="0.25" value="<?= e((string) $data['hours']) ?>" required>
                </div>

                <div class="field">
                    <label for="renewal_cycle_id">Renewal cycle</label>
                    <select id="renewal_cycle_id" name="renewal_cycle_id">
                        <?php foreach ($cycles as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= (int) $data['renewal_cycle_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                                <?= e($c['cycle_start'] . ' to ' . $c['cycle_end']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <?php foreach (App\Services\CeCourseService::CATEGORIES as $v): ?>
                            <option value="<?= e($v) ?>" <?= $data['category'] === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="format">Format</label>
                    <select id="format" name="format">
                        <?php foreach (App\Services\CeCourseService::FORMATS as $v): ?>
                            <option value="<?= e($v) ?>" <?= $data['format'] === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="delivery_mode">Delivery mode</label>
                    <select id="delivery_mode" name="delivery_mode">
                        <?php foreach (App\Services\CeCourseService::DELIVERY_MODES as $v): ?>
                            <option value="<?= e($v) ?>" <?= $data['delivery_mode'] === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="approval_source">Approval source</label>
                    <select id="approval_source" name="approval_source">
                        <?php foreach (App\Services\CeCourseService::APPROVAL_SOURCES as $v): ?>
                            <option value="<?= e($v) ?>" <?= $data['approval_source'] === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field full">
                    <label class="checkbox-row">
                        <input type="checkbox" name="counts_toward_cycle" value="1" <?= !empty($data['counts_toward_cycle']) ? 'checked' : '' ?>>
                        <span>Counts toward renewal cycle</span>
                    </label>

                    <label class="checkbox-row">
                        <input type="checkbox" name="is_professional_conference" value="1" <?= !empty($data['is_professional_conference']) ? 'checked' : '' ?>>
                        <span>Professional conference</span>
                    </label>
                </div>

                <div class="field full">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes"><?= e((string) $data['notes']) ?></textarea>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="card__header">
                <div>
                    <h2 class="card__title">Certificate / documentation</h2>
                    <p class="card__subtitle">Optional. Upload the certificate now, or leave this blank and upload it later.</p>
                </div>
            </div>

            <div class="grid">
                <div class="field">
                    <label for="document_type">Document type</label>
                    <select id="document_type" name="document_type">
                        <?php foreach (App\Services\DocumentService::CERT_TYPES as $v): ?>
                            <option value="<?= e($v) ?>" <?= $documentData['document_type'] === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="document_title">Document title</label>
                    <input id="document_title" name="document_title" value="<?= e((string) $documentData['title']) ?>" placeholder="Defaults to course title if blank">
                </div>

                <div class="field full">
                    <label for="document_file">Certificate/document file</label>
                    <input id="document_file" type="file" name="document_file">
                </div>

                <div class="field full">
                    <label for="document_notes">Document notes</label>
                    <textarea id="document_notes" name="document_notes"><?= e((string) $documentData['notes']) ?></textarea>
                </div>
            </div>
        </section>

        <div class="button-row">
            <button type="submit">Save CE Course</button>
            <a class="button button--secondary" href="<?= e(app_base_path('ce/index.php')) ?>">Cancel</a>
        </div>
    </form>
</main>

</body>
</html>
