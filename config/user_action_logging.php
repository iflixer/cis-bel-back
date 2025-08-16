<?php

return [
    'enabled' => env('USER_ACTION_LOGGING_ENABLED', true),
    'exclude_methods' => [
        'system.ping',
        'system.health',
        'tikets.getNew',
        'domains.get',
        'articles.get',
        'exits'
    ],
    'exclude_sensitive_fields' => [
        'password',
        'password_confirmation',
        'token',
        'secret',
        'api_key',
        'bearer_token',
        'refresh_token'
    ],
    'log_request_data' => true,
    'max_request_data_length' => 10000,
];