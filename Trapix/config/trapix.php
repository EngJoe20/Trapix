<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Python Engine Configuration
    |--------------------------------------------------------------------------
    */

    'python_executable'  => env('PYTHON_EXECUTABLE', 'python'),

    // Absolute path to the Tools/main.py script
    'python_script_path' => env('PYTHON_SCRIPT_PATH', base_path('../Tools/main.py')),

    // Timeout in seconds for a single Python analysis subprocess
    'python_timeout'     => (int) env('PYTHON_TIMEOUT', 300),

    /*
    |--------------------------------------------------------------------------
    | Upload Constraints
    |--------------------------------------------------------------------------
    */

    // Maximum single-file upload size in bytes (default 50 MB)
    'max_upload_bytes'   => (int) env('MAX_UPLOAD_BYTES', 52428800),

    // Maximum number of files per analysis job
    'max_files_per_job'  => (int) env('MAX_FILES_PER_JOB', 50),

    // Allowed MIME types (kept loose for binary files)
    'allowed_mime_types' => [
        'application/octet-stream',
        'application/x-dosexec',
        'application/x-executable',
        'application/x-msdownload',
        'application/vnd.microsoft.portable-executable',
        'application/x-mach-binary',
        'application/x-elf',
        'application/pdf',
        'application/zip',
        'application/x-zip-compressed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Quota Defaults (used when no plan is assigned)
    |--------------------------------------------------------------------------
    */

    'default_monthly_analyses'  => (int) env('DEFAULT_MONTHLY_ANALYSES', 10),
    'guest_max_analyses'        => (int) env('GUEST_MAX_ANALYSES', 3),

    /*
    |--------------------------------------------------------------------------
    | AI Integration
    |--------------------------------------------------------------------------
    | Set ai_provider to the provider you want to use.
    | Available: openai | claude | gemini | ollama
    */

    'ai_provider'    => env('AI_PROVIDER', 'openai'),

    'openai_api_key' => env('OPENAI_API_KEY'),
    'openai_model'   => env('OPENAI_MODEL', 'gpt-4o-mini'),

    // Future providers (uncomment when implementing adapters)
    // 'claude_api_key'  => env('CLAUDE_API_KEY'),
    // 'claude_model'    => env('CLAUDE_MODEL', 'claude-3-5-sonnet-20241022'),
    // 'gemini_api_key'  => env('GEMINI_API_KEY'),
    // 'gemini_model'    => env('GEMINI_MODEL', 'gemini-1.5-pro'),
    // 'ollama_base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
    // 'ollama_model'    => env('OLLAMA_MODEL', 'llama3'),

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    */

    'storage_disk'      => env('STORAGE_DISK', 'local'),

    // Number of days before temporary job files are cleaned up
    'job_cleanup_days'  => (int) env('JOB_CLEANUP_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting (requests per minute)
    |--------------------------------------------------------------------------
    */

    'api_rate_limit'    => (int) env('API_RATE_LIMIT', 60),
    'upload_rate_limit' => (int) env('UPLOAD_RATE_LIMIT', 10),

];
