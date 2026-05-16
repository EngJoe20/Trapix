    /**
     * Return all AI provider configs as a flat array keyed by provider name.
     * Used by AiIntegrationController to seed the settings page when the user
     * has no personal overrides saved in the database.
     */
    'ai_providers' => [
        'openai' => [
            'api_key'      => env('OPENAI_API_KEY'),
            'base_url'     => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'default_model'=> env('OPENAI_MODEL', 'gpt-4o-mini'),
            'is_enabled'   => ! empty(env('OPENAI_API_KEY')),
        ],
        'gemini' => [
            'api_key'      => env('GEMINI_API_KEY'),
            'base_url'     => null,
            'default_model'=> env('GEMINI_MODEL', 'gemini-2.5-flash'),
            'is_enabled'   => ! empty(env('GEMINI_API_KEY')),
        ],
        'claude' => [
            'api_key'      => env('CLAUDE_API_KEY'),
            'base_url'     => 'https://api.anthropic.com/v1',
            'default_model'=> env('CLAUDE_MODEL', 'claude-3-5-sonnet-20240620'),
            'is_enabled'   => ! empty(env('CLAUDE_API_KEY')),
        ],
        'ollama' => [
            'api_key'      => null,
            'base_url'     => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
            'default_model'=> env('OLLAMA_MODEL', 'llama3'),
            'is_enabled'   => true,  // Local — always available without key
        ],
    ],
];