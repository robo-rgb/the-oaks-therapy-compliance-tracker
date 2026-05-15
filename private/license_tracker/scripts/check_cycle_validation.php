<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$svc = new App\Services\RenewalCycleService();
$bad = [
    'cycle_start' => '2026-09-30',
    'cycle_end' => '2024-10-01',
    'renewal_deadline' => '2026-09-30',
    'late_renewal_deadline' => '2026-09-01',
    'renewal_submitted' => 1,
    'renewal_submitted_date' => '',
    'renewal_fee_paid' => 1,
    'renewal_fee_paid_date' => '',
];
$errors = $svc->validate($bad);
if (count($errors) < 4) {
    fwrite(STDERR, "Validation check failed\n");
    exit(1);
}
echo "Validation check passed\n";
