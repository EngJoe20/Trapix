<?php

namespace App\Jobs;

use App\Models\AnalysisJob;
use App\Models\User;
use App\Services\PythonBridgeService;
use App\Services\QuotaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * RunPythonAnalysis
 * -----------------
 * Queue job that invokes the Python malware triage engine via PythonBridgeService.
 * Runs on the 'default' or 'high' queue depending on user plan.
 */
class RunPythonAnalysis implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /** Retry up to 2 times on failure */
    public int $tries = 2;

    /** Max execution time (seconds) */
    public int $timeout = 360;

    public function __construct(
        public readonly string $jobId,
    ) {}

    public function handle(PythonBridgeService $bridge, QuotaService $quota): void
    {
        // ── Load the job ───────────────────────────────────────────────────────
        $job = AnalysisJob::with('files')->find($this->jobId);

        if (! $job || $job->isCompleted() || $job->isFailed()) {
            Log::warning('RunPythonAnalysis: Job not found or already terminal', ['id' => $this->jobId]);
            return;
        }

        // ── Mark as processing ─────────────────────────────────────────────────
        $job->update(['status' => AnalysisJob::STATUS_PROCESSING, 'started_at' => now()]);

        // ── Run the bridge ─────────────────────────────────────────────────────
        try {
            $result = $bridge->run($job);
            $bridge->persistResult($job, $result);

            // ── Increment user quota after successful analysis ──────────────────
            if ($job->user_id && $result['success']) {
                $user = User::find($job->user_id);
                if ($user) {
                    $quota->incrementUser($user);
                }
            } elseif ($job->guest_token && $result['success']) {
                $quota->incrementGuest($job->guest_token);
            }
            
            // ── Call AI Expert System if requested ─────────────────────────────
            $tools = $job->options['tools'] ?? [];
            if (in_array('ai', $tools) && $result['success']) {
                try {
                    app(\App\Services\AI\AiAnalysisService::class)->run($job);
                } catch (\Throwable $e) {
                    Log::error('RunPythonAnalysis: AI Expert System failed', [
                        'job'   => $job->id,
                        'error' => $e->getMessage()
                    ]);
                    // We don't fail the whole job if only AI fails
                }
            }

            Log::info('RunPythonAnalysis: Completed', [
                'job'   => $job->id,
                'risk'  => $job->result['risk_level'] ?? 'N/A',
            ]);
        } catch (\Throwable $e) {
            $fileName = $job->files->first()?->original_name ?? 'unknown';
            
            Log::error("RunPythonAnalysis: Analysis Failed for [{$fileName}]", [
                'job_id'   => $this->jobId,
                'file'     => $fileName,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            $job->update([
                'status'        => AnalysisJob::STATUS_FAILED,
                'error_message' => "Error analyzing [{$fileName}]: " . $e->getMessage(),
                'completed_at'  => now(),
            ]);

            throw $e; // Let the queue retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('RunPythonAnalysis: Permanently failed', [
            'job'   => $this->jobId,
            'error' => $exception->getMessage(),
        ]);

        AnalysisJob::where('id', $this->jobId)->update([
            'status'        => AnalysisJob::STATUS_FAILED,
            'error_message' => 'Analysis failed after all retries: ' . $exception->getMessage(),
            'completed_at'  => now(),
        ]);
    }
}
