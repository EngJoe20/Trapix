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
    private string $providerName;

    public function __construct()
    {
        // Default system provider if user has no specific preference selected
        $this->providerName = config('trapix.ai_provider', 'gemini');
    }

    /**
     * Run AI analysis on a completed job and persist results.
     */
    public function run(AnalysisJob $job): AiResponse
    {
        abort_unless($job->isCompleted() && $job->result, 422, 'Job not ready for AI analysis.');

        // User might have requested a specific tool/provider or we use the default
        // The frontend could send a specific provider, but for now we use the default system provider
        // and inject the user's keys if they have an integration for it.
        $user = $job->user; 
        
        $this->provider = AIManager::resolveProvider($user, $this->providerName);

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
}
