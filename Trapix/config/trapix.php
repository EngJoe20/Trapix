<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Python Analysis Engine Configuration
    |--------------------------------------------------------------------------
    */

    // Path to the Python interpreter (use venv if available)
    'python_executable' => env('PYTHON_EXECUTABLE', 'python'),

    // Path to the main entry point for the analysis script
    'python_script_path' => env('PYTHON_SCRIPT_PATH', base_path('../Tools/main.py')),

    // Max execution time for the Python script (in seconds)
    'python_timeout' => env('PYTHON_TIMEOUT', 300),

    /*
    |--------------------------------------------------------------------------
    | AI & Threat Intelligence Configuration
    |--------------------------------------------------------------------------
    */

    // VirusTotal API Key
    'virustotal_api_key' => env('VIRUSTOTAL_API_KEY'),

    // AI Provider selection (openai | gemini | claude | ollama)
    'ai_provider' => env('AI_PROVIDER', 'gemini'),

    // AI Agent path for Gemini provider
    'ai_agent_path' => env('AI_AGENT_PATH', base_path('../../AI Agent')),

    /*
    |--------------------------------------------------------------------------
    | Upload & Processing Limits
    |--------------------------------------------------------------------------
    */

    'storage_disk'      => env('TRAPIX_STORAGE_DISK', 'local'),
    'max_upload_bytes'  => env('MAX_UPLOAD_BYTES', 52428800), // 50MB
    'max_files_per_job' => env('MAX_FILES_PER_JOB', 50),
    'job_cleanup_days'  => env('JOB_CLEANUP_DAYS', 7),

    'allowed_mime_types' => [
        'application/x-dosexec',
        'application/x-msdownload',
        'application/exe',
        'application/x-exe',
        'application/dos-exe',
        'vms/exe',
        'application/x-winexe',
        'application/msdos-windows',
        'application/x-ms-dos-executable',
        'application/zip',
        'application/x-zip-compressed',
        'multipart/x-zip',
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Providers Detail (Used for Settings UI & Defaults)
    |--------------------------------------------------------------------------
    */
    'ai_providers' => [
        'openai' => [
            'api_key'       => env('OPENAI_API_KEY'),
            'base_url'      => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'default_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'is_enabled'    => ! empty(env('OPENAI_API_KEY')),
        ],
        'gemini' => [
            'api_key'       => env('GEMINI_API_KEY'),
            'base_url'      => null,
            'default_model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
            'is_enabled'    => ! empty(env('GEMINI_API_KEY')),
        ],
        'claude' => [
            'api_key'       => env('CLAUDE_API_KEY'),
            'base_url'      => 'https://api.anthropic.com/v1',
            'default_model' => env('CLAUDE_MODEL', 'claude-3-5-sonnet-20240620'),
            'is_enabled'    => ! empty(env('CLAUDE_API_KEY')),
        ],
        'ollama' => [
            'api_key'       => null,
            'base_url'      => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
            'default_model' => env('OLLAMA_MODEL', 'llama3'),
            'is_enabled'    => true,
        ],
    ],

    // Legacy individual keys (for backward compatibility if needed by some providers)
    'openai_api_key'   => env('OPENAI_API_KEY'),
    'openai_model'     => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'gemini_api_key'   => env('GEMINI_API_KEY'),
    'gemini_model'     => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    'claude_api_key'   => env('CLAUDE_API_KEY'),
    'claude_model'     => env('CLAUDE_MODEL', 'claude-3-5-sonnet-20240620'),
    'ollama_base_url'  => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
    'ollama_model'     => env('OLLAMA_MODEL', 'llama3'),
];