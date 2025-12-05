<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'user'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_filter(
        explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://localhost:8000,https://projekt-ai-novamed.onrender.com'))
    ),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['X-XSRF-TOKEN'],
    'max_age' => 0,
    'supports_credentials' => true,
];
