<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;

final class DocumentService
{
    public const TYPES = ['ce_certificate','renewal_confirmation','payment_confirmation','transcript','independent_study_affidavit','board_correspondence','other'];
    public const CERT_TYPES = ['ce_certificate','transcript','independent_study_affidavit'];
    public const MAX_SIZE = 10485760;

    private const ALLOWED = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    ];

    public function list(int $licenseId, ?int $cycleId = null, ?string $type = null): array
    {
        $sql = 'SELECT d.*, c.course_title FROM documents d LEFT JOIN ce_courses c ON c.id=d.ce_course_id WHERE d.license_id=:license_id';
        $params=['license_id'=>$licenseId];
        if ($cycleId) { $sql .= ' AND d.renewal_cycle_id=:cycle_id'; $params['cycle_id']=$cycleId; }
        if ($type) { $sql .= ' AND d.document_type=:type'; $params['type']=$type; }
        $sql .= ' ORDER BY d.uploaded_at DESC, d.id DESC';
        $stmt=Connection::get()->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }

    public function get(int $id, int $licenseId): ?array
    {
        $stmt=Connection::get()->prepare('SELECT * FROM documents WHERE id=:id AND license_id=:license_id LIMIT 1');
        $stmt->execute(['id'=>$id,'license_id'=>$licenseId]);
        $r=$stmt->fetch(); return $r?:null;
    }

    public function upload(array $data, array $file): array
    {
        [$errors,$warnings,$meta]=$this->validateUpload($data,$file);
        if($errors){return ['errors'=>$errors,'warnings'=>$warnings];}

        $uploadDir = __DIR__ . '/../../uploads';
        if(!is_dir($uploadDir)){mkdir($uploadDir,0750,true);}        
        $stored=$meta['stored_filename'];
        $target=$uploadDir.'/'.$stored;
        if(!move_uploaded_file($file['tmp_name'],$target)){
            return ['errors'=>['Failed to save uploaded file.'],'warnings'=>$warnings];
        }

        $stmt=Connection::get()->prepare('INSERT INTO documents (license_id, renewal_cycle_id, ce_course_id, document_type, title, original_filename, stored_filename, file_path, mime_type, file_size, uploaded_at, notes) VALUES (:license_id,:renewal_cycle_id,:ce_course_id,:document_type,:title,:original_filename,:stored_filename,:file_path,:mime_type,:file_size,CURRENT_TIMESTAMP,:notes)');
        $stmt->execute([
            'license_id'=>(int)$data['license_id'],
            'renewal_cycle_id'=>!empty($data['renewal_cycle_id'])?(int)$data['renewal_cycle_id']:null,
            'ce_course_id'=>!empty($data['ce_course_id'])?(int)$data['ce_course_id']:null,
            'document_type'=>$data['document_type'],
            'title'=>trim((string)$data['title']),
            'original_filename'=>$meta['original_filename'],
            'stored_filename'=>$stored,
            'file_path'=>'uploads/'.$stored,
            'mime_type'=>$meta['mime_type'],
            'file_size'=>$meta['file_size'],
            'notes'=>trim((string)($data['notes']??'')),
        ]);
        return ['errors'=>[],'warnings'=>$warnings];
    }

    public function delete(int $id, int $licenseId): bool
    {
        $doc=$this->get($id,$licenseId); if(!$doc){return false;}
        $path=__DIR__.'/../../'.$doc['file_path'];
        if (is_file($path) && !unlink($path)) {
            error_log('Failed to delete document file: '.$path);
            return false;
        }
        $stmt=Connection::get()->prepare('DELETE FROM documents WHERE id=:id AND license_id=:license_id');
        $stmt->execute(['id'=>$id,'license_id'=>$licenseId]);
        return true;
    }

    public function hasCertificateForCourse(int $ceCourseId, int $licenseId, ?int $cycleId = null): bool
    {
        $in = implode(',', array_fill(0, count(self::CERT_TYPES), '?'));
        $sql = "SELECT id FROM documents WHERE ce_course_id=? AND license_id=?";
        $params = [$ceCourseId, $licenseId];
        if ($cycleId) {
            $sql .= ' AND renewal_cycle_id=?';
            $params[] = $cycleId;
        }
        $sql .= " AND document_type IN ($in) LIMIT 1";
        $stmt = Connection::get()->prepare($sql);
        $stmt->execute(array_merge($params, self::CERT_TYPES));
        return (bool)$stmt->fetch();
    }

    public function validateUpload(array $data, array $file): array
    {
        $errors=[]; $warnings=[];
        if (!in_array((string)($data['document_type']??''), self::TYPES, true)) $errors[]='invalid document_type.';
        if (trim((string)($data['title']??''))==='') $errors[]='title is required.';
        if (empty($file['name']) || !isset($file['tmp_name'])) $errors[]='file is required.';

        $meta=['stored_filename'=>'','original_filename'=>'','mime_type'=>'','file_size'=>0];
        if(!$errors){
            $orig=(string)$file['name']; $size=(int)$file['size'];
            $ext=strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $mime=(string)(new \finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
            if (!isset(self::ALLOWED[$ext])) $errors[]='extension not allowed.';
            elseif (!in_array($mime, self::ALLOWED[$ext], true)) $errors[]='mime type mismatch.';
            if ($size > self::MAX_SIZE) $errors[]='file exceeds 10MB.';
            $stored = bin2hex(random_bytes(16)).'.'.$ext;
            $meta=['stored_filename'=>$stored,'original_filename'=>$orig,'mime_type'=>$mime,'file_size'=>$size];
        }

        return [$errors,$warnings,$meta];
    }

    public function safeDownloadName(string $originalFilename): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($originalFilename)) ?: 'document';
        return $name;
    }
}
