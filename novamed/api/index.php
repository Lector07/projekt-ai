<?php

file_put_contents('php://stderr', "🐘 api/index.php STARTED | Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'n/a') . " | URI: " . ($_SERVER['REQUEST_URI'] ?? 'n/a') . "\n");

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
