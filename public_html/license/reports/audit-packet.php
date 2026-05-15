<?php declare(strict_types=1);
require __DIR__.'/../../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce(); start_secure_session();
$userId=(int)$_SESSION['user_id']; $license=(new App\Services\LicenseService())->getByUserId($userId); if(!$license){http_response_code(404);exit;}
$rep=new App\Services\ReportService(); $cycle=$rep->resolveCycleForLicense((int)$license['id'], (int)($_GET['cycle_id']??0)); $cycleId=(int)($cycle['id']??0); if($cycleId<1){http_response_code(400);exit('No cycle');}
$svc=new App\Services\ReportService(); $r=$svc->build((int)$license['id'],$cycleId);
ob_start(); include __DIR__.'/ce-summary.php'; $html=ob_get_clean(); $pdf=$svc->pdfBytes($html);
if(!class_exists('ZipArchive')){http_response_code(500);exit('ZIP export is unavailable.');}
$tmp=tempnam(sys_get_temp_dir(),'audit'); $zip=new ZipArchive(); $zip->open($tmp, ZipArchive::OVERWRITE);
$root='GA_CSW_Audit_Packet_'.$svc->cycleLabel((array)$r['cycle']).'/';
$zip->addFromString($root.$svc->reportPdfName($r),$pdf);
$missing=[];
foreach($r['ce_courses'] as $c){
  if(empty($r['certificate_status'][(int)$c['id']])) continue;
}
foreach($r['renewal_documents'] as $d){
  $path=$svc->absoluteDocumentPath((string)$d['file_path']);
  $name=$svc->safeFile((string)$d['original_filename']);
  if($path && is_file($path)) $zip->addFile($path,$root.'renewal/'.$name); else $missing[]=$name;
}
$allDocs=(new App\Services\DocumentService())->list((int)$license['id'],$cycleId,null);
foreach($allDocs as $d){
 if(!in_array((string)$d['document_type'], App\Services\DocumentService::CERT_TYPES,true)) continue;
 $path=$svc->absoluteDocumentPath((string)$d['file_path']); $name=$svc->safeFile((string)$d['original_filename']);
 if(is_file($path)) $zip->addFile($path,$root.'certificates/'.$name); else $missing[]=$name;
}
if($missing) $zip->addFromString($root.'MISSING_FILES.txt',implode("\n",$missing));
$zip->close();
header('Content-Type: application/zip'); header('Content-Disposition: attachment; filename="'.$svc->auditZipName($r).'"'); readfile($tmp); unlink($tmp); exit;
