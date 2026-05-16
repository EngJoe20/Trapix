<?php

use App\Models\AnalysisJob;
use App\Services\Report\ReportBuilder;

describe('ReportBuilder', function () {

    beforeEach(function () {
        $this->builder = new ReportBuilder();
    });

    function makeBuilderJob(array $result = []): AnalysisJob
    {
        $job = new AnalysisJob();
        $job->id           = 'builder-test-uuid';
        $job->status       = 'completed';
        $job->file_count   = 1;
        $job->result       = $result;
        $job->started_at   = now()->subMinutes(1);
        $job->completed_at = now();

        $job->setRelation('files', collect());
        $job->setRelation('user', null);
        $job->setRelation('aiResponse', null);

        return $job;
    }

    it('build() returns expected top-level keys', function () {
        $job    = makeBuilderJob();
        $report = $this->builder->build($job);

        expect($report)->toHaveKeys([
            'report_metadata',
            'job_information',
            'executive_summary',
            'threat_overview',
            'risk_assessment',
            'file_analyses',
            'static_analysis',
            'virustotal_intel',
            'suspicious_apis',
            'ioc_extraction',
            'strings_analysis',
            'behavioral_intel',
            'mitre_mapping',
            'recommendations',
            'generated_at',
        ]);
    });

    it('build() does not throw on fully empty job', function () {
        $job = makeBuilderJob([]);
        expect(fn() => $this->builder->build($job))->not->toThrow(\Throwable::class);
    });

    it('exportJSON() returns valid JSON string', function () {
        $job  = makeBuilderJob();
        $json = $this->builder->exportJSON($job);

        expect($json)->toBeString();
        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray()->toHaveKey('report_metadata');
    });

    it('buildPreview() returns condensed structure', function () {
        $job     = makeBuilderJob();
        $preview = $this->builder->buildPreview($job);

        expect($preview)->toHaveKeys(['job_id', 'file_count', 'overall_risk', 'threat_summary', 'files']);
    });

    it('exportSTIX() returns valid STIX bundle JSON', function () {
        $job  = makeBuilderJob([
            'results' => [[
                'iocs' => ['ip_addresses' => ['1.2.3.4']],
            ]],
        ]);
        $stix = $this->builder->exportSTIX($job);

        $decoded = json_decode($stix, true);
        expect($decoded)->toBeArray()
            ->toHaveKey('type')
            ->toHaveKey('objects');
        expect($decoded['type'])->toBe('bundle');
    });

    it('buildThreatIntelReport() returns correct keys', function () {
        $report = $this->builder->buildThreatIntelReport(makeBuilderJob());

        expect($report)->toHaveKeys([
            'threat_classification',
            'malware_family',
            'attack_patterns',
            'mitre_techniques',
            'indicators_of_compromise',
            'virustotal_intelligence',
            'recommendations',
        ]);
    });

    it('buildDFIRReport() returns case_metadata and evidence_details', function () {
        $report = $this->builder->buildDFIRReport(makeBuilderJob());

        expect($report)->toHaveKeys(['case_metadata', 'evidence_details', 'forensic_findings', 'recommendations']);
    });

    it('buildSOCReport() returns alert_summary with expected keys', function () {
        $report = $this->builder->buildSOCReport(makeBuilderJob());

        expect($report)->toHaveKeys(['alert_summary', 'threat_details', 'affected_assets']);
        expect($report['alert_summary'])->toHaveKeys(['severity', 'confidence', 'risk_score']);
    });
});
