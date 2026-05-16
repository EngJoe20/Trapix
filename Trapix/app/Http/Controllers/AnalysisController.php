<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadAnalysisRequest;
use App\Services\AnalysisService;
use App\Services\QuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * AnalysisController
 * ------------------
 * Handles file upload and analysis job creation.
 * Works for both authenticated users and guest sessions.
 *
 * API Endpoints:
 *   POST /api/analysis          → createJob()
 *   GET  /api/analysis/{id}     → status()
 *   GET  /api/analysis/{id}/result → result()
 */
class AnalysisController extends Controller
{
    public function __construct(
        private AnalysisService $analysisService,
        private QuotaService $quota,
    ) {}

    /**
     * POST /api/analysis
     * Accept uploaded files, validate quota, create job, dispatch worker.
     */
    public function createJob(UploadAnalysisRequest $request): JsonResponse
    {
        try {
            $user       = Auth::user();
            $guestToken = $request->input('guest_token') ?? $request->session()->get('guest_token');

            \Illuminate\Support\Facades\Log::channel('trapix')->info('CreateJob Attempt', [
                'user_id'    => $user?->id,
                'email'      => $user?->email,
                'guest_token' => $guestToken,
                'files_count' => count($request->file('files') ?? []),
                'ip'         => $request->ip(),
            ]);

            // ── Issue guest token if not authenticated ─────────────────────────────
            if (! $user && ! $guestToken) {
                $guestToken = Str::random(48);
                $request->session()->put('guest_token', $guestToken);
            }

            // ── Quota check ────────────────────────────────────────────────────────
            $quota = $this->quota->check($user, $guestToken);
            if (! $quota['allowed']) {
                \Illuminate\Support\Facades\Log::channel('trapix')->warning('Quota Denied', [
                    'user_id' => $user?->id,
                    'reason'  => $quota['reason'],
                ]);
                return response()->json([
                    'error'         => $quota['reason'],
                    'upgrade'       => $quota['upgrade'],
                    'require_login' => $quota['require_login'] ?? false,
                    'redirect_to'   => $quota['redirect_to'] ?? null,
                ], 429);
            }

            // ── Create the job ─────────────────────────────────────────────────────
            $files     = $request->file('files'); // array
            $skipVt    = (bool) $request->input('skip_vt', false);
            $vtApiKey  = $request->input('vt_api_key');

            // Decode the 'options' JSON blob from the frontend
            $rawOptions = $request->input('options');
            $decoded = is_string($rawOptions) ? (json_decode($rawOptions, true) ?? []) : ($rawOptions ?? []);

            // Build clean options structure: preserve nested keys
            $options = [
                'skip_vt'         => $skipVt,
                'vt_api_key'      => $vtApiKey,
                'tools'           => $decoded['tools'] ?? [],
                'hash_algorithms' => $decoded['hash_algorithms'] ?? [],
                'ai'              => (bool) ($decoded['ai'] ?? false),
            ];

            $job = $this->analysisService->createJob(
                files: $files,
                userId: $user?->id,
                guestToken: $guestToken,
                options: $options,
            );

            \Illuminate\Support\Facades\Log::channel('trapix')->info('Job Created Successfully', [
                'job_id' => $job->id,
                'user_id' => $user?->id,
            ]);

            return response()->json([
                'job_id'      => $job->id,
                'status'      => $job->status,
                'file_count'  => $job->file_count,
                'guest_token' => $guestToken,
                'poll_url'    => route('api.analysis.status', $job->id),
            ], 202);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('trapix')->error('CreateJob Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Internal server error during job creation. Developers have been notified.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /api/analysis/{id}
     * Poll job status for frontend progress indicator.
     */
    public function status(string $id): JsonResponse
    {
        $job = $this->analysisService->getJobResult($id);

        if (! $job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        $this->authorizeJobAccess($job);

        return response()->json([
            'job_id'       => $job->id,
            'status'       => $job->status,
            'file_count'   => $job->file_count,
            'started_at'   => $job->started_at?->toIsoString(),
            'completed_at' => $job->completed_at?->toIsoString(),
            'duration_s'   => $job->durationSeconds(),
        ]);
    }

    /**
     * GET /api/analysis/{id}/result
     * Return full analysis result JSON.
     */
    public function result(string $id): JsonResponse
    {
        $job = $this->analysisService->getJobResult($id);

        if (! $job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        $this->authorizeJobAccess($job);

        if (! $job->isCompleted()) {
            return response()->json([
                'error'  => 'Analysis not yet complete',
                'status' => $job->status,
            ], 409);
        }

        return response()->json([
            'job_id'     => $job->id,
            'status'     => $job->status,
            'result'     => $job->result,
            'risk_level' => $job->report?->risk_level,
            'has_pdf'    => (bool) $job->report?->pdf_path,
            'pdf_url'    => $job->report?->pdf_path
                            ? route('api.analysis.report.download', $job->id)
                            : null,
            'files'      => $job->files->map(fn($f) => [
                'name' => $f->original_name,
                'size' => $f->size_bytes,
                'sha256' => $f->sha256,
            ]),
        ]);
    }

    /**
     * POST /api/analysis/{jobId}/run-ai
     * Trigger AI analysis on-demand for a completed job.
     */
    public function runAi(Request $request, string $jobId): JsonResponse
    {
        $job = \App\Models\AnalysisJob::findOrFail($jobId);
        $this->authorizeJobAccess($job);

        if (! $job->isCompleted() || ! $job->result) {
            return response()->json(['error' => 'Job must be completed successfully first.'], 400);
        }

        try {
            $aiResponse = app(\App\Services\AI\AiAnalysisService::class)->run($job);
            
            return response()->json([
                'success'  => true,
                'insights' => $aiResponse->insights,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('On-demand AI Analysis failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'AI analysis failed: ' . $e->getMessage()], 500);
        }
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Verify that the current request has access to the given job.
     * Admin can see all; users see their own; guests see by token.
     */
    private function authorizeJobAccess(\App\Models\AnalysisJob $job): void
    {
        $user = Auth::user();

        if ($user?->isAdmin()) {
            return;
        }

        if ($user && $job->user_id !== $user->id) {
            abort(403, 'Access denied.');
        }

        if (! $user) {
            $token = request()->input('guest_token')
                ?? request()->session()->get('guest_token');

            if ($job->guest_token !== $token) {
                abort(403, 'Access denied.');
            }
        }
    }
}
