<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;

final class LicenseService
{
    public function getByUserId(int $userId): ?array
    {
        $stmt = Connection::get()->prepare('SELECT * FROM licenses WHERE user_id = :user_id ORDER BY id DESC LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function save(int $userId, array $data, ?int $licenseId = null): array
    {
        $errors = $this->validate($data);
        if ($errors) {
            return $errors;
        }

        $payload = [
            'user_id' => $userId,
            'licensee_first_name' => trim((string)$data['licensee_first_name']),
            'licensee_last_name' => trim((string)$data['licensee_last_name']),
            'license_type' => trim((string)$data['license_type']),
            'state' => trim((string)$data['state']),
            'license_number' => trim((string)$data['license_number']),
            'original_issue_date' => (string)$data['original_issue_date'] ?: null,
            'status' => trim((string)$data['status']),
            'notes' => trim((string)($data['notes'] ?? '')),
        ];

        $pdo = Connection::get();
        if ($licenseId) {
            $sql = 'UPDATE licenses SET licensee_first_name=:licensee_first_name, licensee_last_name=:licensee_last_name, license_type=:license_type, state=:state, license_number=:license_number, original_issue_date=:original_issue_date, status=:status, notes=:notes, updated_at=CURRENT_TIMESTAMP WHERE id=:id AND user_id=:user_id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($payload + ['id' => $licenseId]);
        } else {
            $sql = 'INSERT INTO licenses (user_id, licensee_first_name, licensee_last_name, license_type, state, license_number, original_issue_date, status, notes) VALUES (:user_id,:licensee_first_name,:licensee_last_name,:license_type,:state,:license_number,:original_issue_date,:status,:notes)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($payload);
        }

        return [];
    }

    private function validate(array $data): array
    {
        $errors = [];
        foreach (['licensee_first_name','licensee_last_name','license_type','state','license_number','status'] as $field) {
            if (trim((string)($data[$field] ?? '')) === '') {
                $errors[] = "{$field} is required.";
            }
        }
        if (!empty($data['original_issue_date']) && !$this->validDate((string)$data['original_issue_date'])) {
            $errors[] = 'original_issue_date must be YYYY-MM-DD.';
        }
        return $errors;
    }

    private function validDate(string $value): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return $d && $d->format('Y-m-d') === $value;
    }
}
