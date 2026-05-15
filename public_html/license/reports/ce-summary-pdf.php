<?php declare(strict_types=1);
require __DIR__.'/../../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce(); start_secure_session();
$userId=(int)$_SESSION['user_id']; $license=(new App\Services\LicenseService())->getByUserId($userId); if(!$license){http_response_code(404);exit;}
$rep=new App\Services\ReportService(); $cycle=$rep->resolveCycleForLicense((int)$license['id'], (int)($_GET['cycle_id']??0)); $cycleId=(int)($cycle['id']??0); if($cycleId<1){http_response_code(400);exit('No cycle');}
$svc=new App\Services\ReportService(); $r=$svc->build((int)$license['id'],$cycleId);
ob_start(); include __DIR__.'/ce-summary.php'; $html=ob_get_clean();
try { $pdf=$svc->pdfBytes($html); } catch (\RuntimeException $e) { http_response_code(500); exit('PDF generation is unavailable.'); }
header('Content-Type: application/pdf'); header('Content-Disposition: attachment; filename="'.$svc->reportPdfName($r).'"'); echo $pdf; exit;
