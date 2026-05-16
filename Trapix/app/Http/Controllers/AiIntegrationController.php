<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AiIntegration;
use App\Services\AI\AIManager;
use Illuminate\Support\Facades\Log;

/**
 * AiIntegrationController
 * -----------------------
 * Handles per-user AI integrations (saving keys, testing connections).
 */
class AiIntegrationController extends Controller
{
    /**
     * Known model lists per provider (used for dropdowns in settings).
     */
    private const PROVIDER_MODELS = [
        'openai' => [
            'gpt-4o'              => 'GPT-4o (recommended)',
            'gpt-4o-mini'         => 'GPT-4o Mini (fast / cheap)',
            'gpt-4-turbo'         => 'GPT-4 Turbo',
            'o3-mini'             => 'o3-mini (reasoning)',
            'o1'                  => 'o1 (reasoning)',
        ],
        'gemini' => [
            'gemini-2.5-pro'      => 'Gemini 2.5 Pro (recommended)',
            'gemini-2.5-flash'    => 'Gemini 2.5 Flash (fast)',
            'gemini-2.0-flash'    => 'Gemini 2.0 Flash',
            'gemini-1.5-pro'      => 'Gemini 1.5 Pro',
            'gemini-1.5-flash'    => 'Gemini 1.5 Flash',
        ],
        'claude' => [
            'claude-sonnet-4-20250514'     => 'Claude Sonnet 4 (latest)',
            'claude-3-5-sonnet-20241022'   => 'Claude 3.5 Sonnet',
            'claude-3-5-haiku-20241022'    => 'Claude 3.5 Haiku (fast)',
            'claude-3-opus-20240229'       => 'Claude 3 Opus (most capable)',
        ],
        'ollama' => [
            'llama3.3'            => 'Llama 3.3 (recommended)',
            'llama3.2'            => 'Llama 3.2',
            'llama3'              => 'Llama 3',
            'deepseek-r1'         => 'DeepSeek R1 (reasoning)',
            'qwen2.5'             => 'Qwen 2.5',
        ],
    ];

    /**
     * Display the AI Integrations settings page.
     *
     * Merges two sources:
     *   1. User's personal overrides stored in ai_integrations table (highest priority).
     *   2. System-wide defaults from config('trapix.ai_providers') — fed from .env.
     *
     * This means every provider card is always visible with sensible defaults
     * even when the user has never saved a personal key.
     */
    public function index()
    {
        $user       = Auth::user();
        $allConfigs = config('trapix.ai_providers', []);

        // Load user's personal overrides keyed by provider name
        $userIntegrations = $user->aiIntegrations->keyBy('provider');

        // Merge: user overrides win; fall back to .env config
        $integrations = [];
        foreach ($allConfigs as $provider => $envConfig) {
            if ($userIntegrations->has($provider)) {
                // Personal override — use everything from the DB record
                $dbRecord                    = $userIntegrations[$provider];
                $integrations[$provider]     = [
                    'is_enabled'     => $dbRecord->is_enabled,
                    'api_key'        => null, // Never send plaintext key to the frontend
                    'base_url'       => $dbRecord->base_url,
                    'default_model'  => $dbRecord->default_model,
                    'env_key_present'=> ! empty(config("trapix.ai_providers.{$provider}.api_key")),
                ];
            } else {
                // No personal override — seed from .env so the card is never empty
                $integrations[$provider] = [
                    'is_enabled'     => (bool) ($envConfig['is_enabled'] ?? false),
                    'api_key'        => null,
                    'base_url'       => $envConfig['base_url'] ?? '',
                    'default_model'  => $envConfig['default_model'] ?? '',
                    'env_key_present'=> ! empty($envConfig['api_key']),
                ];
            }
        }

        return view('settings.ai-integrations', compact('user', 'integrations'));
    }

