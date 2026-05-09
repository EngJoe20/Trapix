<?php

namespace App\Services\AI;

use App\Models\AnalysisJob;
use App\Models\AiResponse;
use Illuminate\Support\Facades\Log;

/**
 * AiAnalysisService
 * -----------------
 * Facade over any AI provider adapter.
 * The active provider is resolved from config/trapix.php.
 * Swap providers at runtime without touching any controller.
 *
 * Usage:
 *   app(AiAnalysisService::class)->run($job);
 */
class AiAnalysisService
{
    private AiProviderInterface $provider;

    public function __construct()
    {
        $this->provider = $this->resolveProvider();
    }

    /**
     * Run AI analysis on a completed job and persist results.
     */
    public function run(AnalysisJob $job): AiResponse
    {
        abort_unless($job->isCompleted() && $job->result, 422, 'Job not ready for AI analysis.');

        $aiRecord = AiResponse::updateOrCreate(
            ['analysis_job_id' => $job->id],
            ['provider' => $this->provider->providerName(), 'model' => $this->provider->modelName(), 'status' => 'pending']
        );

        try {
            $insights = $this->provider->analyze($job->result);
            $pdfPath  = $this->provider->generateReport($job->result, $insights);

            $aiRecord->update([
                'insights'        => $insights,
                'pdf_path'        => $pdfPath,
                'tokens_used'     => $insights['tokens_used'] ?? 0,
                'cost_microcents' => $insights['cost_microcents'] ?? 0,
                'status'          => 'completed',
                'error'           => null,
            ]);

            Log::info('AiAnalysisService: completed', ['job' => $job->id, 'provider' => $this->provider->providerName()]);
        } catch (\Throwable $e) {
            $aiRecord->update(['status' => 'failed', 'error' => $e->getMessage()]);
            Log::error('AiAnalysisService: failed', ['job' => $job->id, 'error' => $e->getMessage()]);
            throw $e;
        }

        return $aiRecord->fresh();
    }

    // ── Provider resolution ───────────────────────────────────────────────────

    private function resolveProvider(): AiProviderInterface
    {
        $provider = config('trapix.ai_provider', 'openai');

        return match ($provider) {
            'openai' => app(OpenAiProvider::class),
            // 'claude'  => app(ClaudeProvider::class),
            // 'gemini'  => app(GeminiProvider::class),
            // 'ollama'  => app(OllamaProvider::class),
            default  => throw new \InvalidArgumentException("Unknown AI provider: {$provider}"),
        };
    }
}
