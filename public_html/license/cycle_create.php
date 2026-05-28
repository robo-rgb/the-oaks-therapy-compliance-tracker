<?php
declare(strict_types=1);
require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce(); start_secure_session();
$userId=(int)$_SESSION['user_id']; $ls=new App\Services\LicenseService(); $license=$ls->getByUserId($userId); if(!$license){redirect('license_edit.php');}
$svc=new App\Services\RenewalCycleService();
$data=['cycle_start'=>'2024-10-01','cycle_end'=>'2026-09-30','renewal_deadline'=>'2026-09-30','late_renewal_deadline'=>'2026-10-31','is_active'=>1,'renewal_submitted'=>0,'renewal_submitted_date'=>'','renewal_fee_paid'=>0,'renewal_fee_paid_date'=>''];
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!verify_csrf_token($_POST['_csrf_token']??null)){$errors[]='Invalid request token.';} else { $data=array_merge($data,$_POST); $errors=$svc->save((int)$license['id'],$data,null); if(!$errors)redirect('cycles.php'); }
}
?>
<!doctype html>
<html>
<body>
<?php require __DIR__ . '/_auth_nav.php'; ?>

<h1><?= $license ? 'Edit' : 'Create' ?> Create Cycle </h1><?php foreach($errors as $e1):?><p style="color:red"><?= e($e1) ?></p><?php endforeach;?><form method="post"><?= csrf_input() ?>
<input name="cycle_start" value="<?= e($data['cycle_start']) ?>" required><br><input name="cycle_end" value="<?= e($data['cycle_end']) ?>" required><br>
<input name="renewal_deadline" value="<?= e($data['renewal_deadline']) ?>" required><br><input name="late_renewal_deadline" value="<?= e($data['late_renewal_deadline']) ?>" required><br>
<label><input type="checkbox" name="is_active" value="1" <?= !empty($data['is_active'])?'checked':'' ?>> Active</label><br>
<label><input type="checkbox" name="renewal_submitted" value="1" <?= !empty($data['renewal_submitted'])?'checked':'' ?>> Renewal submitted</label><br><input name="renewal_submitted_date" value="<?= e((string)$data['renewal_submitted_date']) ?>" placeholder="YYYY-MM-DD"><br>
<label><input type="checkbox" name="renewal_fee_paid" value="1" <?= !empty($data['renewal_fee_paid'])?'checked':'' ?>> Fee paid</label><br><input name="renewal_fee_paid_date" value="<?= e((string)$data['renewal_fee_paid_date']) ?>" placeholder="YYYY-MM-DD"><br>
<button>Save</button></form></body></html>
