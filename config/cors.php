<?php

return [
    'allowed_origins' => [
        env('APP_URL', 'http://localhost'),
        env('FRONTEND_URL', 'http://localhost:8040'),
        env('CORS_ALLOWED_ORIGINS', ''),
    ],
    'allowed_methods' => [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'OPTIONS',
    ],
    'allowed_headers' => [
        'Origin',
        'Content-Type',
        'Accept',
        'Authorization',
        'X-Requested-With',
    ],
    'allow_credentials' => true,
    'max_age' => 86400,
];
