<?php
declare(strict_types=1);
require __DIR__ . '/../../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce(); start_secure_session();
$userId=(int)$_SESSION['user_id'];
$licenseService=new App\Services\LicenseService(); $cycleService=new App\Services\RenewalCycleService(); $ceService=new App\Services\CeCourseService();
$license=$licenseService->getByUserId($userId); if(!$license){redirect('license_edit.php');}
$id=(int)($_GET['id']??0); $row=$ceService->getById($id,(int)$license['id']); if(!$row){redirect('ce/index.php');}
$cycles=$cycleService->listByLicense((int)$license['id']);
$data=$row; $errors=[]; $warnings=[];
$docSvc=new App\Services\DocumentService();
$docRows=$docSvc->list((int)$license['id'], null, null);
$ceDocs=array_values(array_filter($docRows, fn($d)=>(int)($d['ce_course_id']??0)===$id));

if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!verify_csrf_token($_POST['_csrf_token']??null)){$errors[]='Invalid request token.';} else {
  $data=array_merge($data,$_POST,['license_id'=>(int)$license['id']]);
  $result=$ceService->update($id,(int)$license['id'],$data); $errors=$result['errors']; $warnings=$result['warnings'];
  if(!$errors){redirect('ce/index.php');}
 }
}
?>
<!doctype html><html><body><h1>Edit CE Course</h1>
<?php foreach($errors as $m):?><p style="color:red"><?= e($m) ?></p><?php endforeach; ?>
<?php foreach($warnings as $m):?><p style="color:orange"><?= e($m) ?></p><?php endforeach; ?>
<form method="post" action="<?= e(app_base_path('ce/edit.php')) . '?id=' . $id ?>"><?= csrf_input() ?>
<input name="course_title" value="<?= e((string)$data['course_title']) ?>" required><br>
<input name="provider_name" value="<?= e((string)$data['provider_name']) ?>" required><br>
<input name="date_completed" value="<?= e((string)$data['date_completed']) ?>" required><br>
<input name="hours" type="number" step="0.25" value="<?= e((string)$data['hours']) ?>" required><br>
<select name="renewal_cycle_id"><?php foreach($cycles as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$data['renewal_cycle_id']===(int)$c['id']?'selected':'' ?>><?= e($c['cycle_start'].' to '.$c['cycle_end']) ?></option><?php endforeach; ?></select><br>
<select name="category"><?php foreach(App\Services\CeCourseService::CATEGORIES as $v): ?><option value="<?= e($v) ?>" <?= $data['category']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select><br>
<select name="format"><?php foreach(App\Services\CeCourseService::FORMATS as $v): ?><option value="<?= e($v) ?>" <?= $data['format']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select><br>
<select name="delivery_mode"><?php foreach(App\Services\CeCourseService::DELIVERY_MODES as $v): ?><option value="<?= e($v) ?>" <?= $data['delivery_mode']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select><br>
<select name="approval_source"><?php foreach(App\Services\CeCourseService::APPROVAL_SOURCES as $v): ?><option value="<?= e($v) ?>" <?= $data['approval_source']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select><br>
<label><input type="checkbox" name="counts_toward_cycle" value="1" <?= !empty($data['counts_toward_cycle'])?'checked':'' ?>> Counts toward cycle</label><br>
<label><input type="checkbox" name="is_professional_conference" value="1" <?= !empty($data['is_professional_conference'])?'checked':'' ?>> Professional conference</label><br>
<textarea name="notes"><?= e((string)$data['notes']) ?></textarea><br><button>Save</button></form><h2>Attached Documents</h2><?php foreach($ceDocs as $d): ?><div><?= e((string)$d['title']) ?> (<?= e((string)$d['document_type']) ?>) - <a href="<?= e(app_base_path('download.php')) . "?id=" . (int)$d['id'] ?>">Download</a></div><?php endforeach; ?><p><a href="<?= e(app_base_path('document_upload.php')) ?>?ce_course_id=<?= $id ?>">Upload CE Document</a></p></body></html>
