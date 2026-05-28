<?php declare(strict_types=1);
require __DIR__.'/../../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce(); start_secure_session();
$userId=(int)$_SESSION['user_id']; $ls=new App\Services\LicenseService(); $rs=new App\Services\RenewalCycleService(); $rep=new App\Services\ReportService();
$license=$ls->getByUserId($userId); if(!$license){redirect('license_edit.php');}
$cycles=$rs->listByLicense((int)$license['id']); $resolved=$rep->resolveCycleForLicense((int)$license['id'], (int)($_GET['cycle_id']??0));
$cycleId=(int)($resolved['id']??0);
?>
<!doctype html>
<html>
<body>
<?php require __DIR__ . '/../_auth_nav.php'; ?>

<h1>Reports</h1>
<form method="get"><select name="cycle_id"><?php foreach($cycles as $c):?><option value="<?= (int)$c['id'] ?>" <?= $cycleId===(int)$c['id']?'selected':'' ?>><?= e($c['cycle_start'].' to '.$c['cycle_end']) ?></option><?php endforeach;?></select><button>Set Cycle</button></form>
<ul>
<li><a href="<?= e(app_base_path('reports/ce-summary.php')).'?cycle_id='.$cycleId ?>">Printable CE Summary HTML</a></li>
<li><a href="<?= e(app_base_path('reports/ce-summary-pdf.php')).'?cycle_id='.$cycleId ?>">PDF CE Report</a></li>
<li><a href="<?= e(app_base_path('reports/ce-export-csv.php')).'?cycle_id='.$cycleId ?>">CSV Export</a></li>
<li><a href="<?= e(app_base_path('reports/audit-packet.php')).'?cycle_id='.$cycleId ?>">Audit Packet ZIP</a></li>
</ul></body></html>
