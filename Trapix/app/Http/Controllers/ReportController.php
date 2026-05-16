<?php

namespace App\Http\Controllers;

use App\Models\AnalysisJob;
use App\Models\AnalysisReport;
use App\Models\ReportDownload;
use App\Services\Report\ReportBuilder;
use App\Services\Report\IOCExtractor;
use App\Http\Resources\EnterpriseReportResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;

/**
 * ReportController
 * ----------------
 * Handles PDF report downloads with plan-based download limits.
 * Supports enterprise report generation in multiple formats.
 */
class ReportController extends Controller
{
    private ReportBuilder $reportBuilder;
    private IOCExtractor $iocExtractor;

    public function __construct(ReportBuilder $reportBuilder, IOCExtractor $iocExtractor)
    {
        $this->reportBuilder = $reportBuilder;
        $this->iocExtractor = $iocExtractor;
    }

    /**
     * GET /analysis/{jobId}/report
     * Download the PDF report, enforcing download limits for free users.
     */
    public function download(Request $request, string $jobId)
    {
        $job    = AnalysisJob::with(['report', 'user', 'files', 'aiResponse'])->findOrFail($jobId);
        $report = $job->report;

        // ── Access control ─────────────────────────────────────────────────────
        $user = Auth::user();
        $this->authorizeAccess($job, $user, $request);

        // ── Download limit enforcement ─────────────────────────────────────────
        if ($user && $report) {
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
        if ($report) {
            ReportDownload::create([
                'analysis_report_id' => $report->id,
                'user_id'            => $user?->id,
                'ip_address'         => $request->ip(),
                'user_agent'         => $request->userAgent(),
            ]);
            $report->increment('download_count');
        }

        // ── Generate fresh PDF dynamically (cached pdf_path optional) ──────────
        $pdf = Pdf::loadView('pdf.report', compact('job'));
        return $pdf->download("trapix_report_{$jobId}.pdf");
    }

    /**
     * GET /analysis/{jobId}/report-html
     * Display report as HTML (for web viewing)
     */
    public function view(Request $request, string $jobId)
    {
        $job = AnalysisJob::findOrFail($jobId);
        $user = Auth::user();
        $this->authorizeAccess($job, $user, $request);

        return view('pdf.report', compact('job'));
    }

    /**
     * GET /analysis/{jobId}/report-api
     * Get report as API response (JSON)
     */
    public function api(Request $request, string $jobId)
    {
        $job = AnalysisJob::findOrFail($jobId);
        $user = Auth::user();
        $this->authorizeAccess($job, $user, $request);

        return new EnterpriseReportResource($job);
    }

    /**
     * GET /analysis/{jobId}/report-preview
     * Get quick preview of report
     */
    public function preview(Request $request, string $jobId)
    {
        $job = AnalysisJob::findOrFail($jobId);
        $user = Auth::user();
        $this->authorizeAccess($job, $user, $request);

        $preview = $this->reportBuilder->buildPreview($job);
        return response()->json($preview);
    }

    /**
     * GET /analysis/{jobId}/report-threat-intel
     * Get threat intelligence focused report
     */
    public function threatIntel(Request $request, string $jobId)
    {
        $job = AnalysisJob::findOrFail($jobId);
        $user = Auth::user();
        $this->authorizeAccess($job, $user, $request);

        $report = $this->reportBuilder->buildThreatIntelReport($job);
        return response()->json($report);
    }

    /**
     * GET /analysis/{jobId}/report-dfir
     * Get DFIR (Digital Forensics & Incident Response) focused report
     */
    public function dfir(Request $request, string $jobId)
    {
        $job = AnalysisJob::findOrFail($jobId);
        $user = Auth::user();
        $this->authorizeAccess($job, $user, $request);

        $report = $this->reportBuilder->buildDFIRReport($job);
        return response()->json($report);
    }

    /**
     * GET /analysis/{jobId}/report-soc
     * Get SOC (Security Operations Center) focused report
     */
    public function soc(Request $request, string $jobId)
    {
        $job = AnalysisJob::findOrFail($jobId);
        $user = Auth::user();
        $this->authorizeAccess($job, $user, $request);

        $report = $this->reportBuilder->buildSOCReport($job);
        return response()->json($report);
    }

    /**
     * GET /analysis/{jobId}/export-json
     * Export report as JSON
     */
    public function exportJson(Request $request, string $jobId)
    {
        $job = AnalysisJob::findOrFail($jobId);
        $user = Auth::user();
        $this->authorizeAccess($job, $user, $request);

        $json = $this->reportBuilder->exportJSON($job);

        return response($json, 200)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=trapix-report-{$jobId}.json");
    }

    /**
     * GET /analysis/{jobId}/export-stix
     * Export IOCs as STIX 2.1 format
     */
    public function exportStix(Request $request, string $jobId)
    {
        $job = AnalysisJob::findOrFail($jobId);
        $user = Auth::user();
        $this->authorizeAccess($job, $user, $request);

        $stix = $this->reportBuilder->exportSTIX($job);

        return response($stix, 200)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename=trapix-stix-{$jobId}.json");
    }

    /**
     * GET /analysis/{jobId}/export-iocs
     * Export IOCs as CSV
     */
    public function exportIocs(Request $request, string $jobId)
    {
        $job = AnalysisJob::findOrFail($jobId);
        $user = Auth::user();
        $this->authorizeAccess($job, $user, $request);

        $result = $job->result ?? [];
        $analysisResults = $result['results'] ?? [$result];
        
        $allIocs = [];
        foreach ($analysisResults as $fileAnalysis) {
            if (!empty($fileAnalysis)) {
                $iocs = $this->iocExtractor->extractIOCs($fileAnalysis);
                $allIocs = array_merge($allIocs, $iocs);
            }
        }

        $csv = $this->iocExtractor->exportCSV($allIocs);

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=trapix-iocs-{$jobId}.csv");
    }

    /**
     * GET /analysis/{jobId}/export-zip
     * Generate a ZIP archive containing the PDF, JSON results, and IOCs.
     */
    public function exportZip(Request $request, string $jobId)
    {
        $job = AnalysisJob::with(['report', 'aiResponse', 'files'])->findOrFail($jobId);

        $this->authorizeAccess($job, Auth::user(), $request);

        $zip = new ZipArchive();
        $zipFileName = "trapix_export_{$jobId}.zip";
        $privatePath = storage_path('app/private');
        if (! is_dir($privatePath)) {
            mkdir($privatePath, 0755, true);
        }
        $zipPath = $privatePath . DIRECTORY_SEPARATOR . $zipFileName;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            // 1. Professional PDF Report
            $pdf = Pdf::loadView('pdf.report', compact('job'));
            $zip->addFromString('report.pdf', $pdf->output());

            // 2. Enterprise Report JSON
            $enterpriseReport = $this->reportBuilder->build($job);
            $zip->addFromString('enterprise-report.json', json_encode($enterpriseReport, JSON_PRETTY_PRINT));

            // 3. Raw Analysis Results
            $zip->addFromString('raw_analysis.json', json_encode($job->result, JSON_PRETTY_PRINT));

            // 4. AI Insights
            if ($job->aiResponse) {
                $zip->addFromString('ai_insights.json', json_encode($job->aiResponse->toArray(), JSON_PRETTY_PRINT));
            }

            // 5. IOCs CSV
            $result = $job->result ?? [];
            $analysisResults = $result['results'] ?? [$result];
            
            $allIocs = [];
            foreach ($analysisResults as $fileAnalysis) {
                if (!empty($fileAnalysis)) {
                    $iocs = $this->iocExtractor->extractIOCs($fileAnalysis);
                    $allIocs = array_merge($allIocs, $iocs);
                }
            }

            $csv = $this->iocExtractor->exportCSV($allIocs);
            $zip->addFromString('iocs.csv', $csv);

            // 6. STIX format
            $stix = $this->reportBuilder->exportSTIX($job);
            $zip->addFromString('indicators-stix.json', $stix);

            // 7. Threat Intelligence Report
            $threatReport = $this->reportBuilder->buildThreatIntelReport($job);
            $zip->addFromString('threat-intelligence.json', json_encode($threatReport, JSON_PRETTY_PRINT));

            // 8. DFIR Report
            $dfirReport = $this->reportBuilder->buildDFIRReport($job);
            $zip->addFromString('dfir-report.json', json_encode($dfirReport, JSON_PRETTY_PRINT));

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
