<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    exit("This script must be run from CLI.\n");
}

[$script, $email, $fullName, $password] = array_pad($argv, 4, null);

if (!$email || !$fullName || !$password) {
    exit("Usage: php cli_create_admin.php admin@example.com \"Admin Name\" StrongPassword123!\n");
}

$creator = new App\Services\CreateAdminUser();
$creator->run($email, $fullName, $password);

echo "Admin user created successfully.\n";
