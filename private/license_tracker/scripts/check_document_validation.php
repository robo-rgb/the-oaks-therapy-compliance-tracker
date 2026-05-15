<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$svc = new App\Services\DocumentService();
$tmp = tempnam(sys_get_temp_dir(), 'doc');
file_put_contents($tmp, '%PDF-1.4 test');

$data = ['license_id'=>1,'document_type'=>'ce_certificate','title'=>'Cert'];
$file = ['name'=>'ok.pdf','tmp_name'=>$tmp,'size'=>filesize($tmp)];
[$e,, $meta] = $svc->validateUpload($data,$file);
if($e){fwrite(STDERR,"allowed pdf failed\n");exit(1);} 

$file['name']='bad.php'; [$e] = $svc->validateUpload($data,$file); if(!$e){fwrite(STDERR,"php should fail\n");exit(1);} 
$file['name']='bad.html'; [$e] = $svc->validateUpload($data,$file); if(!$e){fwrite(STDERR,"html should fail\n");exit(1);} 
$file['name']='bad.svg'; [$e] = $svc->validateUpload($data,$file); if(!$e){fwrite(STDERR,"svg should fail\n");exit(1);} 
$file['name']='bad.js'; [$e] = $svc->validateUpload($data,$file); if(!$e){fwrite(STDERR,"js should fail\n");exit(1);} 
$file['name']='bad.sh'; [$e] = $svc->validateUpload($data,$file); if(!$e){fwrite(STDERR,"sh should fail\n");exit(1);} 
$file['name']='bad.zip'; [$e] = $svc->validateUpload($data,$file); if(!$e){fwrite(STDERR,"zip should fail\n");exit(1);} 

$file['name']='big.pdf'; $file['size']=App\Services\DocumentService::MAX_SIZE+1; [$e] = $svc->validateUpload($data,$file); if(!$e){fwrite(STDERR,"oversize should fail\n");exit(1);} 
$data2=$data; $data2['document_type']='nope'; $file=['name'=>'ok.pdf','tmp_name'=>$tmp,'size'=>filesize($tmp)]; [$e] = $svc->validateUpload($data2,$file); if(!$e){fwrite(STDERR,"invalid type fail\n");exit(1);} 
if(!in_array('ce_certificate', App\Services\DocumentService::CERT_TYPES, true)){fwrite(STDERR,"cert types set\n");exit(1);} 
if(!preg_match('/^[a-f0-9]{32}\.[a-z0-9]+$/',$meta['stored_filename'])){fwrite(STDERR,"unsafe stored filename\n");exit(1);} 
unlink($tmp);
echo "Document validation check passed\n";
