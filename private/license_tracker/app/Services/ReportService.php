<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
use Dompdf\Dompdf;

final class ReportService
{
    public function resolveCycleForLicense(int $licenseId, ?int $cycleId): ?array
    {
        $cycleService = new RenewalCycleService();
        if ($cycleId && $cycleId > 0) {
            return $cycleService->get($cycleId, $licenseId);
        }
        return $cycleService->getActive($licenseId) ?: null;
    }

    public function build(int $licenseId, int $cycleId): array
    {
        $license = $this->getLicense($licenseId);
        $cycle = (new RenewalCycleService())->get($cycleId, $licenseId);
        if (!$license || !$cycle) {
            throw new \RuntimeException('Invalid report context.');
        }

        $compliance = (new ComplianceCalculator())->evaluate($licenseId, $cycleId);
        $ce = (new CeCourseService())->list(['license_id'=>$licenseId,'renewal_cycle_id'=>$cycleId,'category'=>'','delivery_mode'=>'','q'=>'']);
        $docSvc = new DocumentService();
        $docs = $docSvc->list($licenseId, $cycleId, null);

        $certStatus = [];
        foreach ($ce as $row) {
            $certStatus[(int)$row['id']] = $docSvc->hasCertificateForCourse((int)$row['id'], $licenseId, $cycleId);
        }

        $renewalDocs = array_values(array_filter($docs, fn($d) => in_array((string)$d['document_type'], ['renewal_confirmation','payment_confirmation','board_correspondence'], true)));

        return [
            'app_name' => (string) config('app.name', 'The Oaks Compliance Tracker'),
            'license' => $license,
            'cycle' => $cycle,
            'compliance' => $compliance,
            'ce_courses' => $ce,
            'certificate_status' => $certStatus,
            'missing_documents' => $compliance['missing_documents'],
            'renewal_documents' => $renewalDocs,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    public function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    public function safeFile(string $value): string
    {
        return trim((string)preg_replace('/[^A-Za-z0-9._-]+/', '_', $value), '_');
    }

    public function pdfBytes(string $html): string
    {
        if (!class_exists(Dompdf::class)) {
            throw new \RuntimeException('Dompdf is not available.');
        }
        $dompdf = new Dompdf(['isRemoteEnabled' => false]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    public function auditZipName(array $data): string
    {
        $cycleLabel = $this->cycleLabel((array)$data['cycle']);
        return 'GA_CSW_Audit_Packet_' . $this->safeFile($cycleLabel) . '.zip';
    }

    public function reportPdfName(array $data): string
    {
        $cycleLabel = $this->safeFile($this->cycleLabel((array)$data['cycle']));
        $last = $this->safeFile((string)($data['license']['licensee_last_name'] ?? 'Licensee'));
        $first = $this->safeFile((string)($data['license']['licensee_first_name'] ?? 'User'));
        return "GA_CSW_CE_Report_{$cycleLabel}_{$first}_{$last}.pdf";
    }

    public function cycleLabel(array $cycle): string
    {
        return substr((string)$cycle['cycle_start'],0,4) . '-' . substr((string)$cycle['cycle_end'],0,4);
    }

    public function absoluteDocumentPath(string $filePath): ?string
    {
        $root = realpath(__DIR__ . '/../../');
        $full = realpath(__DIR__ . '/../../' . ltrim($filePath, '/'));
        if (!$root || !$full || !str_starts_with($full, $root . DIRECTORY_SEPARATOR)) {
            return null;
        }
        return $full;
    }

    private function getLicense(int $licenseId): ?array
    {
        $stmt = Connection::get()->prepare('SELECT * FROM licenses WHERE id=:id LIMIT 1');
        $stmt->execute(['id'=>$licenseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
