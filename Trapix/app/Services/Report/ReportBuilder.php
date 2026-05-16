<?php

namespace App\Services\Report;

use App\Models\AnalysisJob;

/**
 * ReportBuilder
 *
 * Orchestrates the complete report generation pipeline.
 * Coordinates data transformation, risk scoring, and IOC extraction
 * into a single, unified report structure.
 */
class ReportBuilder
{
    private ReportDataTransformer $transformer;
    private RiskScoringEngine $riskEngine;
    private IOCExtractor $iocExtractor;

    public function __construct(
        ?ReportDataTransformer $transformer = null,
        ?RiskScoringEngine $riskEngine = null,
        ?IOCExtractor $iocExtractor = null
    ) {
        $this->transformer = $transformer ?? new ReportDataTransformer();
        $this->riskEngine = $riskEngine ?? new RiskScoringEngine();
        $this->iocExtractor = $iocExtractor ?? new IOCExtractor();
    }

    /**
     * Build complete enterprise report from AnalysisJob
     *
     * @param AnalysisJob $job
     * @return array Complete report data structure ready for rendering
     */
    public function build(AnalysisJob $job): array
    {
        $transformedData = $this->transformer->transform($job);

        return [
            'report_metadata'     => $transformedData['metadata'],
            'job_information'     => $transformedData['job_info'],
            'executive_summary'   => $transformedData['executive_summary'],
            'threat_overview'     => $transformedData['threat_overview'],
            'risk_assessment'     => $transformedData['risk_assessment'],
            'file_analyses'       => $transformedData['file_analyses'],
            'static_analysis'     => $transformedData['static_analysis'],
            'virustotal_intel'    => $transformedData['virustotal_intel'],
            'suspicious_apis'     => $transformedData['suspicious_apis'],
            'ioc_extraction'      => $transformedData['ioc_extraction'],
            'strings_analysis'    => $transformedData['strings_analysis'],
            'behavioral_intel'    => $transformedData['behavioral_intel'],
            'mitre_mapping'       => $transformedData['mitre_mapping'],
            'recommendations'     => $transformedData['recommendations'],
            'generated_at'        => now()->timestamp,
        ];
    }

    /**
     * Build minimal report (quick preview)
     */
    public function buildPreview(AnalysisJob $job): array
    {
        $full = $this->build($job);

        return [
            'job_id'              => $full['job_information']['job_id'],
            'file_count'          => $full['job_information']['file_count'],
            'overall_risk'        => $full['risk_assessment']['overall_risk'],
            'threat_summary'      => $full['executive_summary']['assessment'],
            'files'               => collect($full['file_analyses'])
                ->map(fn($f) => [
                    'name'      => $f['file_name'],
                    'risk'      => $f['risk_assessment']['level'],
                    'vt_ratio'  => $f['virustotal']['detection_ratio'] ?? 'Unknown',
                ])
                ->toArray(),
        ];
    }

    /**
     * Build report focused on threat intelligence
     */
    public function buildThreatIntelReport(AnalysisJob $job): array
    {
        $full = $this->build($job);

        return [
            'threat_classification'  => $full['threat_overview'],
            'malware_family'         => $full['threat_overview']['threat_label'],
            'attack_patterns'        => $full['behavioral_intel']['insights'],
            'mitre_techniques'       => $full['mitre_mapping']['techniques'],
            'indicators_of_compromise' => $full['ioc_extraction'],
            'virustotal_intelligence' => $full['virustotal_intel'],
            'recommendations'        => $full['recommendations'],
        ];
    }

    /**
     * Build report for DFIR (Digital Forensics & Incident Response)
     */
    public function buildDFIRReport(AnalysisJob $job): array
    {
        $full = $this->build($job);

        return [
            'case_metadata'          => [
                'job_id'           => $full['job_information']['job_id'],
                'case_opened'      => $full['report_metadata']['analysis_started_at'],
                'evidence_analyzed' => $full['report_metadata']['analysis_completed_at'],
                'examiner'         => $full['job_information']['user_name'],
            ],
            'evidence_details'       => $full['file_analyses'],
            'forensic_findings'      => [
                'file_system_artifacts' => $this->extractForensicArtifacts($full['ioc_extraction']),
                'registry_keys'        => $this->filterIOCsByType($full['ioc_extraction'], 'registry_key'),
                'network_indicators'   => $this->filterIOCsByType($full['ioc_extraction'], 'ip_address'),
            ],
            'timeline_analysis'      => $full['report_metadata'],
            'behavioral_timeline'    => $full['behavioral_intel']['insights'],
            'recommendations'        => $full['recommendations'],
        ];
    }

