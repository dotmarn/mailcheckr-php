<?php

return [
    'api_key' => env('MAILCHECKR_API_KEY'),
    'base_url' => env('MAILCHECKR_BASE_URL', 'https://mailcheckr.app/api/v1'),
    'timeout' => (int) env('MAILCHECKR_TIMEOUT', 30),
    'webhook_secret' => env('MAILCHECKR_WEBHOOK_SECRET'),
    'webhook_tolerance' => (int) env('MAILCHECKR_WEBHOOK_TOLERANCE', 300),
];
