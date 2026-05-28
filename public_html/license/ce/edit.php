<?php
declare(strict_types=1);

require __DIR__ . '/../../../private/license_tracker/vendor/autoload.php';

App\Middleware\RequireAuth::enforce();
start_secure_session();

$userId = (int) $_SESSION['user_id'];

$licenseService = new App\Services\LicenseService();
$cycleService = new App\Services\RenewalCycleService();
$ceService = new App\Services\CeCourseService();
$docSvc = new App\Services\DocumentService();

$license = $licenseService->getByUserId($userId);
if (!$license) { redirect('license_edit.php'); }

$id = (int)($_GET['id'] ?? 0);
$row = $ceService->getById($id, (int)$license['id']);
if (!$row) { redirect('ce/index.php'); }

$cycles = $cycleService->listByLicense((int)$license['id']);
$data = $row; $errors = []; $warnings = []; $notices = [];
if (!empty($_GET['notice'])) { $notices[] = (string)$_GET['notice']; }
$documentData = ['document_type'=>'ce_certificate','title'=>'','notes'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) { $errors[] = 'Invalid request token.'; }
    else {
        $data = array_merge($data, $_POST, ['license_id'=>(int)$license['id']]);
        $documentData = [
            'document_type'=>(string)($_POST['document_type'] ?? 'ce_certificate'),
            'title'=>trim((string)($_POST['document_title'] ?? '')),
            'notes'=>trim((string)($_POST['document_notes'] ?? '')),
        ];
        $result = $ceService->update($id, (int)$license['id'], $data); $errors = $result['errors']; $warnings = $result['warnings'];
        if (!$errors) {
            $file = $_FILES['document_file'] ?? [];
            $hasFile = isset($file['error']) && (int)$file['error'] !== UPLOAD_ERR_NO_FILE;
            if ($hasFile) {
                $uploadResult = $docSvc->upload([
                    'license_id'=>(int)$license['id'],
                    'renewal_cycle_id'=>(int)$data['renewal_cycle_id'],
                    'ce_course_id'=>$id,
                    'document_type'=>$documentData['document_type'],
                    'title'=>$documentData['title'] !== '' ? $documentData['title'] : (string)$data['course_title'],
                    'notes'=>$documentData['notes'],
                ], $file);
                if ($uploadResult['errors']) { $errors = array_merge($errors, $uploadResult['errors']); }
                else { $warnings = array_merge($warnings, $uploadResult['warnings']); redirect('ce/edit.php?id='.$id); }
            } else { redirect('ce/index.php'); }
        }
    }
}

$docRows = $docSvc->list((int)$license['id'], null, null);
$ceDocs = array_values(array_filter($docRows, fn($d)=>(int)($d['ce_course_id'] ?? 0) === $id));
?>
<!doctype html>
<html>
<body>
<?php require __DIR__ . '/../_auth_nav.php'; ?>

<h1>Edit CE Course</h1>
<?php foreach ($notices as $m): ?><p style="color:orange"><?= e($m) ?></p><?php endforeach; ?>
<?php foreach ($errors as $m): ?><p style="color:red"><?= e($m) ?></p><?php endforeach; ?>
<?php foreach ($warnings as $m): ?><p style="color:orange"><?= e($m) ?></p><?php endforeach; ?>

<form method="post" action="<?= e(app_base_path('ce/edit.php')) . '?id=' . (int)$id ?>" enctype="multipart/form-data">
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

    <h2>Add Certificate / Documentation</h2>
    <p>Optional. Upload a certificate or transcript for this CE course while saving changes.</p>
    <p><label>Document type<br><select name="document_type"><?php foreach(App\Services\DocumentService::CERT_TYPES as $v): ?><option value="<?= e($v) ?>" <?= $documentData['document_type']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></label></p>
    <p><label>Document title<br><input name="document_title" value="<?= e((string)$documentData['title']) ?>" placeholder="Defaults to course title if blank"></label></p>
    <p><label>Certificate/document file<br><input type="file" name="document_file"></label></p>
    <p><label>Document notes<br><textarea name="document_notes"><?= e((string)$documentData['notes']) ?></textarea></label></p>
    <p><button type="submit">Save</button></p>
</form>

<h2>Attached Documents</h2>
<?php if (!$ceDocs): ?><p>No documents attached to this CE course yet.</p><?php endif; ?>
<?php foreach($ceDocs as $d): ?>
<div>
    <?= e((string)$d['title']) ?> (<?= e((string)$d['document_type']) ?>) -
    <a href="<?= e(app_base_path('download.php')) . '?id=' . (int)$d['id'] ?>">Download</a>
    <form method="post" action="<?= e(app_base_path('document_delete.php')) . '?id=' . (int)$d['id'] ?>" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this document? This cannot be undone.');">
        <?= csrf_input() ?>
        <button type="submit">Delete</button>
    </form>
</div>
<?php endforeach; ?>

</body>
</html>
