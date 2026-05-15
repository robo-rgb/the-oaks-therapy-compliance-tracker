<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$migrator = new App\Database\Migrator();
$migrator->run();

echo "Migrations complete.\n";
