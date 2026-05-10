<?php

namespace App\Services\AI;

use App\Models\User;
use App\Models\AiIntegration;

/**
 * AIManager
 * ---------
 * Unified AI manager that loads the current logged-in user's provider config,
 * injects API keys, and routes the request to the correct provider.
 */
class AIManager
{
    /**
     * Get a configured AI Provider instance for a specific user.
     * If the user hasn't configured the provider, it falls back to the system default (.env).
     *
     * @param User|null $user The user requesting AI analysis.
     * @param string $providerName The requested provider (e.g., 'openai', 'gemini', 'claude', 'ollama').
     * @return AiProviderInterface
     */
    public static function resolveProvider(?User $user, string $providerName): AiProviderInterface
    {
        $provider = match ($providerName) {
            'openai' => app(OpenAiProvider::class),
            'gemini' => app(GeminiProvider::class),
            'claude' => app(ClaudeProvider::class),
            'ollama' => app(OllamaProvider::class),
            default  => throw new \InvalidArgumentException("Unknown AI provider: {$providerName}"),
        };

        if ($user) {
            // Load user's custom configuration for this provider
            $integration = AiIntegration::where('user_id', $user->id)
                ->where('provider', $providerName)
                ->where('is_enabled', true)
                ->first();

            if ($integration) {
                // Inject the user's API key, base URL, and default model
                $provider->setConfig(
                    apiKey: $integration->api_key, // using accessor which decrypts
                    baseUrl: $integration->base_url,
                    model: $integration->default_model
                );
            }
        }

        return $provider;
    }

    /**
     * Generic ask method as requested. Useful for general prompts outside of malware triage.
     */
    public static function ask(string $providerName, string $prompt, ?User $user = null): string
    {
        // For Trapix, the primary usage is the `analyze(array $result)` method via AiAnalysisService.
        // This method is provided for generic prompt usage if needed in the future.
        throw new \RuntimeException("AIManager::ask is a stub. Use AiAnalysisService->run() for malware triage.");
    }
}
