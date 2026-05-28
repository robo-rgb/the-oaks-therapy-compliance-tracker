<?php
declare(strict_types=1);
require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce(); start_secure_session();
$userId=(int)$_SESSION['user_id'];
$license=(new App\Services\LicenseService())->getByUserId($userId); if(!$license){redirect('license_edit.php');}
$cycles=(new App\Services\RenewalCycleService())->listByLicense((int)$license['id']);
$cycleId=(int)($_GET['renewal_cycle_id']??0); $type=trim((string)($_GET['document_type']??''));
$svc=new App\Services\DocumentService();
$rows=$svc->list((int)$license['id'],$cycleId?:null,$type?:null);
?>
<!doctype html><html><body>
<?php require __DIR__ . '/_auth_nav.php'; ?>
<h1>Documents</h1>
<p><a href="<?= e(app_base_path('document_upload.php')) ?>">Upload Document</a></p>
<form method="get"><select name="document_type"><option value="">All types</option><?php foreach(App\Services\DocumentService::TYPES as $v): ?><option value="<?= e($v) ?>" <?= $type===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select>
<select name="renewal_cycle_id"><option value="">All cycles</option><?php foreach($cycles as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cycleId===(int)$c['id']?'selected':'' ?>><?= e($c['cycle_start'].' to '.$c['cycle_end']) ?></option><?php endforeach; ?></select><button>Filter</button></form>
<table border="1"><tr><th>Title</th><th>Type</th><th>Associated CE</th><th>Renewal cycle</th><th>Upload date</th><th>Original filename</th><th>File size</th><th>Actions</th></tr>
<?php foreach($rows as $r): ?><tr><td><?= e((string)$r['title']) ?></td><td><?= e((string)$r['document_type']) ?></td><td><?= e((string)($r['course_title']??'')) ?></td><td><?= e((string)($r['renewal_cycle_id']??'')) ?></td><td><?= e((string)$r['uploaded_at']) ?></td><td><?= e((string)$r['original_filename']) ?></td><td><?= e((string)$r['file_size']) ?></td><td><a href="<?= e(app_base_path('download.php')) . '?id=' . (int)$r['id'] ?>">Download</a> <form
    method="post"
    action="<?= e(app_base_path('document_delete.php')) . '?id=' . (int)$r['id'] ?>"
    style="display:inline"
    onsubmit="return confirm('Are you sure you want to delete this document? This cannot be undone.');"
>
    <?= csrf_input() ?>
    <button type="submit">Delete</button>
</form></td></tr><?php endforeach; ?></table>
</body></html>
