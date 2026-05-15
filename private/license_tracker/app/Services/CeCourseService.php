<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;

final class CeCourseService
{
    public const CATEGORIES = ['ethics','core','related','independent_study','academic_coursework','conference'];
    public const FORMATS = ['in_person','live_webinar','video_conference','self_paced_online','reading_independent_study','academic_semester_course','academic_quarter_course','conference_session'];
    public const DELIVERY_MODES = ['synchronous','asynchronous'];
    public const APPROVAL_SOURCES = ['professional_association','academic_department','licensing_certification_board','government_agency','licensed_hospital','other'];

    public function defaults(?int $licenseId = null, ?int $cycleId = null): array
    {
        return [
            'license_id' => $licenseId,
            'renewal_cycle_id' => $cycleId,
            'course_title' => '',
            'provider_name' => '',
            'date_completed' => '',
            'hours' => '',
            'category' => 'core',
            'format' => 'in_person',
            'delivery_mode' => 'synchronous',
            'approval_source' => 'professional_association',
            'counts_toward_cycle' => 1,
            'is_professional_conference' => 0,
            'notes' => '',
        ];
    }

    public function list(array $filters): array
    {
        $sql = 'SELECT * FROM ce_courses WHERE license_id = :license_id';
        $params = ['license_id' => (int)$filters['license_id']];

        if (!empty($filters['renewal_cycle_id'])) {
            $sql .= ' AND renewal_cycle_id = :renewal_cycle_id';
            $params['renewal_cycle_id'] = (int)$filters['renewal_cycle_id'];
        }
        if (!empty($filters['category'])) {
            $sql .= ' AND category = :category';
            $params['category'] = (string)$filters['category'];
        }
        if (!empty($filters['delivery_mode'])) {
            $sql .= ' AND delivery_mode = :delivery_mode';
            $params['delivery_mode'] = (string)$filters['delivery_mode'];
        }
        if (!empty($filters['q'])) {
            $sql .= ' AND (course_title LIKE :q OR provider_name LIKE :q)';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $sql .= ' ORDER BY date_completed DESC, id DESC';
        $stmt = Connection::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $id, int $licenseId): ?array
    {
        $stmt = Connection::get()->prepare('SELECT * FROM ce_courses WHERE id = :id AND license_id = :license_id LIMIT 1');
        $stmt->execute(['id' => $id, 'license_id' => $licenseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): array
    {
        [$errors, $warnings] = $this->validate($data);
        if ($errors) {
            return ['errors' => $errors, 'warnings' => $warnings];
        }

        $stmt = Connection::get()->prepare('INSERT INTO ce_courses (license_id, renewal_cycle_id, course_title, provider_name, date_completed, hours, category, format, delivery_mode, approval_source, counts_toward_cycle, is_professional_conference, notes, created_at) VALUES (:license_id,:renewal_cycle_id,:course_title,:provider_name,:date_completed,:hours,:category,:format,:delivery_mode,:approval_source,:counts_toward_cycle,:is_professional_conference,:notes,CURRENT_TIMESTAMP)');
        $stmt->execute($this->payload($data));

        return ['errors' => [], 'warnings' => $warnings];
    }

    public function update(int $id, int $licenseId, array $data): array
    {
        [$errors, $warnings] = $this->validate($data);
        if ($errors) {
            return ['errors' => $errors, 'warnings' => $warnings];
        }

        $stmt = Connection::get()->prepare('UPDATE ce_courses SET renewal_cycle_id=:renewal_cycle_id, course_title=:course_title, provider_name=:provider_name, date_completed=:date_completed, hours=:hours, category=:category, format=:format, delivery_mode=:delivery_mode, approval_source=:approval_source, counts_toward_cycle=:counts_toward_cycle, is_professional_conference=:is_professional_conference, notes=:notes WHERE id=:id AND license_id=:license_id');
        $stmt->execute($this->payload($data) + ['id' => $id, 'license_id' => $licenseId]);

        return ['errors' => [], 'warnings' => $warnings];
    }

    public function delete(int $id, int $licenseId): void
    {
        $stmt = Connection::get()->prepare('DELETE FROM ce_courses WHERE id = :id AND license_id = :license_id');
        $stmt->execute(['id' => $id, 'license_id' => $licenseId]);
    }

    public function summaryTotals(int $licenseId, int $cycleId): array
    {
        $stmt = Connection::get()->prepare('SELECT 
            COALESCE(SUM(hours),0) AS total_hours,
            COALESCE(SUM(CASE WHEN category = "ethics" THEN hours ELSE 0 END),0) AS ethics_hours,
            COALESCE(SUM(CASE WHEN category = "core" THEN hours ELSE 0 END),0) AS core_hours,
            COALESCE(SUM(CASE WHEN category = "related" THEN hours ELSE 0 END),0) AS related_hours,
            COALESCE(SUM(CASE WHEN delivery_mode = "asynchronous" THEN hours ELSE 0 END),0) AS asynchronous_hours,
            COALESCE(SUM(CASE WHEN category = "independent_study" THEN hours ELSE 0 END),0) AS independent_study_hours
            FROM ce_courses
            WHERE license_id = :license_id AND renewal_cycle_id = :renewal_cycle_id AND counts_toward_cycle = 1');
        $stmt->execute(['license_id' => $licenseId, 'renewal_cycle_id' => $cycleId]);
        return $stmt->fetch() ?: [];
    }

    public function validate(array $data): array
    {
        $errors = [];
        $warnings = [];

        if (trim((string)($data['course_title'] ?? '')) === '') $errors[] = 'course_title is required.';
        if (trim((string)($data['provider_name'] ?? '')) === '') $errors[] = 'provider_name is required.';
        if (!$this->validDate((string)($data['date_completed'] ?? ''))) $errors[] = 'date_completed must be YYYY-MM-DD.';

        $hours = (float)($data['hours'] ?? 0);
        if ($hours <= 0) $errors[] = 'hours must be greater than 0.';

        if (!in_array((string)($data['category'] ?? ''), self::CATEGORIES, true)) $errors[] = 'category is invalid.';
        if (!in_array((string)($data['format'] ?? ''), self::FORMATS, true)) $errors[] = 'format is invalid.';
        if (!in_array((string)($data['delivery_mode'] ?? ''), self::DELIVERY_MODES, true)) $errors[] = 'delivery_mode is invalid.';

        if (($data['category'] ?? '') === 'ethics' && ($data['delivery_mode'] ?? '') !== 'synchronous') $errors[] = 'Ethics must be synchronous.';

        if (($data['category'] ?? '') === 'independent_study' && ($data['format'] ?? '') !== 'reading_independent_study') {
            $warnings[] = 'independent_study is typically reading_independent_study.';
        }

        if ($hours > 20 && empty($data['is_professional_conference'])) {
            $warnings[] = 'Course over 20 hours without professional conference flag.';
        }

        if (!empty($data['renewal_cycle_id']) && !empty($data['date_completed']) && !empty($data['counts_toward_cycle'])) {
            $cycle = (new RenewalCycleService())->get((int)$data['renewal_cycle_id'], (int)$data['license_id']);
            if ($cycle && ((string)$data['date_completed'] < (string)$cycle['cycle_start'] || (string)$data['date_completed'] > (string)$cycle['cycle_end'])) {
                $warnings[] = 'date_completed is outside selected renewal cycle.';
            }
        }

        return [$errors, $warnings];
    }

    private function payload(array $data): array
    {
        return [
            'license_id' => (int)$data['license_id'],
            'renewal_cycle_id' => (int)$data['renewal_cycle_id'],
            'course_title' => trim((string)$data['course_title']),
            'provider_name' => trim((string)$data['provider_name']),
            'date_completed' => (string)$data['date_completed'],
            'hours' => (float)$data['hours'],
            'category' => (string)$data['category'],
            'format' => (string)$data['format'],
            'delivery_mode' => (string)$data['delivery_mode'],
            'approval_source' => (string)$data['approval_source'],
            'counts_toward_cycle' => !empty($data['counts_toward_cycle']) ? 1 : 0,
            'is_professional_conference' => !empty($data['is_professional_conference']) ? 1 : 0,
            'notes' => trim((string)($data['notes'] ?? '')),
        ];
    }

    private function validDate(string $value): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return $d && $d->format('Y-m-d') === $value;
    }
}
