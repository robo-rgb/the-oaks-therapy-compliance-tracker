<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'The Oaks Compliance Tracker',
        'env' => 'local',
        'debug' => false,
        'base_url' => 'http://localhost:8000/license',
        'base_path' => '/license',
        'session_name' => 'oaks_compliance_session',
    ],
    'database' => [
        'driver' => 'sqlite',
        'path' => __DIR__ . '/../database/license_tracker.sqlite',
    ],
    'smtp' => [
        'host' => 'smtp.example.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'smtp-user@example.com',
        'password' => 'replace-with-secret',
        'from_email' => 'noreply@example.com',
        'from_name' => 'The Oaks Compliance Tracker',
    ],
];
