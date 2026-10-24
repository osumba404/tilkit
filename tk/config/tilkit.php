<?php

return [
    /*
    | TILKIT weather indicator module (Traditional Indigenous and Local Knowledge).
    | Workbook spec: tilkit_docs/TILKIT_Weather_Indicator_Log_Master_Template_v4_OND2026.xlsx
    */
    'weather' => [
        /*
        | Single deployment = one weather tool. Column register & community list stay in JSON;
        | do not seed indicator log rows or generic form fields. Demo import is for tests/CLI only.
        */
        'single_tool' => [
            'enabled' => env('TILKIT_SINGLE_TOOL', true),
            'code' => env('TILKIT_TOOL_CODE', 'tilkit-weather'),
            // New sign-ins receive this role on the embedded tool (not a separate project per study).
            'default_member_role' => env('TILKIT_DEFAULT_MEMBER_ROLE', 'member'),
            'project' => [
                'name' => env('TILKIT_TOOL_NAME', 'TILKIT Weather Indicator Log'),
                'purpose' => 'Traditional indigenous and local knowledge weather indicators — v4 workbook capture and tally.',
                'lawful_basis' => 'consent',
                'public_collection_enabled' => false,
            ],
        ],
        'protocol_version' => env('TILKIT_WEATHER_PROTOCOL', 'v4-2026-10-19'),
        'docs_path' => env('TILKIT_DOCS_PATH', base_path('../tilkit_docs')),
        'column_register_path' => config_path('tilkit_weather_column_register.json'),
        'community_data_list_path' => config_path('tilkit_community_data_list.json'),
        'workbook_sheets_path' => config_path('tilkit_workbook_sheets.json'),
        'demo_fixture_path' => base_path('tests/Fixtures/Tilkit/demo_workbook_full.json'),
        // v4 master + DEMO xlsx: sheet "Sheet 1 - Indicator Log"
        'indicator_log_xlsx' => [
            'sheet_name' => 'Sheet 1 - Indicator Log',
            'column_letter_row' => 2,
            'field_label_rows' => [3, 4],
            'min_scan_row' => 5,
            'uid_column' => 'AH',
        ],
        'engine' => [
            'scored_seasons_for_hit_rate' => (int) env('TILKIT_WEATHER_SCORED_SEASONS', 3),
            'corroboration_cap_until' => 2,
            'separate_confirm_approve' => env('TILKIT_SEPARATE_CONFIRM_APPROVE', true),
            'false_start_counts_in_scored_seasons' => true,
        ],
    ],

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
