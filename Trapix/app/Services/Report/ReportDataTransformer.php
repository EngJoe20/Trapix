<?php

namespace App\Services\Report;

use App\Models\AnalysisJob;

/**
 * ReportDataTransformer
 *
 * Transforms raw analysis data from Python analyzer and AI insights into
 * structured, professional report data. Handles normalization, enrichment,
 * and correlation across all data sources.
 */
class ReportDataTransformer
{
    private RiskScoringEngine $riskEngine;
    private IOCExtractor $iocExtractor;

    public function __construct(
        ?RiskScoringEngine $riskEngine = null,
        ?IOCExtractor $iocExtractor = null
    ) {
        $this->riskEngine = $riskEngine ?? new RiskScoringEngine();
        $this->iocExtractor = $iocExtractor ?? new IOCExtractor();
    }

    /**
     * Transform complete AnalysisJob into report-ready data
     *
     * @param AnalysisJob $job
     * @return array Professional report data structure
     */
    public function transform(AnalysisJob $job): array
    {
        $result = $job->result ?? [];
        $aiInsights = $job->aiResponse?->insights ?? [];

        return [
            'metadata'          => $this->transformMetadata($job),
            'job_info'          => $this->transformJobInfo($job),
            'file_analyses'     => $this->transformFileAnalyses($job, $result),
            'risk_assessment'   => $this->transformRiskAssessment($result),
            'threat_overview'   => $this->transformThreatOverview($result, $aiInsights),
            'static_analysis'   => $this->transformStaticAnalysis($result),
            'virustotal_intel'  => $this->transformVirusTotalIntel($result),
            'suspicious_apis'   => $this->transformSuspiciousAPIs($result),
            'ioc_extraction'    => $this->transformIOCs($result),
            'strings_analysis'  => $this->transformStrings($result),
            'behavioral_intel'  => $this->transformBehavioralIntel($aiInsights),
            'mitre_mapping'     => $this->transformMITRE($aiInsights),
            'recommendations'   => $this->transformRecommendations($result, $aiInsights),
            'executive_summary' => $this->generateExecutiveSummary($job, $result, $aiInsights),
        ];
    }

    /**
     * Transform metadata (dates, timestamps, etc.)
     */
    private function transformMetadata(AnalysisJob $job): array
    {
        return [
            'report_generated_at' => now()->format('Y-m-d H:i:s T'),
            'analysis_started_at' => $job->started_at?->format('Y-m-d H:i:s T'),
            'analysis_completed_at' => $job->completed_at?->format('Y-m-d H:i:s T'),
            'total_duration_seconds' => $job->completed_at ? $job->completed_at->diffInSeconds($job->started_at) : null,
            'report_version' => '1.0',
            'platform' => 'Trapix Security Analyzer',
        ];
    }

    /**
     * Transform job identification and context
     */
    private function transformJobInfo(AnalysisJob $job): array
    {
        return [
            'job_id'        => $job->id,
            'user_id'       => $job->user_id,
            'user_name'     => $job->user?->name ?? 'Guest',
            'user_email'    => $job->user?->email ?? 'N/A',
            'status'        => $job->status,
            'error_message' => $job->error_message,
            'file_count'    => $job->file_count,
            'files'         => $job->files->map(fn($f) => [
                'id'              => $f->id,
                'original_name'   => $f->original_name,
                'path'            => $f->path,
                'sha256'          => $f->sha256,
            ])->toArray(),
        ];
    }

    /**
     * Transform per-file analysis results
     */
    private function transformFileAnalyses(AnalysisJob $job, array $result): array
    {
        $files = [];
        $analysisResults = $result['results'] ?? [$result];
        // Normalize collection to 0-indexed array for safe offset access
        $uploadedFiles = $job->files->values();

        foreach ($analysisResults as $idx => $fileAnalysis) {
            if (empty($fileAnalysis)) {
                continue;
            }

            $fileName = $fileAnalysis['file_name'] ?? $uploadedFiles->get($idx)?->original_name ?? "File {$idx}";

            $fileRisk = $this->riskEngine->calculateFileRisk($fileAnalysis);
            $iocs = $this->iocExtractor->extractIOCs($fileAnalysis);

            $files[] = [
                'index'            => $idx,
                'file_name'        => $fileName,
                'file_size'        => $fileAnalysis['file_size'] ?? 0,
                'file_type'        => $fileAnalysis['file_type'] ?? 'Unknown',
                'hashes'           => $fileAnalysis['hashes'] ?? [],
                'risk_assessment'  => $fileRisk,
                'virustotal'       => $this->normalizeVirusTotal($fileAnalysis['virustotal'] ?? []),
                'pe_info'          => $this->normalizePEInfo($fileAnalysis['pe_info'] ?? []),
                'packer'           => $fileAnalysis['packer'] ?? [],
                'entropy'          => $this->normalizeEntropy($fileAnalysis['entropy'] ?? []),
                'suspicious_apis'  => $fileAnalysis['suspicious_apis'] ?? [],
                'iocs'             => $this->iocExtractor->categorizeForReport($iocs),
                'imports_exports'  => $this->normalizeImportsExports($fileAnalysis['pe_info'] ?? []),
                'strings_summary'  => $this->summarizeStrings($fileAnalysis['strings'] ?? []),
            ];
        }

        return $files;
    }

