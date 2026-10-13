<?php

return [
    'dataset_analysis' => [
        'python_binary' => env('TILKIT_PYTHON_BIN', 'python'),
        'max_rows' => (int) env('TILKIT_DATASET_MAX_ROWS', 100000),
        'max_upload_mb' => (int) env('TILKIT_DATASET_MAX_UPLOAD_MB', 25),
        'sandbox_timeout_seconds' => (int) env('TILKIT_SANDBOX_TIMEOUT', 45),
        'max_code_fix_attempts' => (int) env('TILKIT_SANDBOX_MAX_ATTEMPTS', 3),
        'max_dashboard_charts' => (int) env('TILKIT_DATASET_MAX_DASHBOARD_CHARTS', 12),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],
];
