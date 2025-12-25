<?php

return [
    'enabled' => env('TELEGRAM_ENABLED', true),
    'bot_token' => env('TELEGRAM_REPORT_BOT_TOKEN'),
    'chat_id' => env('TELEGRAM_REPORT_GROUP'),
];
