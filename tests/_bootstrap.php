<?php

use AspectMock\Kernel;

include __DIR__.'/../vendor/autoload.php'; // composer autoload

$kernel = Kernel::getInstance();
$kernel->init([
    'debug' => true,
    'includePaths' => [__DIR__.'/../src']
]);
