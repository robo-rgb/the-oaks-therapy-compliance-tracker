<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$seedFiles = glob(__DIR__ . '/seeds/*.php') ?: [];
sort($seedFiles);

foreach ($seedFiles as $file) {
    require $file;
}

echo "Seeding complete.\n";
