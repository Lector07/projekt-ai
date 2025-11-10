<?php

/*
|--------------------------------------------------------------------------
| Laravel Serverless Entry Point (Vercel)
|--------------------------------------------------------------------------
*/

use IlluminateContractsHttpKernel;
use IlluminateHttpRequest;

define('LARAVEL_START', microtime(true));

// Load Composer's autoloader
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Run the application
$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
);

$response->send();

$kernel->terminate($request, $response);