    /**
     * Save or update an AI provider configuration.
     */
    public function save(Request $request)
    {
        $request->validate([
            'provider'   => 'required|string|in:openai,gemini,claude,ollama',
            'api_key'    => 'nullable|string',
            'base_url'   => 'nullable|url',
            'model'      => 'nullable|string',
            'is_enabled' => 'boolean',
        ]);

        $user     = Auth::user();
        $provider = $request->input('provider');

        // ── Validation: API key must exist somewhere (request body OR .env OR DB) ──
        $envHasKey = ! empty(config("trapix.ai_providers.{$provider}.api_key"));
        $hasExistingKey = (bool) AiIntegration::where('user_id', $user->id)
            ->where('provider', $provider)
            ->value('api_key_encrypted');

        if ($provider !== 'ollama'
            && empty($request->input('api_key'))
            && ! $envHasKey
            && ! $hasExistingKey) {

            return response()->json([
                'success' => false,
                'message' => 'No API key found. Enter a key, or set it in your .env file and refresh.',
            ], 422);
        }

        // ── Preserve existing config so we never wipe data the user didn’t touch ─
        $existing = AiIntegration::where('user_id', $user->id)
            ->where('provider', $provider)
            ->first();

        if ($existing) {
            $baseUrl   = $request->input('base_url');
            $model     = $request->input('model');
            $isEnabled = $request->boolean('is_enabled', $existing->is_enabled);

            $changed = false;

            if ($baseUrl !== null && $baseUrl !== $existing->base_url)  { $existing->base_url   = $baseUrl;   $changed = true; }
            if ($model   !== null && $model   !== $existing->default_model){ $existing->default_model = $model; $changed = true; }
            if ($isEnabled !== $existing->is_enabled)                      { $existing->is_enabled = $isEnabled; $changed = true; }

            // Only touch the API key when the user explicitly typed a new one
            if ($request->filled('api_key')) {
                $existing->api_key = $request->input('api_key');
                $changed = true;
            }

            if ($changed) {
                $existing->save();
            }

            $integration = $existing;
        } else {
            // ── New record – all required fields must be present ───────────────
            $integration = AiIntegration::create([
                'user_id'      => $user->id,
                'provider'     => $provider,
                'api_key'      => $request->input('api_key') ?? '',
                'base_url'     => $request->input('base_url') ?? '',
                'default_model'=> $request->input('model'),
                'is_enabled'   => $request->boolean('is_enabled', true),
            ]);
        }

        Log::info('User updated AI integration', [
            'user_id'   => $user->id,
            'provider'  => $provider,
            'model'     => $integration->default_model,
            'is_enabled'=> $integration->is_enabled,
        ]);

        return response()->json([
            'success'    => true,
            'message'    => ucfirst($provider) . ' configuration saved successfully.',
            'model'      => $integration->default_model,
            'is_enabled' => $integration->is_enabled,
        ]);
    }

    /**
     * Delete an AI provider configuration.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:openai,gemini,claude,ollama',
        ]);

        $user     = Auth::user();
        $provider = $request->input('provider');

        AiIntegration::where('user_id', $user->id)
            ->where('provider', $provider)
            ->delete();

        Log::info('User deleted AI integration', ['user_id' => $user->id, 'provider' => $provider]);

        return response()->json([
            'success' => true,
            'message' => ucfirst($provider) . ' configuration deleted.',
        ]);
    }

    /**
     * Test the connection to the provider using the provided credentials.
     */
    public function test(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:openai,gemini,claude,ollama',
            'api_key'  => 'nullable|string',
            'base_url' => 'nullable|url',
            'model'    => 'nullable|string',
        ]);

        $providerName = $request->input('provider');
        $apiKey       = $request->input('api_key');
        $baseUrl      = $request->input('base_url');
        $model        = $request->input('model');

        // Fall back to saved key if user didn't type a new one in the test modal
        if (empty($apiKey)) {
            $existing = AiIntegration::where('user_id', Auth::id())
                ->where('provider', $providerName)
                ->first();

            if ($existing) {
                $apiKey = $existing->api_key;
            }
        }

        // Fast-fail: all non-Ollama providers need a key
        if ($providerName !== 'ollama' && empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'API Key is required to test.',
            ], 422);
        }

        try {
            $providerService = AIManager::resolveProvider(null, $providerName);
            $providerService->setConfig(apiKey: $apiKey, baseUrl: $baseUrl, model: $model);

            $dummyResult = [
                'file_name'     => 'test.txt',
                'risk_level'    => 'CLEAN',
                'virustotal'    => [],
                'packer'        => [],
                'suspicious_apis'=> [],
                'iocs'          => [],
                'entropy'       => [],
                'strings'       => ['ascii' => ['test'], 'unicode' => []],
                'pe_info'       => ['imports' => []],
            ];

            $providerService->analyze($dummyResult);

            Log::info('User successfully tested AI integration', [
                'user_id'    => Auth::id(),
                'provider'   => $providerName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Connection successful!',
            ]);

        } catch (\Throwable $e) {
            Log::warning('User failed AI integration test', [
                'user_id'  => Auth::id(),
                'provider' => $providerName,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Return the known model list for the given provider (used by the frontend JS).
     * Route: GET /settings/ai-integrations/models?provider=openai
     */
    public function models(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:openai,gemini,claude,ollama',
        ]);

        $provider = $request->input('provider');
        $models   = self::PROVIDER_MODELS[$provider] ?? [];

        return response()->json([
            'success'    => true,
            'provider'   => $provider,
            'models'     => $models,
        ]);
    }
}
