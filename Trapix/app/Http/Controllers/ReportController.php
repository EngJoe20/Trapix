<?php

namespace App\Http\Controllers;

use App\Models\AnalysisJob;
use App\Models\AnalysisReport;
use App\Models\ReportDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * ReportController
 * ----------------
 * Handles PDF report downloads with plan-based download limits.
 */
class ReportController extends Controller
{
    /**
     * GET /analysis/{jobId}/report
     * Download the PDF report, enforcing download limits for free users.
     */
    public function download(Request $request, string $jobId)
    {
        $job    = AnalysisJob::with('report')->findOrFail($jobId);
        $report = $job->report;

        abort_if(! $report || ! $report->pdf_path, 404, 'No report available for this analysis.');

        // ── Access control ─────────────────────────────────────────────────────
        $user = Auth::user();
        $this->authorizeAccess($job, $user, $request);

        // ── Download limit enforcement ─────────────────────────────────────────
        if ($user) {
            $plan = $user->effectivePlan();
            if ($plan && ! $plan->report_downloads_unlimited) {
                $downloadedByUser = ReportDownload::where('analysis_report_id', $report->id)
                    ->where('user_id', $user->id)
                    ->count();

                if ($downloadedByUser >= $plan->report_download_limit) {
                    return response()->json([
                        'error'   => "You have reached the download limit ({$plan->report_download_limit}) for this report on your current plan.",
                        'upgrade' => true,
                    ], 403);
                }
            }
        }

        // ── Log download ───────────────────────────────────────────────────────
        ReportDownload::create([
            'analysis_report_id' => $report->id,
            'user_id'            => $user?->id,
            'ip_address'         => $request->ip(),
            'user_agent'         => $request->userAgent(),
        ]);

        $report->increment('download_count');

        // ── Stream PDF ─────────────────────────────────────────────────────────
        $fullPath = Storage::disk($report->disk ?? 'local')->path($report->pdf_path);

        abort_unless(file_exists($fullPath), 404, 'Report file missing.');

        return response()->download(
            $fullPath,
            "trapix_report_{$jobId}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function authorizeAccess(AnalysisJob $job, $user, Request $request): void
    {
        if ($user?->isAdmin()) {
            return;
        }

        if ($user && $job->user_id === $user->id) {
            return;
        }

        // Guest access by token
        if (! $user) {
            $token = $request->input('guest_token')
                ?? $request->session()->get('guest_token');

            if ($job->guest_token && $job->guest_token === $token) {
                return;
            }
        }

        abort(403, 'Access denied.');
    }
}
