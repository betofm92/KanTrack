<?php

declare(strict_types=1);

use Tests\Support\MailTemplateCatalogBuilder;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../tests/Support/MailTemplateCatalogBuilder.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$builder = new MailTemplateCatalogBuilder();
$outputPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'mail-template-catalog.html';
$catalog = $builder->generate($outputPath);

echo json_encode([
    'output_path' => $outputPath,
    'summary' => $catalog['summary'],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
