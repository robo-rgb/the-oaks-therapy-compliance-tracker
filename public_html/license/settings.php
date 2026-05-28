<?php declare(strict_types=1);
require __DIR__.'/../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce(); start_secure_session();
$svc=new App\Services\ReminderService(); $emailSvc=new App\Services\EmailService();
$settings=$svc->getSettings(); $msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!verify_csrf_token($_POST['_csrf_token']??null)){$err='Invalid request token.';}
 else {
  if(isset($_POST['send_test_email'])){
    $to=trim((string)($settings['admin_recipient_email']??'')); if($to==='')$to=trim((string)($settings['licensee_recipient_email']??''));
    if(!filter_var($to,FILTER_VALIDATE_EMAIL)){$err='No valid recipient configured.';} else { $res=$emailSvc->sendTest($to); $msg=$res['ok']?'Test email sent.':'Test email failed.'; $svc->log('settings_test_email','test_email',0,0,$to,'SMTP Test Email',$res['ok']?'sent':'failed',$res['error'],null);} 
  } else {
    $data=[
      'app_name'=>trim((string)$_POST['app_name']),
      'business_name'=>trim((string)$_POST['business_name']),
      'admin_recipient_email'=>trim((string)$_POST['admin_recipient_email']),
      'licensee_recipient_email'=>trim((string)$_POST['licensee_recipient_email']),
      'reminder_schedule_enabled'=>isset($_POST['reminder_schedule_enabled'])?'1':'0',
      'monthly_summary_enabled'=>isset($_POST['monthly_summary_enabled'])?'1':'0',
      'reminder_days_before_deadline'=>trim((string)$_POST['reminder_days_before_deadline']),
      'sender_email'=>trim((string)$_POST['sender_email']),
    ];
    if($data['admin_recipient_email']!=='' && !filter_var($data['admin_recipient_email'],FILTER_VALIDATE_EMAIL)) $err='Admin email invalid.';
    if($data['licensee_recipient_email']!=='' && !filter_var($data['licensee_recipient_email'],FILTER_VALIDATE_EMAIL)) $err='Licensee email invalid.';
    $parsedDays=$svc->parseReminderDays($data['reminder_days_before_deadline']); if($parsedDays===[]) $err='Reminder days list invalid.';
    if(!$err){
      $pdo=App\Database\Connection::get();
      $st=$pdo->prepare('INSERT INTO app_settings (setting_key,setting_value) VALUES (:k,:v) ON CONFLICT(setting_key) DO UPDATE SET setting_value=:v');
      foreach($data as $k=>$v){$st->execute(['k'=>$k,'v'=>$v]);}
      $settings=$svc->getSettings();
      $msg='Settings saved.';
    }
  }
 }
}
?>
<!doctype html>
<html>
<body>
<?php require __DIR__ . '/_auth_nav.php'; ?>

<h1>Settings</h1><?php if($msg):?><p><?= e($msg) ?></p><?php endif; ?><?php if($err):?><p style="color:red"><?= e($err) ?></p><?php endif; ?>
<p>SMTP config present: <?= $emailSvc->smtpPresent()?'Yes':'No' ?> (password hidden)</p>
<form method="post"><?= csrf_input() ?>
<input name="app_name" value="<?= e((string)($settings['app_name']??'')) ?>" placeholder="App name"><br>
<input name="business_name" value="<?= e((string)($settings['business_name']??'')) ?>" placeholder="Business name"><br>
<input name="admin_recipient_email" value="<?= e((string)($settings['admin_recipient_email']??'')) ?>" placeholder="Admin recipient email"><br>
<input name="licensee_recipient_email" value="<?= e((string)($settings['licensee_recipient_email']??'')) ?>" placeholder="Licensee recipient email"><br>
<input name="sender_email" value="<?= e((string)($settings['sender_email']??'')) ?>" placeholder="Sender display email"><br>
<label><input type="checkbox" name="reminder_schedule_enabled" value="1" <?= (($settings['reminder_schedule_enabled']??'1')==='1')?'checked':'' ?>> Reminder schedule enabled</label><br>
<label><input type="checkbox" name="monthly_summary_enabled" value="1" <?= (($settings['monthly_summary_enabled']??'1')==='1')?'checked':'' ?>> Monthly summary enabled</label><br>
<input name="reminder_days_before_deadline" value="<?= e((string)($settings['reminder_days_before_deadline']??'')) ?>"><br>
<button type="submit">Save Settings</button></form>
<form method="post"><?= csrf_input() ?><button type="submit" name="send_test_email" value="1">Send test email</button></form>
</body></html>
