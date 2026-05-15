<?php
declare(strict_types=1);
require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce(); start_secure_session();
$userId=(int)$_SESSION['user_id']; $license=(new App\Services\LicenseService())->getByUserId($userId); if(!$license){redirect('license_edit.php');}
$cycleSvc=new App\Services\RenewalCycleService(); $cycles=$cycleSvc->listByLicense((int)$license['id']); $active=$cycleSvc->getActive((int)$license['id']);
$ceRows=(new App\Services\CeCourseService())->list(['license_id'=>(int)$license['id'],'renewal_cycle_id'=>(int)($active['id']??0),'category'=>'','delivery_mode'=>'','q'=>'']);
$prefillCe=(int)($_GET['ce_course_id']??0);
$data=['license_id'=>(int)$license['id'],'renewal_cycle_id'=>(int)($active['id']??0),'ce_course_id'=>$prefillCe?:'','document_type'=>'ce_certificate','title'=>'','notes'=>'']; $errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!verify_csrf_token($_POST['_csrf_token']??null)){$errors[]='Invalid request token.';} else {
  $data=array_merge($data,$_POST,['license_id'=>(int)$license['id']]);
  $result=(new App\Services\DocumentService())->upload($data,$_FILES['document_file']??[]); $errors=$result['errors'];
  if(!$errors){redirect('documents.php');}
 }
}
?>
<!doctype html><html><body><h1>Upload Document</h1><?php foreach($errors as $e1):?><p style="color:red"><?= e($e1) ?></p><?php endforeach; ?>
<form method="post" enctype="multipart/form-data"><?= csrf_input() ?>
<select name="document_type"><?php foreach(App\Services\DocumentService::TYPES as $v): ?><option value="<?= e($v) ?>" <?= $data['document_type']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select><br>
<input name="title" value="<?= e((string)$data['title']) ?>" required placeholder="Title"><br>
<select name="renewal_cycle_id"><option value="">No cycle</option><?php foreach($cycles as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$data['renewal_cycle_id']===(int)$c['id']?'selected':'' ?>><?= e($c['cycle_start'].' to '.$c['cycle_end']) ?></option><?php endforeach; ?></select><br>
<select name="ce_course_id"><option value="">No CE course</option><?php foreach($ceRows as $ce): ?><option value="<?= (int)$ce['id'] ?>" <?= (int)$data['ce_course_id']===(int)$ce['id']?'selected':'' ?>><?= e($ce['course_title']) ?></option><?php endforeach; ?></select><br>
<input type="file" name="document_file" required><br><textarea name="notes"><?= e((string)$data['notes']) ?></textarea><br><button>Upload</button></form>
</body></html>
