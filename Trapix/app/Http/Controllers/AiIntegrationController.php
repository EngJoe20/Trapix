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
     * Display the AI Integrations settings page.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Eager load the user's integrations
        $integrations = $user->aiIntegrations->keyBy('provider');

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

        $user = Auth::user();
        $provider = $request->input('provider');

        // Only Ollama can have an empty API key
        if ($provider !== 'ollama' && empty($request->input('api_key'))) {
            // If they are not updating the key (leaving it blank), we shouldn't overwrite an existing key with null unless they explicitly deleted it
            // Actually, we'll let them clear it out, but they might just be saving the model.
            // Let's check if the integration exists.
            $existing = AiIntegration::where('user_id', $user->id)->where('provider', $provider)->first();
            if (!$existing && $request->input('is_enabled')) {
                return response()->json(['success' => false, 'message' => 'API Key is required.'], 422);
            }
        }

        $integration = AiIntegration::updateOrCreate(
            ['user_id' => $user->id, 'provider' => $provider],
            [
                'base_url'      => $request->input('base_url'),
                'default_model' => $request->input('model'),
                'is_enabled'    => $request->boolean('is_enabled', true),
            ]
        );

        // Update API key only if provided (or if explicitly requested to clear, which we can handle via destroy)
        if ($request->filled('api_key')) {
            $integration->api_key = $request->input('api_key');
            $integration->save();
        }

        Log::info('User updated AI integration', ['user_id' => $user->id, 'provider' => $provider]);

        return response()->json([
            'success' => true,
            'message' => ucfirst($provider) . ' configuration saved successfully.',
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

        $user = Auth::user();
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
     * We don't save it here, we just use the raw provider service.
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

        // If API key is empty, check if we have a saved one
        if (empty($apiKey)) {
            $existing = AiIntegration::where('user_id', Auth::id())->where('provider', $providerName)->first();
            if ($existing) {
                $apiKey = $existing->api_key;
            }
        }

        // Fast fail
        if ($providerName !== 'ollama' && empty($apiKey)) {
            return response()->json(['success' => false, 'message' => 'API Key is required to test.'], 422);
        }

        try {
            // Resolve the provider service directly (ignoring user config, we inject manually)
            $providerService = AIManager::resolveProvider(null, $providerName);
            $providerService->setConfig(apiKey: $apiKey, baseUrl: $baseUrl, model: $model);

            // Create a fake analysis result to test
            $dummyResult = [
                'file_name' => 'test.txt',
                'risk_level' => 'CLEAN',
                'virustotal' => [],
                'packer' => [],
                'suspicious_apis' => [],
                'iocs' => [],
                'entropy' => [],
                'strings' => ['ascii' => ['test'], 'unicode' => []],
                'pe_info' => ['imports' => []]
            ];

            // This will throw an exception if the API key is invalid or base URL is unreachable
            $providerService->analyze($dummyResult);

            Log::info('User successfully tested AI integration', ['user_id' => Auth::id(), 'provider' => $providerName]);

            return response()->json([
                'success' => true,
                'message' => 'Connection successful!',
            ]);

        } catch (\Throwable $e) {
            Log::warning('User failed AI integration test', ['user_id' => Auth::id(), 'provider' => $providerName, 'error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 400);
        }
    }
}
