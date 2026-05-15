<?php

declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

$svc = new App\Services\ReminderService();
$cycle=['renewal_deadline'=>'2026-09-30','renewal_submitted'=>0,'renewal_fee_paid'=>0];
$settings=['reminder_days_before_deadline'=>'180,120,90,60,30,14,7,1,0','monthly_summary_enabled'=>'1'];
$due=$svc->dueKeys($cycle,$settings,new DateTimeImmutable('2026-07-02')); if(!in_array('deadline_90',$due,true)){fwrite(STDERR,'90 due fail\n');exit(1);} 
$due=$svc->dueKeys($cycle,$settings,new DateTimeImmutable('2026-07-03')); if(in_array('deadline_90',$due,true)){fwrite(STDERR,'89 false positive\n');exit(1);} 
$due=$svc->dueKeys($cycle,$settings,new DateTimeImmutable('2026-08-01')); if(!in_array('monthly_summary',$due,true)){fwrite(STDERR,'monthly first day fail\n');exit(1);} 
$due=$svc->dueKeys($cycle,$settings,new DateTimeImmutable('2026-08-02')); if(in_array('monthly_summary',$due,true)){fwrite(STDERR,'monthly non-first day fail\n');exit(1);} 
foreach(['2026-10-01','2026-10-15','2026-10-30'] as $d){$due=$svc->dueKeys($cycle,$settings,new DateTimeImmutable($d)); if(!array_filter($due,fn($k)=>str_starts_with($k,'late_renewal_'))){fwrite(STDERR,'late renewal fail\n');exit(1);} }
if($svc->parseReminderDays('90,abc')!==[]){fwrite(STDERR,'day parse fail\n');exit(1);} if($svc->parseReminderDays('90,,30')!==[]){fwrite(STDERR,'day malformed fail\n');exit(1);} if($svc->parseReminderDays('-1,30')!==[]){fwrite(STDERR,'day negative fail\n');exit(1);} 
$comp=['total_hours'=>30,'ethics_hours'=>2,'core_hours'=>10,'related_hours'=>16,'asynchronous_hours'=>12,'independent_study_hours'=>6,'missing_document_count'=>3];
$rec=$svc->recipients(['admin_recipient_email'=>'a@example.com','licensee_recipient_email'=>'a@example.com']); if(count($rec)!==1){fwrite(STDERR,'recipient dedupe fail\n');exit(1);}
$txt=$svc->buildDeficitSummary($comp,$cycle);
if(strpos($txt,'Ethics shortfall')===false || strpos($txt,'Missing certificates')===false){fwrite(STDERR,'deficit summary fail\n');exit(1);} 
$es = new App\Services\EmailService(); $res=$es->send('invalid','x','x'); if(($res['error']??'')!==null && stripos((string)$res['error'],'password')!==false){fwrite(STDERR,'password leakage fail\n');exit(1);} 

echo "Reminder logic check passed\n";
