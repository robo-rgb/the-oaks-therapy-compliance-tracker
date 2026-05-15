<?php
declare(strict_types=1);
require __DIR__ . '/../../private/license_tracker/vendor/autoload.php';
App\Middleware\RequireAuth::enforce(); start_secure_session();
if($_SERVER['REQUEST_METHOD']!=='POST' || !verify_csrf_token($_POST['_csrf_token']??null)){http_response_code(405);exit('Invalid request.');}
$userId=(int)$_SESSION['user_id']; $license=(new App\Services\LicenseService())->getByUserId($userId); if(!$license){redirect('license_edit.php');}
$ok=(new App\Services\DocumentService())->delete((int)($_GET['id']??0),(int)$license['id']);
if(!$ok){http_response_code(400);exit('Unable to delete document.');}
redirect('documents.php');
