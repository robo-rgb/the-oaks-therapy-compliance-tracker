<?php
declare(strict_types=1);
require __DIR__ . '/../../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce();
start_secure_session();
$userId=(int)$_SESSION['user_id'];
$licenseService=new App\Services\LicenseService();
$cycleService=new App\Services\RenewalCycleService();
$ceService=new App\Services\CeCourseService();
$license=$licenseService->getByUserId($userId);
if(!$license){$noContext='No active license. ';}
$activeCycle=$license?$cycleService->getActive((int)$license['id']):null;
if($license && !$activeCycle){$noContext='No active renewal cycle. ';}
$filters=[
 'license_id'=>(int)($license['id']??0),
 'renewal_cycle_id'=>(int)($_GET['renewal_cycle_id']??($activeCycle['id']??0)),
 'category'=>trim((string)($_GET['category']??'')),
 'delivery_mode'=>trim((string)($_GET['delivery_mode']??'')),
 'q'=>trim((string)($_GET['q']??'')),
];
$courses=($license&&$activeCycle)?$ceService->list($filters):[];
$docService=new App\Services\DocumentService();
$cycles=$license?$cycleService->listByLicense((int)$license['id']):[];
$cycleById=[]; foreach($cycles as $cc){$cycleById[(int)$cc['id']]=$cc;}
?>
<!doctype html><html><body>
<nav><a href="<?= e(app_base_path('dashboard.php')) ?>">Dashboard</a> | <a href="<?= e(app_base_path('license.php')) ?>">License Profile</a> | <a href="<?= e(app_base_path('cycles.php')) ?>">Renewal Cycles</a> | <a href="<?= e(app_base_path('ce/index.php')) ?>">CE Courses</a> | <a href="<?= e(app_base_path('reports/index.php')) ?>">Reports</a> | <a href="<?= e(app_base_path('settings.php')) ?>">Settings</a> | <a href="<?= e(app_base_path('reminders_log.php')) ?>">Reminder Log</a> | <a href="<?= e(app_base_path('documents.php')) ?>">Documents</a>
<form style="display:inline" method="post" action="<?= e(app_base_path('logout.php')) ?>"><?= csrf_input() ?><button>Logout</button></form></nav>
<h1>CE Courses</h1>
<?php if(!empty($noContext)): ?><p><?= e($noContext) ?><a href="<?= e(app_base_path('license_edit.php')) ?>">Set up now</a></p><?php else: ?>
<p><a href="<?= e(app_base_path('ce/create.php')) ?>">Add CE Course</a></p>
<form method="get">
<select name="renewal_cycle_id"><option value="">All cycles</option><?php foreach($cycles as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$filters['renewal_cycle_id']===(int)$c['id']?'selected':'' ?>><?= e($c['cycle_start'].' to '.$c['cycle_end']) ?></option><?php endforeach; ?></select>
<select name="category"><option value="">All categories</option><?php foreach(App\Services\CeCourseService::CATEGORIES as $v): ?><option value="<?= e($v) ?>" <?= $filters['category']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select>
<select name="delivery_mode"><option value="">All modes</option><?php foreach(App\Services\CeCourseService::DELIVERY_MODES as $v): ?><option value="<?= e($v) ?>" <?= $filters['delivery_mode']===$v?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select>
<input name="q" value="<?= e($filters['q']) ?>" placeholder="Search title/provider"><button>Filter</button></form>
<table border="1"><tr><th>Date completed</th><th>Course title</th><th>Provider</th><th>Hours</th><th>Category</th><th>Format</th><th>Delivery mode</th><th>Counts toward cycle</th><th>Document status</th><th>Issues</th><th>Actions</th></tr>
<?php foreach($courses as $row): ?><tr>
<td><?= e((string)$row['date_completed']) ?></td><td><?= e((string)$row['course_title']) ?></td><td><?= e((string)$row['provider_name']) ?></td><td><?= e((string)$row['hours']) ?></td>
<td><?= e((string)$row['category']) ?></td><td><?= e((string)$row['format']) ?></td><td><?= e((string)$row['delivery_mode']) ?></td>
<td><?= (int)$row['counts_toward_cycle']===1?'Yes':'No' ?></td>
<td><?= $docService->hasCertificateForCourse((int)$row['id'], (int)$license['id'], (int)($row['renewal_cycle_id']??0))?'Present':'Missing' ?></td>
<td><?php $issues=[]; if((string)$row['category']==='ethics' && (string)$row['delivery_mode']!=='synchronous')$issues[]='Ethics async'; if((float)$row['hours']>20 && (int)$row['is_professional_conference']!==1)$issues[]='Over 20h'; if((int)$row['counts_toward_cycle']===1 && isset($cycleById[(int)$row['renewal_cycle_id']])){ $cy=$cycleById[(int)$row['renewal_cycle_id']]; if((string)$row['date_completed']<(string)$cy['cycle_start'] || (string)$row['date_completed']>(string)$cy['cycle_end'])$issues[]='Outside cycle'; } if(!$docService->hasCertificateForCourse((int)$row['id'], (int)$license['id'], (int)$row['renewal_cycle_id']))$issues[]='Missing cert'; echo e(implode(', ', $issues)); ?></td>
<td><a href="<?= e(app_base_path('ce/edit.php')) . '?id=' . (int)$row['id'] ?>">Edit</a>
<form method="post" action="<?= e(app_base_path('ce/delete.php')) . '?id=' . (int)$row['id'] ?>" style="display:inline"><?= csrf_input() ?><button>Delete</button></form></td>
</tr><?php endforeach; ?></table>
<?php if(!$courses): ?><p>No CE courses found.</p><?php endif; ?>
<?php endif; ?>
</body></html>
