<?php

declare(strict_types=1);

$example = require __DIR__ . '/config.example.php';
$localPath = __DIR__ . '/local.php';

if (file_exists($localPath)) {
    $local = require $localPath;
    return array_replace_recursive($example, $local);
}

return $example;