    /**
     * Transform risk assessment section
     */
    private function transformRiskAssessment(array $result): array
    {
        $analysisResults = $result['results'] ?? [$result];
        $fileRisks = [];

        foreach ($analysisResults as $fileAnalysis) {
            if (!empty($fileAnalysis)) {
                $fileRisks[] = $this->riskEngine->calculateFileRisk($fileAnalysis);
            }
        }

        $overallRisk = $this->riskEngine->calculateJobRisk($fileRisks);

        return [
            'overall_risk'  => $overallRisk,
            'file_risks'    => $fileRisks,
            'risk_summary'  => [
                'critical_count' => count(array_filter($fileRisks, fn($r) => $r['level'] === 'CRITICAL')),
                'high_count'     => count(array_filter($fileRisks, fn($r) => $r['level'] === 'HIGH')),
                'medium_count'   => count(array_filter($fileRisks, fn($r) => $r['level'] === 'MEDIUM')),
                'low_count'      => count(array_filter($fileRisks, fn($r) => $r['level'] === 'LOW')),
                'clean_count'    => count(array_filter($fileRisks, fn($r) => $r['level'] === 'CLEAN')),
            ],
        ];
    }

    /**
     * Generate threat overview
     */
    private function transformThreatOverview(array $result, array $aiInsights): array
    {
        // Safely access the first file result, falling back to the root result
        $firstFile = $result['results'][0] ?? $result;

        return [
            'summary'          => $aiInsights['risk_assessment']['risk_justification'] ?? 'Multiple indicators detected',
            'overall_level'    => $result['summary']['risk_level'] ?? 'UNKNOWN',
            'threat_label'     => $firstFile['virustotal']['threat_label'] ?? $aiInsights['packing']['packer'] ?? 'Unclassified',
            'is_packed'        => $firstFile['packer']['is_packed'] ?? false,
            'high_entropy'     => (($firstFile['entropy']['file_entropy'] ?? 0) > 7.5),
            'vt_detection_ratio' => $firstFile['virustotal']['detection_ratio'] ?? '0/0',
        ];
    }

    /**
     * Transform static analysis section
     */
    private function transformStaticAnalysis(array $result): array
    {
        $firstFile = $result['results'][0] ?? $result;

        return [
            'pe_structure'     => [
                'machine_type'     => $firstFile['pe_info']['machine_type'] ?? 'Unknown',
                'entry_point'      => $firstFile['pe_info']['entry_point'] ?? 'Unknown',
                'subsystem'        => $firstFile['pe_info']['subsystem'] ?? 'Unknown',
                'imphash'          => $firstFile['pe_info']['imphash'] ?? 'N/A',
                'section_count'    => count($firstFile['pe_info']['sections'] ?? []),
                'sections'         => $this->formatPESections($firstFile['pe_info']['sections'] ?? []),
            ],
            'entropy_analysis'  => [
                'file_entropy'     => $firstFile['entropy']['file_entropy'] ?? 0,
                'overall_suspicious' => $firstFile['entropy']['overall_suspicious'] ?? false,
                'section_analysis' => $firstFile['entropy']['section_entropies'] ?? [],
            ],
            'packer_detection'  => [
                'is_packed'        => $firstFile['packer']['is_packed'] ?? false,
                'packer_name'      => $firstFile['packer']['packer_name'] ?? 'None',
                'confidence'       => $firstFile['packer']['confidence'] ?? 'N/A',
                'warnings'         => $firstFile['packer']['warnings'] ?? [],
            ],
        ];
    }

