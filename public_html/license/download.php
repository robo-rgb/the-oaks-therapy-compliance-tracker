<?php
declare(strict_types=1);
require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce(); start_secure_session();
$userId=(int)$_SESSION['user_id']; $license=(new App\Services\LicenseService())->getByUserId($userId); if(!$license){http_response_code(404);exit;}
$doc=(new App\Services\DocumentService())->get((int)($_GET['id']??0),(int)$license['id']); if(!$doc){http_response_code(404);exit('Not found');}
$path=__DIR__.'/../../private/license_tracker/'.$doc['file_path']; if(!is_file($path)){http_response_code(404);exit('Not found');}
header('Content-Type: '.($doc['mime_type'] ?: 'application/octet-stream'));
header('Content-Length: '.filesize($path));
$safe=(new App\Services\DocumentService())->safeDownloadName((string)$doc['original_filename']);
header('Content-Disposition: attachment; filename="'.$safe.'"');
readfile($path); exit;
