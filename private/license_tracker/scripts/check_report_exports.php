<?php

declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

$svc = new App\Services\ReportService();
$d = ['cycle'=>['cycle_start'=>'2024-10-01','cycle_end'=>'2026-09-30'],'license'=>['licensee_first_name'=>'Maylin','licensee_last_name'=>'Last/name']];
if (str_starts_with($svc->csvSafe('=1+1'), '=')) { fwrite(STDERR,"csv sanitize fail\n"); exit(1);} 
if ($svc->safeFile('a/../../b') !== 'a_.._.._b') { fwrite(STDERR,"safe filename fail\n"); exit(1);} 
if (!str_ends_with($svc->auditZipName($d), '.zip')) { fwrite(STDERR,"zip name fail\n"); exit(1);} 
if (!str_ends_with($svc->reportPdfName($d), '.pdf')) { fwrite(STDERR,"pdf name fail\n"); exit(1);} 

use App\Database\Connection;
$pdo = Connection::get();
$pdo->exec('DELETE FROM documents');$pdo->exec('DELETE FROM ce_courses');$pdo->exec('DELETE FROM renewal_cycles');$pdo->exec('DELETE FROM licenses');$pdo->exec('DELETE FROM users');
$pdo->exec("INSERT INTO users (id,email,full_name,password_hash,role,is_active) VALUES (1,'r@example.com','R','x','admin',1)");
$pdo->exec("INSERT INTO licenses (id,user_id,license_type,state,license_number,status,licensee_first_name,licensee_last_name) VALUES (1,1,'CSW','GA','L1','active','A','B')");
$pdo->exec("INSERT INTO renewal_cycles (id,license_id,cycle_start,cycle_end,renewal_deadline,late_renewal_deadline,is_active,status,required_hours,ethics_required_hours) VALUES (1,1,'2024-10-01','2026-09-30','2026-09-30','2026-10-31',1,'open',35,5)");
$pdo->exec("INSERT INTO ce_courses (id,license_id,renewal_cycle_id,course_title,provider_name,date_completed,hours,category,format,delivery_mode,approval_source,counts_toward_cycle,is_professional_conference,notes) VALUES (1,1,1,'Core','=Danger','2025-01-01',35,'core','in_person','synchronous','other',1,0,'')");
$r = $svc->build(1,1);
if (!isset($r['compliance']) || !isset($r['missing_documents'])) { fwrite(STDERR, "build fail\n"); exit(1);} 

echo "Report export check passed\n";
