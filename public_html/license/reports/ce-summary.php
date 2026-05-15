<?php declare(strict_types=1);
require __DIR__.'/../../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce(); start_secure_session();
$userId=(int)$_SESSION['user_id']; $license=(new App\Services\LicenseService())->getByUserId($userId); if(!$license){redirect('license_edit.php');}
$rep=new App\Services\ReportService(); $cycle=$rep->resolveCycleForLicense((int)$license['id'], (int)($_GET['cycle_id']??0)); $cycleId=(int)($cycle['id']??0); if($cycleId<1){redirect('reports/index.php');}
$svc=new App\Services\ReportService(); $r=$svc->build((int)$license['id'],$cycleId);
?><!doctype html><html><head><style>@media print{a{display:none}} body{font-family:Arial;font-size:13px} table{border-collapse:collapse;width:100%}th,td{border:1px solid #ccc;padding:4px}</style></head><body>
<h1>Georgia Clinical Social Worker Continuing Education Report</h1>
<p><?= e($r['app_name']) ?> | Generated: <?= e($r['generated_at']) ?></p>
<p><?= e(($r['license']['licensee_first_name']??'').' '.($r['license']['licensee_last_name']??'')) ?> | <?= e((string)$r['license']['license_type']) ?> | <?= e((string)$r['license']['license_number']) ?> | <?= e((string)$r['license']['state']) ?></p>
<p>Cycle: <?= e((string)$r['cycle']['cycle_start']) ?> to <?= e((string)$r['cycle']['cycle_end']) ?> | Renewal deadline: <?= e((string)$r['cycle']['renewal_deadline']) ?> | Late: <?= e((string)$r['cycle']['late_renewal_deadline']) ?></p>
<p><strong>Overall compliance: <?= !empty($r['compliance']['compliant'])?'Compliant':'Noncompliant' ?></strong></p>
<table><tr><th>Total CE</th><th>Ethics</th><th>Core</th><th>Related</th><th>Asynchronous</th><th>Independent Study</th><th>Missing Certificates</th></tr>
<tr><td><?= e((string)$r['compliance']['total_hours']) ?></td><td><?= e((string)$r['compliance']['ethics_hours']) ?></td><td><?= e((string)$r['compliance']['core_hours']) ?></td><td><?= e((string)$r['compliance']['related_hours']) ?></td><td><?= e((string)$r['compliance']['asynchronous_hours']) ?></td><td><?= e((string)$r['compliance']['independent_study_hours']) ?></td><td><?= e((string)$r['compliance']['missing_document_count']) ?></td></tr></table>
<h3>Course Log</h3><table><tr><th>Date</th><th>Title</th><th>Provider</th><th>Hours</th><th>Category</th><th>Format</th><th>Delivery</th><th>Approval</th><th>Counts</th><th>Cert</th><th>Notes</th></tr>
<?php foreach($r['ce_courses'] as $c):?><tr><td><?= e((string)$c['date_completed']) ?></td><td><?= e((string)$c['course_title']) ?></td><td><?= e((string)$c['provider_name']) ?></td><td><?= e((string)$c['hours']) ?></td><td><?= e((string)$c['category']) ?></td><td><?= e((string)$c['format']) ?></td><td><?= e((string)$c['delivery_mode']) ?></td><td><?= e((string)$c['approval_source']) ?></td><td><?= (int)$c['counts_toward_cycle']===1?'Yes':'No' ?></td><td><?= !empty($r['certificate_status'][(int)$c['id']])?'Present':'Missing' ?></td><td><?= e((string)$c['notes']) ?></td></tr><?php endforeach;?></table>
<h3>Missing Documentation</h3><ul><?php foreach($r['missing_documents'] as $m):?><li><?= e($m['title'].' / '.$m['provider'].' / '.$m['date_completed']) ?></li><?php endforeach;?></ul>
<h3>Warnings</h3><ul><?php foreach($r['compliance']['warnings'] as $w):?><li><?= e((string)$w) ?></li><?php endforeach;?></ul>
<h3>Errors</h3><ul><?php foreach($r['compliance']['errors'] as $er):?><li><?= e((string)$er) ?></li><?php endforeach;?></ul>
<h3>Renewal Status</h3><p>Submitted: <?= (int)$r['cycle']['renewal_submitted']===1?'Yes':'No' ?> <?= e((string)($r['cycle']['renewal_submitted_date']??'')) ?></p><p>Fee paid: <?= (int)$r['cycle']['renewal_fee_paid']===1?'Yes':'No' ?> <?= e((string)($r['cycle']['renewal_fee_paid_date']??'')) ?></p>
</body></html>
