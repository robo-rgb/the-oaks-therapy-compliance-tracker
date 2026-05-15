<?php declare(strict_types=1);
require __DIR__.'/../../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce(); start_secure_session();
$userId=(int)$_SESSION['user_id']; $license=(new App\Services\LicenseService())->getByUserId($userId); if(!$license){http_response_code(404);exit;}
$rep=new App\Services\ReportService(); $cycle=$rep->resolveCycleForLicense((int)$license['id'], (int)($_GET['cycle_id']??0)); $cycleId=(int)($cycle['id']??0); if($cycleId<1){http_response_code(400);exit('No cycle');}
$svc=new App\Services\ReportService(); $r=$svc->build((int)$license['id'],$cycleId);
header('Content-Type: text/csv; charset=UTF-8'); header('Content-Disposition: attachment; filename="ce_export_'.$svc->cycleLabel((array)$r['cycle']).'.csv"');
$out=fopen('php://output','w'); fputcsv($out,['Date completed','Course title','Provider','Hours','Category','Format','Delivery mode','Approval source','Counts toward cycle','Certificate attached','Notes']);
foreach($r['ce_courses'] as $c){
 $row=[(string)$c['date_completed'],(string)$c['course_title'],(string)$c['provider_name'],(string)$c['hours'],(string)$c['category'],(string)$c['format'],(string)$c['delivery_mode'],(string)$c['approval_source'],(int)$c['counts_toward_cycle']===1?'Yes':'No',!empty($r['certificate_status'][(int)$c['id']])?'Yes':'No',(string)$c['notes']];
 $row=array_map(fn($v)=>$svc->csvSafe((string)$v),$row); fputcsv($out,$row);
}
fclose($out); exit;