    /**
     * Transform VirusTotal intelligence
     */
    private function transformVirusTotalIntel(array $result): array
    {
        $firstFile = $result['results'][0] ?? $result;
        $vt = $firstFile['virustotal'] ?? [];

        if (!($vt['queried'] ?? false)) {
            return [
                'queried'    => false,
                'reason'     => $vt['error'] ?? 'VirusTotal query skipped',
                'detections' => [],
            ];
        }

        return [
            'queried'           => true,
            'found'             => $vt['found'] ?? false,
            'malicious'         => $vt['malicious'] ?? 0,
            'suspicious'        => $vt['suspicious'] ?? 0,
            'total_engines'     => $vt['total_engines'] ?? 0,
            'detection_ratio'   => $vt['detection_ratio'] ?? '0/0',
            'threat_label'      => $vt['threat_label'] ?? 'Unknown',
            'scan_date'         => $vt['scan_date'] ?? 'Never',
            'vendor_detections' => array_slice($vt['detections'] ?? [], 0, 10),
        ];
    }

    /**
     * Transform suspicious APIs section
     */
    private function transformSuspiciousAPIs(array $result): array
    {
        $firstFile = $result['results'][0] ?? $result;
        $apis = $firstFile['suspicious_apis'] ?? [];

        $categorized = [];
        foreach ($apis as $api => $info) {
            $severity = is_array($info) ? ($info['severity'] ?? 'medium') : 'medium';
            $category = is_array($info) ? ($info['category'] ?? 'unknown') : 'unknown';
            $reason = is_array($info) ? ($info['reason'] ?? '') : '';

            if (!isset($categorized[$category])) {
                $categorized[$category] = [];
            }

            $categorized[$category][] = [
                'api'       => $api,
                'severity'  => $severity,
                'reason'    => $reason,
            ];
        }

        return [
            'total_count'  => count($apis),
            'by_category'  => $categorized,
        ];
    }

    /**
     * Transform IOC extraction section
     */
    private function transformIOCs(array $result): array
    {
        $firstFile = $result['results'][0] ?? $result;
        $iocs = $this->iocExtractor->extractIOCs($firstFile);
        $categorized = $this->iocExtractor->categorizeForReport($iocs);

        return $categorized;
    }

    /**
     * Transform strings analysis
     */
    private function transformStrings(array $result): array
    {
        $firstFile = $result['results'][0] ?? $result;
        $strings = $firstFile['strings'] ?? [];

        return [
            'total_ascii'    => count($strings['ascii'] ?? []),
            'total_unicode'  => count($strings['unicode'] ?? []),
            'total_strings'  => (count($strings['ascii'] ?? []) + count($strings['unicode'] ?? [])),
            'samples'        => [
                'ascii'   => array_slice($strings['ascii'] ?? [], 0, 10),
                'unicode' => array_slice($strings['unicode'] ?? [], 0, 10),
            ],
        ];
    }

    /**
     * Transform behavioral intelligence from AI insights
     */
    private function transformBehavioralIntel(array $aiInsights): array
    {
        return [
            'insights'        => $aiInsights['behavioral_insights'] ?? [],
            'summary'         => $aiInsights['packing']['impact_on_analysis'] ?? 'Analysis complete',
            'capabilities'    => $this->extractCapabilities($aiInsights),
        ];
    }

    /**
     * Transform MITRE ATT&CK framework mappings
     */
    private function transformMITRE(array $aiInsights): array
    {
        $techniques = [];

        foreach ($aiInsights['behavioral_insights'] ?? [] as $insight) {
            if (isset($insight['mitre_attack'])) {
                $techniques[] = [
                    'tactic'       => $insight['mitre_attack']['tactic'] ?? 'Unknown',
                    'technique'    => $insight['mitre_attack']['technique'] ?? 'Unknown',
                    'evidence'     => implode(', ', $insight['evidence'] ?? []),
                    'severity'     => $insight['severity'] ?? 'medium',
                ];
            }
        }

        return [
            'techniques' => $techniques,
            'summary'    => count($techniques) . ' unique ATT&CK techniques identified',
        ];
    }

    /**
     * Transform recommendations section
     */
    private function transformRecommendations(array $result, array $aiInsights): array
    {
        $recommendations = [];

        $overallRisk = $result['summary']['risk_level'] ?? 'UNKNOWN';

        if ($overallRisk === 'CRITICAL') {
            $recommendations[] = [
                'priority' => 'IMMEDIATE',
                'action'   => 'Isolate affected system from network immediately',
                'type'     => 'containment',
            ];
            $recommendations[] = [
                'priority' => 'IMMEDIATE',
                'action'   => 'Engage incident response and threat hunting teams',
                'type'     => 'ir',
            ];
        } elseif ($overallRisk === 'HIGH') {
            $recommendations[] = [
                'priority' => 'URGENT',
                'action'   => 'Move to isolated VLAN for further analysis',
                'type'     => 'containment',
            ];
        }

        // Always include DFIR actions
        $recommendations[] = [
            'priority' => 'NORMAL',
            'action'   => 'Preserve full memory dump and disk image for forensic analysis',
            'type'     => 'dfir',
        ];

        return $recommendations;
    }

