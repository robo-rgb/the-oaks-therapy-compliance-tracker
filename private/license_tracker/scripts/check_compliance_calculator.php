<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Database\Connection;

$pdo = Connection::get();
$pdo->exec('DELETE FROM documents');
$pdo->exec('DELETE FROM ce_courses');
$pdo->exec('DELETE FROM renewal_cycles');
$pdo->exec('DELETE FROM licenses');
$pdo->exec('DELETE FROM users');
$pdo->exec("INSERT INTO users (id,email,full_name,password_hash,role,is_active) VALUES (1,'t@example.com','T','x','admin',1)");
$pdo->exec("INSERT INTO licenses (id,user_id,license_type,state,license_number,status,licensee_first_name,licensee_last_name) VALUES (1,1,'CSW','GA','L1','active','A','B')");
$pdo->exec("INSERT INTO renewal_cycles (id,license_id,cycle_start,cycle_end,renewal_deadline,late_renewal_deadline,is_active,status,required_hours,ethics_required_hours) VALUES (1,1,'2024-10-01','2026-09-30','2026-09-30','2026-10-31',1,'open',35,5)");

$insertCourse=$pdo->prepare("INSERT INTO ce_courses (license_id,renewal_cycle_id,course_title,provider_name,date_completed,hours,category,format,delivery_mode,approval_source,counts_toward_cycle,is_professional_conference,notes) VALUES (1,1,:t,'P','2025-01-01',:h,:c,'in_person',:d,'other',:ct,:pc,'')");
$insertDoc=$pdo->prepare("INSERT INTO documents (license_id,renewal_cycle_id,ce_course_id,document_type,title,original_filename,stored_filename,file_path,storage_path,mime_type,file_size,uploaded_at,notes) VALUES (1,1,:ce,'ce_certificate','t','a.pdf','b.pdf','uploads/b.pdf','uploads/b.pdf','application/pdf',100,CURRENT_TIMESTAMP,'')");

$add=function($title,$h,$cat,$delivery='synchronous',$ct=1,$pc=0,$doc=true)use($insertCourse,$insertDoc,$pdo){
 $insertCourse->execute(['t'=>$title,'h'=>$h,'c'=>$cat,'d'=>$delivery,'ct'=>$ct,'pc'=>$pc]);
 $id=(int)$pdo->lastInsertId(); if($doc){$insertDoc->execute(['ce'=>$id]);}
};

// Test 1 compliant
$add('Ethics',5,'ethics'); $add('Core',15,'core'); $add('Related',15,'related');
$r=(new App\Services\ComplianceCalculator())->evaluate(1,1); if(!$r['compliant']){fwrite(STDERR,'test1 fail\n');exit(1);} 

$pdo->exec('DELETE FROM ce_courses'); $pdo->exec('DELETE FROM documents');
$add('Ethics bad',5,'ethics','asynchronous'); $add('Core',15,'core'); $add('Related',15,'related');
$r=(new App\Services\ComplianceCalculator())->evaluate(1,1); if($r['compliant']){fwrite(STDERR,'test2 fail\n');exit(1);} 

$pdo->exec('DELETE FROM ce_courses'); $pdo->exec('DELETE FROM documents');
$add('Ethics',5,'ethics'); $add('Core',10,'core'); $add('Related',20,'related');
$r=(new App\Services\ComplianceCalculator())->evaluate(1,1); if($r['compliant']){fwrite(STDERR,'test3/4 fail\n');exit(1);} 

$pdo->exec('DELETE FROM ce_courses'); $pdo->exec('DELETE FROM documents');
$add('Async core',12,'core','asynchronous'); $add('Core',23,'core');
$r=(new App\Services\ComplianceCalculator())->evaluate(1,1); if($r['compliant']){fwrite(STDERR,'test5/8 fail\n');exit(1);} 

$pdo->exec('DELETE FROM ce_courses'); $pdo->exec('DELETE FROM documents');
$add('Ind',6,'independent_study'); $add('Core',29,'core');
$r=(new App\Services\ComplianceCalculator())->evaluate(1,1); if($r['compliant']){fwrite(STDERR,'test6 fail\n');exit(1);} 

$pdo->exec('DELETE FROM ce_courses'); $pdo->exec('DELETE FROM documents');
$add('No doc',35,'core','synchronous',1,0,false);
$r=(new App\Services\ComplianceCalculator())->evaluate(1,1); if($r['missing_document_count']<1){fwrite(STDERR,'test9 fail\n');exit(1);} 

$pdo->exec('DELETE FROM ce_courses'); $pdo->exec('DELETE FROM documents');
$add('Counted',35,'core'); $add('Excluded',10,'core','synchronous',0);
$r=(new App\Services\ComplianceCalculator())->evaluate(1,1); if(abs($r['total_hours']-35)>0.01){fwrite(STDERR,'test10 fail\n');exit(1);} 


$pdo->exec("INSERT INTO renewal_cycles (id,license_id,cycle_start,cycle_end,renewal_deadline,late_renewal_deadline,is_active,status,required_hours,ethics_required_hours) VALUES (2,1,'2026-10-01','2028-09-30','2028-09-30','2028-10-31',0,'open',35,5)");
$pdo->exec('DELETE FROM ce_courses'); $pdo->exec('DELETE FROM documents');
$add('Cycle doc mismatch',35,'core','synchronous',1,0,false);
$ceId=(int)$pdo->query('SELECT id FROM ce_courses ORDER BY id DESC LIMIT 1')->fetchColumn();
$pdo->prepare("INSERT INTO documents (license_id,renewal_cycle_id,ce_course_id,document_type,title,original_filename,stored_filename,file_path,storage_path,mime_type,file_size,uploaded_at,notes) VALUES (1,2,:ce,'ce_certificate','x','x.pdf','x.pdf','uploads/x.pdf','uploads/x.pdf','application/pdf',10,CURRENT_TIMESTAMP,'')")->execute(['ce'=>$ceId]);
$r=(new App\Services\ComplianceCalculator())->evaluate(1,1); if($r['missing_document_count']<1){fwrite(STDERR,'test11 fail\n');exit(1);} 

echo "Compliance calculator check passed\n";
