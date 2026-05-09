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
        $user       = Auth::user();
        $guestToken = $request->input('guest_token') ?? $request->session()->get('guest_token');

        // ── Issue guest token if not authenticated ─────────────────────────────
        if (! $user && ! $guestToken) {
            $guestToken = Str::random(48);
            $request->session()->put('guest_token', $guestToken);
        }

        // ── Quota check ────────────────────────────────────────────────────────
        $quota = $this->quota->check($user, $guestToken);
        if (! $quota['allowed']) {
            return response()->json([
                'error'   => $quota['reason'],
                'upgrade' => $quota['upgrade'],
            ], 429);
        }

        // ── Create the job ─────────────────────────────────────────────────────
        $files   = $request->file('files'); // array
        $options = $request->only(['skip_vt', 'vt_api_key']);

        $job = $this->analysisService->createJob(
            files: $files,
            userId: $user?->id,
            guestToken: $guestToken,
            options: $options,
        );

        return response()->json([
            'job_id'      => $job->id,
            'status'      => $job->status,
            'file_count'  => $job->file_count,
            'guest_token' => $guestToken,
            'poll_url'    => route('api.analysis.status', $job->id),
        ], 202);
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
                            ? route('api.analysis.report', $job->id)
                            : null,
            'files'      => $job->files->map(fn($f) => [
                'name' => $f->original_name,
                'size' => $f->size_bytes,
                'sha256' => $f->sha256,
            ]),
        ]);
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
