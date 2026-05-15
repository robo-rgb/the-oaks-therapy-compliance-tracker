<?php

declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';

$rem=new App\Services\ReminderService(); $settings=$rem->getSettings();
if(($settings['reminder_schedule_enabled']??'1')!=='1'){echo "Reminder schedule disabled\n"; exit(0);} 
[$license,$cycle]=$rem->getPrimaryContext();
if(!$license){echo "No license found\n"; exit(0);} 
if(!$cycle){echo "No active cycle\n"; exit(0);} 
$comp=(new App\Services\ComplianceCalculator())->evaluate((int)$license['id'], (int)$cycle['id']);
$due=$rem->dueKeys($cycle,$settings);
$recipients=$rem->recipients($settings);
if(!$recipients){echo "No valid recipients\n"; exit(0);} 
$mail=new App\Services\EmailService(); $sent=0; $skipped=0;
foreach($due as $key){
 foreach($recipients as $to){
  if($rem->duplicateExists($key,(int)$license['id'],(int)$cycle['id'],$to,date('Y-m-d'))){$skipped++; continue;}
  $subject='Compliance Reminder: '.$key;
  $body="Reminder key: {$key}\n\n".$rem->buildDeficitSummary($comp,$cycle);
  $res=$mail->send($to,$subject,$body);
  $rem->log($key, str_starts_with($key,'deadline_')?'deadline':(str_starts_with($key,'late_renewal_')?'late_renewal':'monthly_summary'), (int)$license['id'], (int)$cycle['id'], $to, $subject, $res['ok']?'sent':'failed', $res['error'], (string)$cycle['renewal_deadline']);
  if($res['ok'])$sent++;
 }
}
echo "Due: ".count($due)." Recipients: ".count($recipients)." Sent: {$sent} Skipped duplicates: {$skipped}\n";
