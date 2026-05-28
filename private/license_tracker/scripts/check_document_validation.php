<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$svc = new App\Services\DocumentService();

$tmp = tempnam(sys_get_temp_dir(), 'doc');
file_put_contents($tmp, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");

$baseFile = [
    'name' => 'ok.pdf',
    'tmp_name' => $tmp,
    'size' => filesize($tmp),
    'error' => UPLOAD_ERR_OK,
];

$standaloneData = [
    'license_id' => 1,
    'renewal_cycle_id' => 1,
    'document_type' => 'renewal_confirmation',
    'title' => 'Renewal confirmation',
];

$certData = [
    'license_id' => 1,
    'renewal_cycle_id' => 1,
    'ce_course_id' => 1,
    'document_type' => 'ce_certificate',
    'title' => 'CE certificate',
];

$certDataMissingCourse = [
    'license_id' => 1,
    'renewal_cycle_id' => 1,
    'document_type' => 'ce_certificate',
    'title' => 'CE certificate missing course',
];

[$errors, , $meta] = $svc->validateUpload($standaloneData, $baseFile);
if ($errors) {
    fwrite(STDERR, "allowed standalone pdf failed: " . implode('; ', $errors) . "\n");
    unlink($tmp);
    exit(1);
}

[$errors] = $svc->validateUpload($certData, $baseFile);
if ($errors) {
    fwrite(STDERR, "allowed certificate pdf with CE course failed: " . implode('; ', $errors) . "\n");
    unlink($tmp);
    exit(1);
}

[$errors] = $svc->validateUpload($certDataMissingCourse, $baseFile);
if (!$errors) {
    fwrite(STDERR, "ce_certificate without ce_course_id should fail\n");
    unlink($tmp);
    exit(1);
}

foreach (['transcript', 'independent_study_affidavit'] as $type) {
    $missingCourseData = [
        'license_id' => 1,
        'renewal_cycle_id' => 1,
        'document_type' => $type,
        'title' => $type . ' missing course',
    ];

    [$errors] = $svc->validateUpload($missingCourseData, $baseFile);

    if (!$errors) {
        fwrite(STDERR, $type . " without ce_course_id should fail\n");
        unlink($tmp);
        exit(1);
    }
}

foreach (['renewal_confirmation', 'payment_confirmation', 'board_correspondence', 'other'] as $type) {
    $nonCertData = [
        'license_id' => 1,
        'renewal_cycle_id' => 1,
        'document_type' => $type,
        'title' => $type . ' document',
    ];

    [$errors] = $svc->validateUpload($nonCertData, $baseFile);

    if ($errors) {
        fwrite(STDERR, $type . " should not require ce_course_id: " . implode('; ', $errors) . "\n");
        unlink($tmp);
        exit(1);
    }
}

$file = $baseFile;
$file['name'] = 'bad.php';
[$errors] = $svc->validateUpload($standaloneData, $file);
if (!$errors) {
    fwrite(STDERR, "php should fail\n");
    unlink($tmp);
    exit(1);
}

$file['name'] = 'bad.html';
[$errors] = $svc->validateUpload($standaloneData, $file);
if (!$errors) {
    fwrite(STDERR, "html should fail\n");
    unlink($tmp);
    exit(1);
}

$file['name'] = 'bad.svg';
[$errors] = $svc->validateUpload($standaloneData, $file);
if (!$errors) {
    fwrite(STDERR, "svg should fail\n");
    unlink($tmp);
    exit(1);
}

$file['name'] = 'bad.js';
[$errors] = $svc->validateUpload($standaloneData, $file);
if (!$errors) {
    fwrite(STDERR, "js should fail\n");
    unlink($tmp);
    exit(1);
}

$file['name'] = 'bad.sh';
[$errors] = $svc->validateUpload($standaloneData, $file);
if (!$errors) {
    fwrite(STDERR, "sh should fail\n");
    unlink($tmp);
    exit(1);
}

$file['name'] = 'bad.zip';
[$errors] = $svc->validateUpload($standaloneData, $file);
if (!$errors) {
    fwrite(STDERR, "zip should fail\n");
    unlink($tmp);
    exit(1);
}

$file = $baseFile;
$file['name'] = 'big.pdf';
$file['size'] = App\Services\DocumentService::MAX_SIZE + 1;
[$errors] = $svc->validateUpload($standaloneData, $file);
if (!$errors) {
    fwrite(STDERR, "oversize should fail\n");
    unlink($tmp);
    exit(1);
}

$invalidTypeData = $standaloneData;
$invalidTypeData['document_type'] = 'nope';
[$errors] = $svc->validateUpload($invalidTypeData, $baseFile);
if (!$errors) {
    fwrite(STDERR, "invalid type should fail\n");
    unlink($tmp);
    exit(1);
}

if (!in_array('ce_certificate', App\Services\DocumentService::CERT_TYPES, true)) {
    fwrite(STDERR, "cert types set\n");
    unlink($tmp);
    exit(1);
}

if (!preg_match('/^[a-f0-9]{32}\.[a-z0-9]+$/', $meta['stored_filename'])) {
    fwrite(STDERR, "unsafe stored filename\n");
    unlink($tmp);
    exit(1);
}

unlink($tmp);

echo "Document validation check passed\n";