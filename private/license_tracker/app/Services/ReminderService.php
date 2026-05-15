<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;

final class ReminderService
{
    public function parseReminderDays(string $csv): array
    {
        $parts = explode(',', $csv);
        $days=[];
        foreach($parts as $p){
            $p=trim($p);
            if($p==='') return [];
            if(!preg_match('/^\d+$/',$p)) return [];
            $n=(int)$p;
            if($n<0 || $n>3650) return [];
            if(in_array($n,$days,true)) continue;
            $days[]=$n;
        }
        return $days;
    }

    public function getSettings(): array
    {
        $rows = Connection::get()->query('SELECT setting_key, setting_value FROM app_settings')->fetchAll();
        $out=[]; foreach($rows as $r){$out[$r['setting_key']]=$r['setting_value'];}
        return $out;
    }

    public function getPrimaryContext(): array
    {
        $license = Connection::get()->query('SELECT * FROM licenses ORDER BY id ASC LIMIT 1')->fetch();
        if(!$license) return [null,null];
        $cycle=(new RenewalCycleService())->getActive((int)$license['id']);
        return [$license?:null,$cycle?:null];
    }

    public function recipients(array $settings): array
    {
        $candidates=[trim((string)($settings['admin_recipient_email']??'')),trim((string)($settings['licensee_recipient_email']??''))];
        $out=[];
        foreach($candidates as $c){ if($c!=='' && filter_var($c,FILTER_VALIDATE_EMAIL)) $out[$c]=$c; }
        return array_values($out);
    }

    public function dueKeys(array $cycle, array $settings, ?\DateTimeImmutable $today=null): array
    {
        $today = $today ?: new \DateTimeImmutable('today');
        $keys=[];
        $deadline = new \DateTimeImmutable((string)$cycle['renewal_deadline']);
        $daysInt = (int)$today->diff($deadline)->format('%r%a');
        foreach($this->parseReminderDays((string)($settings['reminder_days_before_deadline'] ?? '')) as $d){
            if($daysInt === $d) $keys[] = 'deadline_'.$d;
        }
        if(($settings['monthly_summary_enabled'] ?? '0')==='1' && $today->format('d')==='01') $keys[]='monthly_summary';
        $year=(int)$deadline->format('Y');
        if($year%2===0 && (int)$today->format('Y')===$year && (int)$today->format('m')===10 && in_array((int)$today->format('d'), [1,15,30], true)) $keys[]='late_renewal_'.(int)$today->format('d');
        return $keys;
    }

    public function duplicateExists(string $key, int $licenseId, int $cycleId, string $recipient, string $date): bool
    {
        $st=Connection::get()->prepare('SELECT id FROM reminder_logs WHERE reminder_key=:k AND license_id=:l AND renewal_cycle_id=:c AND recipient_email=:r AND sent_date=:d LIMIT 1');
        $st->execute(['k'=>$key,'l'=>$licenseId,'c'=>$cycleId,'r'=>$recipient,'d'=>$date]);
        return (bool)$st->fetch();
    }

    public function log(string $key, string $type, int $licenseId, int $cycleId, string $recipient, string $subject, string $status, ?string $message, ?string $deadline): void
    {
        $st=Connection::get()->prepare('INSERT OR IGNORE INTO reminder_logs (reminder_key, reminder_type, license_id, renewal_cycle_id, recipient_email, subject, status, response_message, related_deadline, sent_date, attempted_at) VALUES (:k,:t,:l,:c,:r,:s,:st,:m,:rd,:sd,CURRENT_TIMESTAMP)');
        $st->execute(['k'=>$key,'t'=>$type,'l'=>$licenseId,'c'=>$cycleId,'r'=>$recipient,'s'=>$subject,'st'=>$status,'m'=>$message,'rd'=>$deadline,'sd'=>date('Y-m-d')]);
    }

    public function buildDeficitSummary(array $comp, array $cycle): string
    {
        $lines=[];
        if($comp['total_hours']<35)$lines[]='Total CE shortfall: '.(35-$comp['total_hours']);
        if($comp['ethics_hours']<5)$lines[]='Ethics shortfall: '.(5-$comp['ethics_hours']);
        if($comp['core_hours']<15)$lines[]='Core shortfall: '.(15-$comp['core_hours']);
        if($comp['related_hours']>15)$lines[]='Related over limit: '.($comp['related_hours']-15);
        if($comp['asynchronous_hours']>10)$lines[]='Asynchronous over limit: '.($comp['asynchronous_hours']-10);
        if($comp['independent_study_hours']>5)$lines[]='Independent study over limit: '.($comp['independent_study_hours']-5);
        if($comp['missing_document_count']>0)$lines[]='Missing certificates: '.$comp['missing_document_count'];
        if((int)$cycle['renewal_submitted']!==1)$lines[]='Renewal not marked submitted';
        if((int)$cycle['renewal_fee_paid']!==1)$lines[]='Renewal fee not marked paid';
        return implode("\n", $lines);
    }
}
