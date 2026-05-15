<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;

final class ComplianceCalculator
{
    public function evaluate(int $licenseId, int $cycleId): array
    {
        $rules = ['total_min'=>35.0,'ethics_min'=>5.0,'core_min'=>15.0,'related_max'=>15.0,'async_max'=>10.0,'independent_max'=>5.0,'single_max'=>20.0];
        $stmt = Connection::get()->prepare('SELECT * FROM ce_courses WHERE license_id=:license_id AND renewal_cycle_id=:renewal_cycle_id AND counts_toward_cycle=1 ORDER BY date_completed DESC, id DESC');
        $stmt->execute(['license_id'=>$licenseId,'renewal_cycle_id'=>$cycleId]);
        $courses = $stmt->fetchAll();

        $total=$ethics=$core=$related=$async=$independent=0.0;
        $errors=[]; $warnings=[]; $missingDocs=[];
        $docSvc=new DocumentService();

        foreach($courses as $c){
            $hours=(float)$c['hours']; $total+=$hours;
            if((string)$c['delivery_mode']==='asynchronous') $async+=$hours;
            if((string)$c['category']==='core') $core+=$hours;
            if((string)$c['category']==='related') $related+=$hours;
            if((string)$c['category']==='independent_study') {
                $independent+=$hours;
                if((string)$c['format']!=='reading_independent_study') $errors[]='Independent study format mismatch: '.$c['course_title'];
            }
            if((string)$c['category']==='ethics') {
                if((string)$c['delivery_mode']!=='synchronous') $errors[]='Ethics course is asynchronous: '.$c['course_title'];
                else $ethics+=$hours;
            }
            if($hours>$rules['single_max'] && (int)$c['is_professional_conference']!==1) $errors[]='Single activity exceeds 20 hours without conference flag: '.$c['course_title'];

            if(!$docSvc->hasCertificateForCourse((int)$c['id'],$licenseId,$cycleId)) {
                $missingDocs[]=['id'=>(int)$c['id'],'title'=>(string)$c['course_title'],'provider'=>(string)$c['provider_name'],'date_completed'=>(string)$c['date_completed']];
            }
        }

        if($total<$rules['total_min']) $errors[]='Total hours below required minimum.';
        if($ethics<$rules['ethics_min']) $errors[]='Ethics hours below required minimum.';
        if($core<$rules['core_min']) $errors[]='Core hours below required minimum.';
        if($related>$rules['related_max']) $errors[]='Related hours exceed maximum.';
        if($async>$rules['async_max']) $errors[]='Asynchronous hours exceed maximum.';
        if($independent>$rules['independent_max']) $errors[]='Independent study hours exceed maximum.';
        if($missingDocs) $errors[]='Missing documentation for one or more courses.';
        if($ethics>$rules['ethics_min']) $warnings[]='Excess ethics-to-core assignment is not implemented in Phase 5.';

        $status=[
            'total'=>$this->statusCard($total,$rules['total_min'],'min'),
            'ethics'=>$this->statusCard($ethics,$rules['ethics_min'],'min'),
            'core'=>$this->statusCard($core,$rules['core_min'],'min'),
            'related'=>$this->statusCard($related,$rules['related_max'],'max'),
            'asynchronous'=>$this->statusCard($async,$rules['async_max'],'max'),
            'independent'=>$this->statusCard($independent,$rules['independent_max'],'max'),
            'missing_certificates'=>['state'=>$missingDocs?'red':'green','value'=>count($missingDocs)],
            'overall'=>['state'=>$errors?'red':($warnings?'yellow':'green')],
        ];

        return ['total_hours'=>$total,'ethics_hours'=>$ethics,'core_hours'=>$core,'related_hours'=>$related,'asynchronous_hours'=>$async,'independent_study_hours'=>$independent,'missing_document_count'=>count($missingDocs),'missing_documents'=>$missingDocs,'warnings'=>$warnings,'errors'=>$errors,'compliant'=>count($errors)===0,'requirement_status'=>$status,'courses'=>$courses];
    }

    private function statusCard(float $value,float $target,string $mode): array
    {
        $ok = $mode==='min' ? $value>=$target : $value<=$target;
        if($ok) return ['state'=>'green','value'=>$value,'target'=>$target,'mode'=>$mode];
        return ['state'=>($value==0.0?'gray':'red'),'value'=>$value,'target'=>$target,'mode'=>$mode];
    }
}
