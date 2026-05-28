<?php
declare(strict_types=1);
require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce(); start_secure_session();
$userId=(int)$_SESSION['user_id']; $ls=new App\Services\LicenseService(); $license=$ls->getByUserId($userId); if(!$license){redirect('license_edit.php');}
$id=(int)($_GET['id']??0); $svc=new App\Services\RenewalCycleService(); $cycle=$svc->get($id,(int)$license['id']); if(!$cycle){redirect('cycles.php');}
$data=$cycle; $errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!verify_csrf_token($_POST['_csrf_token']??null)){$errors[]='Invalid request token.';} else { $data=array_merge($data,$_POST); $errors=$svc->save((int)$license['id'],$data,$id); if(!$errors)redirect('cycles.php'); }
}
?>
<!doctype html>
<html>
<body>
<?php require __DIR__ . '/_auth_nav.php'; ?>

<h1><?= $license ? 'Edit' : 'Create' ?> Edit Cycle </h1><?php foreach($errors as $e1):?><p style="color:red"><?= e($e1) ?></p><?php endforeach;?><form method="post"><?= csrf_input() ?>
<input name="cycle_start" value="<?= e((string)$data['cycle_start']) ?>" required><br><input name="cycle_end" value="<?= e((string)$data['cycle_end']) ?>" required><br>
<input name="renewal_deadline" value="<?= e((string)$data['renewal_deadline']) ?>" required><br><input name="late_renewal_deadline" value="<?= e((string)$data['late_renewal_deadline']) ?>" required><br>
<label><input type="checkbox" name="is_active" value="1" <?= !empty($data['is_active'])?'checked':'' ?>> Active</label><br>
<label><input type="checkbox" name="renewal_submitted" value="1" <?= !empty($data['renewal_submitted'])?'checked':'' ?>> Renewal submitted</label><br><input name="renewal_submitted_date" value="<?= e((string)($data['renewal_submitted_date']??'')) ?>" placeholder="YYYY-MM-DD"><br>
<label><input type="checkbox" name="renewal_fee_paid" value="1" <?= !empty($data['renewal_fee_paid'])?'checked':'' ?>> Fee paid</label><br><input name="renewal_fee_paid_date" value="<?= e((string)($data['renewal_fee_paid_date']??'')) ?>" placeholder="YYYY-MM-DD"><br>
<button>Save</button></form></body></html>
