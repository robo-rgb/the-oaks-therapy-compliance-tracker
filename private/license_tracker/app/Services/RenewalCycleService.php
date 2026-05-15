<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;

final class RenewalCycleService
{
    public function listByLicense(int $licenseId): array
    {
        $stmt = Connection::get()->prepare('SELECT * FROM renewal_cycles WHERE license_id = :license_id ORDER BY cycle_start DESC');
        $stmt->execute(['license_id' => $licenseId]);
        return $stmt->fetchAll();
    }

    public function get(int $id, int $licenseId): ?array
    {
        $stmt = Connection::get()->prepare('SELECT * FROM renewal_cycles WHERE id = :id AND license_id = :license_id LIMIT 1');
        $stmt->execute(['id' => $id, 'license_id' => $licenseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function save(int $licenseId, array $data, ?int $id = null): array
    {
        $errors = $this->validate($data);
        if ($errors) {
            return $errors;
        }

        $pdo = Connection::get();
        $active = !empty($data['is_active']) ? 1 : 0;

        $payload = [
            'license_id' => $licenseId,
            'cycle_start' => (string) $data['cycle_start'],
            'cycle_end' => (string) $data['cycle_end'],
            'renewal_deadline' => (string) $data['renewal_deadline'],
            'late_renewal_deadline' => (string) $data['late_renewal_deadline'],
            'is_active' => $active,
            'renewal_submitted' => !empty($data['renewal_submitted']) ? 1 : 0,
            'renewal_submitted_date' => !empty($data['renewal_submitted']) ? (string) $data['renewal_submitted_date'] : null,
            'renewal_fee_paid' => !empty($data['renewal_fee_paid']) ? 1 : 0,
            'renewal_fee_paid_date' => !empty($data['renewal_fee_paid']) ? (string) $data['renewal_fee_paid_date'] : null,
            'status' => $active === 1 ? 'open' : 'closed',
        ];

        try {
            $pdo->beginTransaction();

            if ($active === 1) {
                $stmt = $pdo->prepare('UPDATE renewal_cycles SET is_active = 0 WHERE license_id = :license_id');
                $stmt->execute(['license_id' => $licenseId]);
            }

            if ($id) {
                $stmt = $pdo->prepare('UPDATE renewal_cycles SET cycle_start=:cycle_start, cycle_end=:cycle_end, renewal_deadline=:renewal_deadline, late_renewal_deadline=:late_renewal_deadline, is_active=:is_active, renewal_submitted=:renewal_submitted, renewal_submitted_date=:renewal_submitted_date, renewal_fee_paid=:renewal_fee_paid, renewal_fee_paid_date=:renewal_fee_paid_date, status=:status WHERE id=:id AND license_id=:license_id');
                $stmt->execute($payload + ['id' => $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO renewal_cycles (license_id, cycle_start, cycle_end, renewal_deadline, late_renewal_deadline, is_active, renewal_submitted, renewal_submitted_date, renewal_fee_paid, renewal_fee_paid_date, status) VALUES (:license_id,:cycle_start,:cycle_end,:renewal_deadline,:late_renewal_deadline,:is_active,:renewal_submitted,:renewal_submitted_date,:renewal_fee_paid,:renewal_fee_paid_date,:status)');
                $stmt->execute($payload);
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }

        return [];
    }

    public function getActive(int $licenseId): ?array
    {
        $stmt = Connection::get()->prepare('SELECT * FROM renewal_cycles WHERE license_id=:license_id AND is_active = 1 LIMIT 1');
        $stmt->execute(['license_id' => $licenseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function validate(array $d): array
    {
        $errors = [];
        foreach (['cycle_start', 'cycle_end', 'renewal_deadline', 'late_renewal_deadline'] as $field) {
            if (empty($d[$field]) || !$this->validDate((string) $d[$field])) {
                $errors[] = "$field must be YYYY-MM-DD.";
            }
        }

        if (!$errors) {
            if ((string) $d['cycle_start'] >= (string) $d['cycle_end']) {
                $errors[] = 'cycle_start must be before cycle_end.';
            }
            if ((string) $d['late_renewal_deadline'] <= (string) $d['renewal_deadline']) {
                $errors[] = 'late_renewal_deadline must be after renewal_deadline.';
            }
        }

        if (!empty($d['renewal_submitted']) && (empty($d['renewal_submitted_date']) || !$this->validDate((string) $d['renewal_submitted_date']))) {
            $errors[] = 'renewal_submitted_date required when renewal_submitted is checked.';
        }
        if (!empty($d['renewal_fee_paid']) && (empty($d['renewal_fee_paid_date']) || !$this->validDate((string) $d['renewal_fee_paid_date']))) {
            $errors[] = 'renewal_fee_paid_date required when renewal_fee_paid is checked.';
        }

        return $errors;
    }

    private function validDate(string $value): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $value);
        return $dt && $dt->format('Y-m-d') === $value;
    }
}
