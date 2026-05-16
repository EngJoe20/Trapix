<?php

use App\Models\AnalysisJob;
use App\Models\User;

describe('ReportEndpoints', function () {

    /**
     * Create a completed analysis job with minimal data.
     */
    function makeCompletedJob(?User $user = null): AnalysisJob
    {
        return AnalysisJob::create([
            'user_id'      => $user?->id,
            'guest_token'  => $user ? null : 'test-guest-token-abc123',
            'status'       => 'completed',
            'input_type'   => 'file',
            'file_count'   => 1,
            'result'       => [
                'summary' => ['risk_level' => 'LOW'],
                'results' => [
                    [
                        'file_name'       => 'test.exe',
                        'file_size'       => 1024,
                        'file_type'       => 'PE32',
                        'hashes'          => ['md5' => 'abc123', 'sha256' => 'def456'],
                        'virustotal'      => ['queried' => false, 'error' => 'Skipped'],
                        'pe_info'         => [],
                        'packer'          => ['is_packed' => false],
                        'entropy'         => ['file_entropy' => 6.0, 'overall_suspicious' => false],
                        'suspicious_apis' => [],
                        'strings'         => ['ascii' => [], 'unicode' => []],
                        'iocs'            => [],
                    ],
                ],
            ],
            'started_at'   => now()->subMinutes(2),
            'completed_at' => now(),
        ]);
    }

    /**
     * Provide the correct access token for a guest job.
     */
    function guestToken(AnalysisJob $job): string
    {
        return $job->guest_token ?? '';
    }

    // ─────────────────────────────────────────────────────────────────────
    // HTML Report
    // ─────────────────────────────────────────────────────────────────────

    it('GET /analysis/{id}/report-html returns 200 for guest with token', function () {
        $job = makeCompletedJob();

        $response = $this->get("/analysis/{$job->id}/report-html?guest_token=" . guestToken($job));

        $response->assertStatus(200);
    });

    it('GET /analysis/{id}/report-html returns 200 for authenticated owner', function () {
        $user = User::factory()->create();
        $job  = makeCompletedJob($user);

        $response = $this->actingAs($user)->get("/analysis/{$job->id}/report-html");

        $response->assertStatus(200);
    });

    // ─────────────────────────────────────────────────────────────────────
    // JSON Export
    // ─────────────────────────────────────────────────────────────────────

    it('GET /analysis/{id}/export-json returns 200 with JSON content type', function () {
        $job = makeCompletedJob();

        $response = $this->get("/analysis/{$job->id}/export-json?guest_token=" . guestToken($job));

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');
    });

    it('export-json download has correct filename header', function () {
        $job = makeCompletedJob();

        $response = $this->get("/analysis/{$job->id}/export-json?guest_token=" . guestToken($job));

        $response->assertStatus(200);
        expect($response->headers->get('Content-Disposition'))
            ->toContain("trapix-report-{$job->id}.json");
    });

    // ─────────────────────────────────────────────────────────────────────
    // IOC CSV Export
    // ─────────────────────────────────────────────────────────────────────

    it('GET /analysis/{id}/export-iocs returns 200 with CSV content type', function () {
        $job = makeCompletedJob();

        $response = $this->get("/analysis/{$job->id}/export-iocs?guest_token=" . guestToken($job));

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    });

    it('IOC CSV export contains header row', function () {
        $job = makeCompletedJob();

        $response = $this->get("/analysis/{$job->id}/export-iocs?guest_token=" . guestToken($job));

        $response->assertStatus(200);
        expect($response->getContent())->toContain('Type,Value,Severity,Source,Context');
    });

    // ─────────────────────────────────────────────────────────────────────
    // STIX Export
    // ─────────────────────────────────────────────────────────────────────

    it('GET /analysis/{id}/export-stix returns 200', function () {
        $job = makeCompletedJob();

        $response = $this->get("/analysis/{$job->id}/export-stix?guest_token=" . guestToken($job));

        $response->assertStatus(200);
        $decoded = json_decode($response->getContent(), true);
        expect($decoded)->toHaveKey('type')
            ->and($decoded['type'])->toBe('bundle');
    });

    // ─────────────────────────────────────────────────────────────────────
    // Threat Intel / DFIR / SOC Reports
    // ─────────────────────────────────────────────────────────────────────

    it('GET /analysis/{id}/report-threat-intel returns 200 JSON', function () {
        $job = makeCompletedJob();

        $response = $this->get("/analysis/{$job->id}/report-threat-intel?guest_token=" . guestToken($job));

        $response->assertStatus(200);
        $decoded = json_decode($response->getContent(), true);
        expect($decoded)->toHaveKey('threat_classification');
    });

    it('GET /analysis/{id}/report-dfir returns 200 JSON', function () {
        $job = makeCompletedJob();

        $response = $this->get("/analysis/{$job->id}/report-dfir?guest_token=" . guestToken($job));

        $response->assertStatus(200);
        $decoded = json_decode($response->getContent(), true);
        expect($decoded)->toHaveKey('case_metadata');
    });

    it('GET /analysis/{id}/report-soc returns 200 JSON', function () {
        $job = makeCompletedJob();

        $response = $this->get("/analysis/{$job->id}/report-soc?guest_token=" . guestToken($job));

        $response->assertStatus(200);
        $decoded = json_decode($response->getContent(), true);
        expect($decoded)->toHaveKey('alert_summary');
    });

    // ─────────────────────────────────────────────────────────────────────
    // Access Control
    // ─────────────────────────────────────────────────────────────────────

    it('export-json returns 403 for wrong guest token', function () {
        $job = makeCompletedJob();

        $response = $this->get("/analysis/{$job->id}/export-json?guest_token=wrong-token");

        $response->assertStatus(403);
    });

    it('PDF download generates without pdf_path set', function () {
        $user = User::factory()->create();
        $job  = makeCompletedJob($user);
        // No AnalysisReport created, so pdf_path will be null — should still work

        $response = $this->actingAs($user)->get("/analysis/{$job->id}/report");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    });
});
