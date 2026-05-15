<?php
declare(strict_types=1);
require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce();
start_secure_session();
$userId=(int)$_SESSION['user_id'];
$licenseService=new App\Services\LicenseService();
$cycleService=new App\Services\RenewalCycleService();
$ceService=new App\Services\CeCourseService();
$calculator=new App\Services\ComplianceCalculator();
$license=$licenseService->getByUserId($userId);
$activeCycle=$license ? $cycleService->getActive((int)$license['id']) : null;
$compliance=($license && $activeCycle)?$calculator->evaluate((int)$license['id'], (int)$activeCycle['id']):null;
$statusColor=['green'=>'#1f7a1f','yellow'=>'#b58900','red'=>'#b00020','gray'=>'#666'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Dashboard - The Oaks Compliance Tracker</title></head><body>
<nav><a href="<?= e(app_base_path('dashboard.php')) ?>">Dashboard</a> | <a href="<?= e(app_base_path('license.php')) ?>">License Profile</a> | <a href="<?= e(app_base_path('cycles.php')) ?>">Renewal Cycles</a> | <a href="<?= e(app_base_path('ce/index.php')) ?>">CE Courses</a> | <a href="<?= e(app_base_path('reports/index.php')) ?>">Reports</a> | <a href="<?= e(app_base_path('settings.php')) ?>">Settings</a> | <a href="<?= e(app_base_path('reminders_log.php')) ?>">Reminder Log</a> | <a href="<?= e(app_base_path('documents.php')) ?>">Documents</a>
<form style="display:inline" method="post" action="<?= e(app_base_path('logout.php')) ?>"><?= csrf_input() ?><button type="submit">Logout</button></form></nav>
<h1>Dashboard</h1>
<?php if(!$license): ?>
<p>No license exists. <a href="<?= e(app_base_path('license_edit.php')) ?>">Create license profile</a>.</p>
<?php else: ?>
<p>Active licensee name: <?= e(($license['licensee_first_name']??'').' '.($license['licensee_last_name']??'')) ?></p>
<p>License type: <?= e((string)$license['license_type']) ?></p>
<p>State: <?= e((string)($license['state']??'')) ?></p>
<p>License number: <?= e((string)($license['license_number']??'')) ?></p>
<p>License status: <?= e((string)$license['status']) ?></p>
<?php if(!$activeCycle): ?>
<p>No active renewal cycle. <a href="<?= e(app_base_path('cycle_create.php')) ?>">Create or activate one</a>.</p>
<?php else: ?>
<p>Active renewal cycle: <?= e((string)$activeCycle['cycle_start']) ?> to <?= e((string)$activeCycle['cycle_end']) ?></p>
<p>Renewal deadline: <?= e((string)$activeCycle['renewal_deadline']) ?></p>
<p>Late renewal deadline: <?= e((string)$activeCycle['late_renewal_deadline']) ?></p>
<p>Renewal submitted: <?= (int)$activeCycle['renewal_submitted']===1?'Yes':'No' ?></p>
<p>Renewal fee paid: <?= (int)$activeCycle['renewal_fee_paid']===1?'Yes':'No' ?></p>
<p style="color:<?= e($statusColor[$compliance['requirement_status']['total']['state']??'gray']) ?>">Total CE: <?= e((string)($compliance['total_hours'] ?? '0')) ?> / 35</p>
<p style="color:<?= e($statusColor[$compliance['requirement_status']['ethics']['state']??'gray']) ?>">Ethics: <?= e((string)($compliance['ethics_hours'] ?? '0')) ?> / 5 synchronous</p>
<p style="color:<?= e($statusColor[$compliance['requirement_status']['core']['state']??'gray']) ?>">Core: <?= e((string)($compliance['core_hours'] ?? '0')) ?> / 15 minimum</p>
<p style="color:<?= e($statusColor[$compliance['requirement_status']['related']['state']??'gray']) ?>">Related: <?= e((string)($compliance['related_hours'] ?? '0')) ?> / 15 maximum</p>
<p style="color:<?= e($statusColor[$compliance['requirement_status']['asynchronous']['state']??'gray']) ?>">Asynchronous: <?= e((string)($compliance['asynchronous_hours'] ?? '0')) ?> / 10 maximum</p>
<p style="color:<?= e($statusColor[$compliance['requirement_status']['independent']['state']??'gray']) ?>">Independent Study: <?= e((string)($compliance['independent_study_hours'] ?? '0')) ?> / 5 maximum</p><p>Missing Certificates: <?= e((string)($compliance['missing_document_count'] ?? 0)) ?></p><p style="color:<?= e($statusColor[$compliance['requirement_status']['overall']['state']??'gray']) ?>"><strong>Overall Compliance Status: <?= !empty($compliance['compliant'])?'Compliant':'Noncompliant' ?></strong></p><?php if(!empty($compliance['warnings'])): ?><h3>Warnings</h3><ul><?php foreach($compliance['warnings'] as $w): ?><li><?= e((string)$w) ?></li><?php endforeach; ?></ul><?php endif; ?><?php if(!empty($compliance['errors'])): ?><h3>Errors</h3><ul><?php foreach($compliance['errors'] as $er): ?><li><?= e((string)$er) ?></li><?php endforeach; ?></ul><?php endif; ?>
<?php endif; endif; ?>
</body></html>