    /**
     * Build report for security operations center (SOC)
     */
    public function buildSOCReport(AnalysisJob $job): array
    {
        $full = $this->build($job);

        return [
            'alert_summary'          => [
                'severity'              => $full['risk_assessment']['overall_risk']['level'],
                'confidence'            => $full['risk_assessment']['overall_risk']['confidence'],
                'risk_score'            => $full['risk_assessment']['overall_risk']['score'],
            ],
            'threat_details'         => $full['threat_overview'],
            'affected_assets'        => $full['file_analyses'],
            'technical_indicators'   => [
                'suspicious_apis'    => $full['suspicious_apis'],
                'network_indicators' => $this->filterIOCsByType($full['ioc_extraction'], 'ip_address'),
                'file_indicators'    => $this->filterIOCsByType($full['ioc_extraction'], 'file_hash'),
            ],
            'immediate_actions'      => array_filter($full['recommendations'], fn($r) => $r['priority'] === 'IMMEDIATE'),
            'escalation_path'        => $this->buildEscalationPath($full['risk_assessment']['overall_risk']),
            'incident_context'       => $full['behavioral_intel'],
        ];
    }

    /**
     * Export report to JSON format
     */
    public function exportJSON(AnalysisJob $job): string
    {
        $report = $this->build($job);
        return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Export IOCs as STIX 2.1 format
     */
    public function exportSTIX(AnalysisJob $job): string
    {
        $full = $this->build($job);
        $iocs = $full['ioc_extraction']['by_type'] ?? [];

        $objects = [
            [
                'type'         => 'identity',
                'id'           => 'identity--trapix-' . uniqid(),
                'name'         => 'Trapix Security',
                'identity_class' => 'organization',
            ],
        ];

        // Convert IOCs to STIX objects
        foreach ($iocs as $type => $indicators) {
            foreach ($indicators as $ioc) {
                $objects[] = $this->convertToSTIXObject($ioc);
            }
        }

        $bundle = [
            'type'      => 'bundle',
            'id'        => 'bundle--' . uniqid(),
            'objects'   => $objects,
        ];

        return json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helper Methods
    // ─────────────────────────────────────────────────────────────────────

    private function extractForensicArtifacts(array $iocs): array
    {
        return array_filter($iocs['by_type'] ?? [], fn($v, $k) => in_array($k, [
            'file_path', 'registry_key', 'dll', 'mutex', 'named_pipe'
        ]), ARRAY_FILTER_USE_BOTH);
    }

    private function filterIOCsByType(array $iocs, string $type): array
    {
        return $iocs['by_type'][$type] ?? [];
    }

    private function buildEscalationPath(array $overallRisk): array
    {
        $escalation = [];

        if ($overallRisk['level'] === 'CRITICAL') {
            $escalation[] = '1. Notify CISO immediately';
            $escalation[] = '2. Activate incident response team';
            $escalation[] = '3. Begin network isolation procedures';
            $escalation[] = '4. Preserve evidence for forensics';
            $escalation[] = '5. Consider law enforcement notification';
        } elseif ($overallRisk['level'] === 'HIGH') {
            $escalation[] = '1. Alert Security Operations Manager';
            $escalation[] = '2. Begin advanced analysis and containment';
            $escalation[] = '3. Monitor for lateral movement';
            $escalation[] = '4. Prepare incident response procedures';
        } else {
            $escalation[] = '1. Continue monitoring';
            $escalation[] = '2. Apply threat intelligence';
            $escalation[] = '3. Schedule follow-up analysis';
        }

        return $escalation;
    }

    private function convertToSTIXObject(array $ioc): array
    {
        $baseObj = [
            'id'         => $this->generateSTIXID($ioc['type']),
            'created'    => now()->toIso8601ZuluString(),
            'modified'   => now()->toIso8601ZuluString(),
            'revoked'    => false,
            'labels'     => ['malicious-activity'],
        ];

        return match ($ioc['type']) {
            'ip_address' => array_merge($baseObj, [
                'type'  => 'ipv4-addr',
                'value' => $ioc['value'],
            ]),
            'domain' => array_merge($baseObj, [
                'type'  => 'domain-name',
                'value' => $ioc['value'],
            ]),
            'url' => array_merge($baseObj, [
                'type'  => 'url',
                'value' => $ioc['value'],
            ]),
            'file_hash' => array_merge($baseObj, [
                'type'  => 'file',
                'hashes' => ['MD5' => $ioc['value']],
            ]),
            default => $baseObj,
        };
    }

    private function generateSTIXID(string $type): string
    {
        $typeMap = [
            'ip_address' => 'ipv4-addr',
            'domain'     => 'domain-name',
            'url'        => 'url',
            'file_hash'  => 'file',
        ];

        $stixType = $typeMap[$type] ?? 'indicator';
        return "{$stixType}--" . strtolower(str_replace('-', '', uniqid('', true)));
    }
}
