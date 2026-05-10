<?php

namespace App\Http\Controllers;

use App\Models\AnalysisJob;
use App\Models\AnalysisReport;
use App\Models\ReportDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

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
        $pdf = Pdf::loadView('pdf.report', compact('job'));
        return $pdf->download("trapix_report_{$jobId}.pdf");
    }

    /**
     * GET /analysis/{jobId}/export-zip
     * Generate a ZIP archive containing the PDF, JSON results, and IOCs.
     */
    public function exportZip(Request $request, string $jobId)
    {
        $job = AnalysisJob::with(['report', 'aiResponse', 'files'])->findOrFail($jobId);

        $this->authorizeAccess($job, Auth::user(), $request);

        $zip = new \ZipArchive();
        $zipFileName = "trapix_export_{$jobId}.zip";
        $zipPath = storage_path("app/private/{$zipFileName}");

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            // 1. PDF Report
            $pdf = Pdf::loadView('pdf.report', compact('job'));
            $zip->addFromString('report.pdf', $pdf->output());

            // 2. Raw JSON Results
            $zip->addFromString('raw_analysis.json', json_encode($job->result, JSON_PRETTY_PRINT));

            // 3. AI Insights
            if ($job->aiResponse) {
                $zip->addFromString('ai_insights.json', json_encode($job->aiResponse->toArray(), JSON_PRETTY_PRINT));
            }

            // 4. IOCs text file
            $iocsText = "Extracted IOCs\n==============\n\n";
            $results = $job->result ?? [];
            if (isset($results['results'])) {
                $results = $results['results'][0] ?? [];
            }
            if (isset($results['iocs'])) {
                foreach ($results['iocs'] as $type => $items) {
                    $iocsText .= strtoupper($type) . ":\n";
                    if (is_array($items)) {
                        foreach ($items as $item) {
                            $iocsText .= "- " . $item . "\n";
                        }
                    } else {
                        $iocsText .= "- " . $items . "\n";
                    }
                    $iocsText .= "\n";
                }
            } else {
                $iocsText .= "No IOCs found.\n";
            }
            $zip->addFromString('iocs.txt', $iocsText);

            $zip->close();
        } else {
            abort(500, 'Failed to create ZIP archive.');
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
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
