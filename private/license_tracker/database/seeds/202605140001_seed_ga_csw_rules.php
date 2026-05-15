<?php

declare(strict_types=1);

use App\Database\Connection;

$pdo = Connection::get();

$stmt = $pdo->prepare(
    'INSERT INTO requirement_rules (license_type, state_code, rule_code, description, cycle_length_months, total_hours_required, ethics_hours_required, effective_date, is_active)
     VALUES (:license_type, :state_code, :rule_code, :description, :cycle_length_months, :total_hours_required, :ethics_hours_required, :effective_date, :is_active)'
);

$rules = [
    [
        'license_type' => 'CSW',
        'state_code' => 'GA',
        'rule_code' => '135-9-.01',
        'description' => 'Georgia CSW renewal cycle requires 35 continuing education hours every 24 months including at least 5 ethics hours.',
        'cycle_length_months' => 24,
        'total_hours_required' => 35,
        'ethics_hours_required' => 5,
        'effective_date' => '2024-01-01',
        'is_active' => 1,
    ],
];

foreach ($rules as $rule) {
    $check = $pdo->prepare('SELECT id FROM requirement_rules WHERE license_type = :license_type AND state_code = :state_code AND rule_code = :rule_code LIMIT 1');
    $check->execute([
        'license_type' => $rule['license_type'],
        'state_code' => $rule['state_code'],
        'rule_code' => $rule['rule_code'],
    ]);

    if ($check->fetch()) {
        continue;
    }

    $stmt->execute($rule);
}

echo "Seeded Georgia CSW requirement rules.\n";