    /**
     * Generate executive summary
     */
    private function generateExecutiveSummary(AnalysisJob $job, array $result, array $aiInsights): array
    {
        $summary = '';
        $firstFile = $result['results'][0] ?? $result;
        $riskLevel = $result['summary']['risk_level'] ?? 'UNKNOWN';
        $threat = $firstFile['virustotal']['threat_label'] ?? 'Unknown threat';

        if ($riskLevel === 'CRITICAL') {
            $summary = "CRITICAL THREAT DETECTED: This file exhibits strong indicators of malware ({$threat}). "
                     . "Multiple detection engines, suspicious behavioral patterns, and potential execution capabilities "
                     . "indicate immediate threat. Recommend urgent containment and incident response activation.";
        } elseif ($riskLevel === 'HIGH') {
            $summary = "HIGH-RISK FILE: This file contains significant malware indicators ({$threat}). "
                     . "Recommend advanced analysis, memory forensics, and isolation pending investigation.";
        } elseif ($riskLevel === 'MEDIUM') {
            $summary = "MEDIUM RISK: This file exhibits some suspicious characteristics ({$threat}). "
                     . "Recommend further investigation and monitoring in sandbox environment.";
        } else {
            $summary = "LOW RISK OR CLEAN: This file appears benign based on available analysis. "
                     . "However, continued monitoring is recommended for new threat signatures.";
        }

        $maliciousCount = (int) ($firstFile['virustotal']['malicious'] ?? 0);

        return [
            'assessment'        => $summary,
            'risk_level'        => $riskLevel,
            'threat_label'      => $threat,
            'confidence'        => $maliciousCount > 10 ? 'VERY HIGH' : 'HIGH',
            'analyst_notes'     => $aiInsights['analysis_metadata']['analysis_time'] ?? '',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helper Normalization Methods
    // ─────────────────────────────────────────────────────────────────────

    private function normalizeVirusTotal(array $vt): array
    {
        return [
            'queried'        => $vt['queried'] ?? false,
            'found'          => $vt['found'] ?? false,
            'malicious'      => $vt['malicious'] ?? 0,
            'suspicious'     => $vt['suspicious'] ?? 0,
            'total_engines'  => $vt['total_engines'] ?? 0,
            'detection_ratio' => $vt['detection_ratio'] ?? '0/0',
            'threat_label'   => $vt['threat_label'] ?? 'Unknown',
        ];
    }

    private function normalizePEInfo(array $pe): array
    {
        return [
            'machine_type'   => $pe['machine_type'] ?? 'Unknown',
            'entry_point'    => $pe['entry_point'] ?? 'Unknown',
            'subsystem'      => $pe['subsystem'] ?? 'Unknown',
            'imphash'        => $pe['imphash'] ?? 'N/A',
            'sections'       => $this->formatPESections($pe['sections'] ?? []),
        ];
    }

    private function normalizeEntropy(array $entropy): array
    {
        return [
            'file_entropy'        => $entropy['file_entropy'] ?? 0,
            'overall_suspicious'  => $entropy['overall_suspicious'] ?? false,
            'section_entropies'   => $entropy['section_entropies'] ?? [],
        ];
    }

    private function normalizeImportsExports(array $pe): array
    {
        return [
            'imports'  => array_keys($pe['imports'] ?? []),
            'exports'  => $pe['exports'] ?? [],
        ];
    }

    private function formatPESections(array $sections): array
    {
        return array_map(fn($sec) => [
            'name'    => $sec['name'] ?? 'Unknown',
            'vaddr'   => $sec['vaddr'] ?? 0,
            'vsize'   => $sec['vsize'] ?? 0,
            'entropy' => $sec['entropy'] ?? 0,
        ], $sections);
    }

    private function summarizeStrings(array $strings): array
    {
        return [
            'total_count'   => (count($strings['ascii'] ?? []) + count($strings['unicode'] ?? [])),
            'ascii_count'   => count($strings['ascii'] ?? []),
            'unicode_count' => count($strings['unicode'] ?? []),
            'samples'       => array_slice($strings['ascii'] ?? [], 0, 5),
        ];
    }

    private function extractCapabilities(array $aiInsights): array
    {
        $capabilities = [];

        foreach ($aiInsights['behavioral_insights'] ?? [] as $insight) {
            $capabilities[] = [
                'capability' => $insight['title'] ?? 'Unknown',
                'severity'   => $insight['severity'] ?? 'medium',
                'description' => $insight['detail'] ?? '',
            ];
        }

        return $capabilities;
    }
}
