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
if (!$license) { redirect('license_edit.php'); }

$activeCycle = $cycleService->getActive((int)$license['id']);
if (!$activeCycle) { redirect('cycle_create.php'); }

$cycles = $cycleService->listByLicense((int)$license['id']);
$data = $ceService->defaults((int)$license['id'], (int)$activeCycle['id']);
$documentData = ['document_type'=>'ce_certificate','title'=>'','notes'=>''];
$errors = []; $warnings = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) { $errors[] = 'Invalid request token.'; }
    else {
        $data = array_merge($data, $_POST, ['license_id'=>(int)$license['id']]);
        $documentData = [
            'document_type'=>(string)($_POST['document_type'] ?? 'ce_certificate'),
            'title'=>trim((string)($_POST['document_title'] ?? '')),
            'notes'=>trim((string)($_POST['document_notes'] ?? '')),
        ];
        $result = $ceService->create($data); $errors = $result['errors']; $warnings = $result['warnings'];
        if (!$errors) {
            $courseId = (int)($result['id'] ?? 0);
            $file = $_FILES['document_file'] ?? [];
            $hasFile = isset($file['error']) && (int)$file['error'] !== UPLOAD_ERR_NO_FILE;
            if ($courseId > 0 && $hasFile) {
                $uploadResult = $documentService->upload([
                    'license_id'=>(int)$license['id'],
                    'renewal_cycle_id'=>(int)$data['renewal_cycle_id'],
                    'ce_course_id'=>$courseId,
                    'document_type'=>$documentData['document_type'],
                    'title'=>$documentData['title'] !== '' ? $documentData['title'] : (string)$data['course_title'],
                    'notes'=>$documentData['notes'],
                ], $file);
                if ($uploadResult['errors']) {
                    $message = rawurlencode('CE course was saved, but the document upload failed: '.implode(' ', $uploadResult['errors']));
                    redirect('ce/edit.php?id='.$courseId.'&notice='.$message);
                }
            }
            redirect('ce/edit.php?id='.$courseId);
        }
    }
}
?>
<!doctype html>
<html>
<body>
<?php require __DIR__ . '/../_auth_nav.php'; ?>

<h1>Create CE Course</h1>
<?php foreach ($errors as $m): ?><p style="color:red"><?= e($m) ?></p><?php endforeach; ?>
<?php foreach ($warnings as $m): ?><p style="color:orange"><?= e($m) ?></p><?php endforeach; ?>

<form method="post" action="<?= e(app_base_path('ce/create.php')) ?>" enctype="multipart/form-data">
    <?= csrf_input() ?>

    <h2>Course Details</h2>
    <p><label>Course title<br><input name="course_title" value="<?= e((string)$data['course_title']) ?>" required></label></p>
    <p><label>Provider<br><input name="provider_name" value="<?= e((string)$data['provider_name']) ?>" required></label></p>
    <p><label>Date completed<br><input name="date_completed" type="date" value="<?= e((string)$data['date_completed']) ?>" required></label></p>
    <p><label>Hours / CE credits<br><input name="hours" type="number" step="0.25" min="0.25" value="<?= e((string)$data['hours']) ?>" required></label></p>
    <p><label>Renewal cycle<br><select name="renewal_cycle_id"><?php foreach($cycles as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$data['renewal_cycle_id']===(int)$c['id']?'selected':'' ?>><?= e($c['cycle_start'].' to '.$c['cycle_end']) ?></option><?php endforeach; ?></select></label></p>
    <p><label>Category<br><select name="category"><?php foreach(App\Services\CeCourseService::CATEGORIES as $v): ?><option value="<?= e($v) ?>" <?= $data['category']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></label></p>
    <p><label>Format<br><select name="format"><?php foreach(App\Services\CeCourseService::FORMATS as $v): ?><option value="<?= e($v) ?>" <?= $data['format']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></label></p>
    <p><label>Delivery mode<br><select name="delivery_mode"><?php foreach(App\Services\CeCourseService::DELIVERY_MODES as $v): ?><option value="<?= e($v) ?>" <?= $data['delivery_mode']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></label></p>
    <p><label>Approval source<br><select name="approval_source"><?php foreach(App\Services\CeCourseService::APPROVAL_SOURCES as $v): ?><option value="<?= e($v) ?>" <?= $data['approval_source']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></label></p>
    <p><label><input type="checkbox" name="counts_toward_cycle" value="1" <?= !empty($data['counts_toward_cycle'])?'checked':'' ?>> Counts toward renewal cycle</label></p>
    <p><label><input type="checkbox" name="is_professional_conference" value="1" <?= !empty($data['is_professional_conference'])?'checked':'' ?>> Professional conference</label></p>
    <p><label>Notes<br><textarea name="notes"><?= e((string)$data['notes']) ?></textarea></label></p>

    <h2>Certificate / Documentation</h2>
    <p>Optional. Upload the CE certificate now, or leave this blank and upload it later from the CE course edit page.</p>
    <p><label>Document type<br><select name="document_type"><?php foreach(App\Services\DocumentService::CERT_TYPES as $v): ?><option value="<?= e($v) ?>" <?= $documentData['document_type']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></label></p>
    <p><label>Document title<br><input name="document_title" value="<?= e((string)$documentData['title']) ?>" placeholder="Defaults to course title if blank"></label></p>
    <p><label>Certificate/document file<br><input type="file" name="document_file"></label></p>
    <p><label>Document notes<br><textarea name="document_notes"><?= e((string)$documentData['notes']) ?></textarea></label></p>

    <p><button type="submit">Save CE Course</button></p>
</form>

</body>
</html>
