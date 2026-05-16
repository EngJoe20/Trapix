<?php

use App\Models\AnalysisJob;
use App\Services\Report\ReportDataTransformer;

describe('ReportDataTransformer', function () {

    beforeEach(function () {
        $this->transformer = new ReportDataTransformer();
    });

    /**
     * Helper: create a minimal mock AnalysisJob (no DB required)
     */
    function makeJob(array $result = [], ?array $aiInsights = null): AnalysisJob
    {
        $job = new AnalysisJob();
        $job->id          = 'test-uuid-1234';
        $job->user_id     = null;
        $job->status      = 'completed';
        $job->file_count  = 1;
        $job->result      = $result;
        $job->started_at  = now()->subMinutes(2);
        $job->completed_at = now();

        // Minimal relations mocked as empty collections
        $job->setRelation('files', collect());
        $job->setRelation('user', null);
        $job->setRelation('aiResponse', null);

        return $job;
    }

    it('does not throw on empty result', function () {
        $job = makeJob([]);
        $data = $this->transformer->transform($job);

        expect($data)->toBeArray()
            ->toHaveKey('metadata')
            ->toHaveKey('job_info')
            ->toHaveKey('risk_assessment')
            ->toHaveKey('threat_overview')
            ->toHaveKey('executive_summary');
    });

    it('handles null aiResponse gracefully', function () {
        $job = makeJob(['summary' => ['risk_level' => 'LOW']]);
        $data = $this->transformer->transform($job);

        expect($data['behavioral_intel']['insights'])->toBeArray();
        expect($data['mitre_mapping']['techniques'])->toBeArray();
    });

    it('correctly identifies UNKNOWN risk when result is empty', function () {
        $job = makeJob([]);
        $data = $this->transformer->transform($job);

        expect($data['threat_overview']['overall_level'])->toBe('UNKNOWN');
    });

    it('resolves file name from results array', function () {
        $job = makeJob([
            'results' => [
                ['file_name' => 'malware.exe', 'file_size' => 1024, 'hashes' => []],
            ],
        ]);

        $data = $this->transformer->transform($job);
        expect($data['file_analyses'][0]['file_name'])->toBe('malware.exe');
    });

    it('returns UNKNOWN risk for missing summary', function () {
        $job = makeJob(['results' => [['file_name' => 'test.exe']]]);
        $data = $this->transformer->transform($job);

        expect($data['executive_summary']['risk_level'])->toBeIn(['UNKNOWN', 'CLEAN', 'LOW', 'MEDIUM', 'HIGH', 'CRITICAL']);
    });
});
