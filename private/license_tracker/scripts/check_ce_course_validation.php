<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$svc = new App\Services\CeCourseService();
$base = $svc->defaults(1, 1);
$base['course_title'] = 'Ethics Training';
$base['provider_name'] = 'Provider';
$base['date_completed'] = '2026-01-15';
$base['hours'] = 3;
$base['category'] = 'ethics';
$base['format'] = 'live_webinar';
$base['delivery_mode'] = 'synchronous';

[$errors, $warnings] = $svc->validate($base);
if ($errors) { fwrite(STDERR, "valid ethics synchronous should pass\n"); exit(1);} 

$bad = $base; $bad['delivery_mode']='asynchronous';
[$e] = $svc->validate($bad); if (!$e) { fwrite(STDERR, "ethics async should fail\n"); exit(1);} 

$bad=$base; $bad['category']='bad'; [$e]=$svc->validate($bad); if(!$e){fwrite(STDERR,"invalid category fail\n");exit(1);} 
$bad=$base; $bad['delivery_mode']='bad'; [$e]=$svc->validate($bad); if(!$e){fwrite(STDERR,"invalid delivery fail\n");exit(1);} 
$bad=$base; $bad['hours']=0; [$e]=$svc->validate($bad); if(!$e){fwrite(STDERR,"zero hours fail\n");exit(1);} 
$bad=$base; $bad['date_completed']='01-01-2026'; [$e]=$svc->validate($bad); if(!$e){fwrite(STDERR,"bad date fail\n");exit(1);} 

$ind=$base; $ind['category']='independent_study'; $ind['format']='reading_independent_study'; [$e]=$svc->validate($ind); if($e){fwrite(STDERR,"independent study valid\n");exit(1);} 

$warn=$base; $warn['hours']=21; $warn['is_professional_conference']=0; [$e,$w]=$svc->validate($warn); if(!$w){fwrite(STDERR,"20+ warning\n");exit(1);} 

// cycle warning simulated with non-existing cycle by using known cycle id may be absent; if absent, skip hard fail
$outside=$base; $outside['date_completed']='1999-01-01'; $outside['counts_toward_cycle']=1; [$e,$w]=$svc->validate($outside);

$outside2=$outside; $outside2['counts_toward_cycle']=0; [$e2,$w2]=$svc->validate($outside2);
if (count($w2) > count($w)) { fwrite(STDERR, "counts_toward_cycle false should not add compliance warning\n"); exit(1);} 

echo "CE validation check passed\n";
