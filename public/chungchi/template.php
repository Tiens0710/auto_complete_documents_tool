<?php

declare(strict_types=1);

use AIVANBAN\Services\TemplateGenerator;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

(new TemplateGenerator())->output();
