<?php
declare(strict_types=1);
require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce();
start_secure_session();
$userId = (int)$_SESSION['user_id'];
$service = new App\Services\LicenseService();
$license = $service->getByUserId($userId);
$errors = [];
$data = $license ?: ['licensee_first_name'=>'','licensee_last_name'=>'','license_type'=>'CSW','state'=>'GA','license_number'=>'','original_issue_date'=>'','status'=>'active','notes'=>''];
if ($_SERVER['REQUEST_METHOD']==='POST') {
 if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) { $errors[]='Invalid request token.'; }
 else {
   $data = array_merge($data, $_POST);
   $errors = $service->save($userId, $data, $license['id'] ?? null);
   if (!$errors) redirect('license.php');
 }
}
?>
<!doctype html><html><body><h1><?= $license?'Edit':'Create' ?> License Profile</h1>
<?php foreach($errors as $e1): ?><p style="color:red"><?= e($e1) ?></p><?php endforeach; ?>
<form method="post" action="<?= e(app_base_path('license_edit.php')) ?>"><?= csrf_input() ?>
<input name="licensee_first_name" value="<?= e($data['licensee_first_name']) ?>" placeholder="First name" required><br>
<input name="licensee_last_name" value="<?= e($data['licensee_last_name']) ?>" placeholder="Last name" required><br>
<input name="license_type" value="<?= e($data['license_type']) ?>" required><br>
<input name="state" value="<?= e($data['state']) ?>" required><br>
<input name="license_number" value="<?= e($data['license_number']) ?>" required><br>
<input name="original_issue_date" value="<?= e((string)$data['original_issue_date']) ?>" placeholder="YYYY-MM-DD"><br>
<input name="status" value="<?= e($data['status']) ?>" required><br>
<textarea name="notes"><?= e((string)$data['notes']) ?></textarea><br><button>Save</button></form>
</body></html>
