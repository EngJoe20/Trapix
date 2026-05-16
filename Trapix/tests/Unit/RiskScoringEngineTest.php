<?php

use App\Services\Report\RiskScoringEngine;

describe('RiskScoringEngine', function () {

    beforeEach(function () {
        $this->engine = new RiskScoringEngine();
    });

    it('returns CLEAN risk for empty file analysis', function () {
        $result = $this->engine->calculateFileRisk([]);

        expect($result)->toBeArray()
            ->and($result['score'])->toBe(0)
            ->and($result['level'])->toBe('CLEAN')
            ->and($result['confidence'])->toBeInt()
            ->and($result['reasoning'])->toBeString();
    });

    it('returns CLEAN for no VT detections', function () {
        $result = $this->engine->calculateFileRisk([
            'virustotal' => [
                'queried'       => true,
                'found'         => true,
                'malicious'     => 0,
                'total_engines' => 72,
                'detection_ratio' => '0/72',
            ],
        ]);

        expect($result['level'])->toBeIn(['CLEAN', 'LOW']);
        expect($result['score'])->toBeLessThan(40);
    });

    it('returns HIGH or CRITICAL for high VT detections', function () {
        $result = $this->engine->calculateFileRisk([
            'virustotal' => [
                'queried'       => true,
                'found'         => true,
                'malicious'     => 40,
                'total_engines' => 72,
                'detection_ratio' => '40/72',
                'threat_label'  => 'Trojan.GenericKD',
            ],
        ]);

        expect($result['level'])->toBeIn(['HIGH', 'CRITICAL']);
        expect($result['score'])->toBeGreaterThan(30);
    });

    it('escalates packed + shell APIs + high VT to CRITICAL', function () {
        $result = $this->engine->calculateFileRisk([
            'packer' => ['is_packed' => true, 'packer_name' => 'UPX', 'confidence' => 'HIGH'],
            'suspicious_apis' => [
                'ShellExecuteW' => ['severity' => 'critical', 'category' => 'execution', 'reason' => 'shell'],
            ],
            'virustotal' => [
                'queried'       => true,
                'found'         => true,
                'malicious'     => 20,
                'total_engines' => 72,
                'detection_ratio' => '20/72',
            ],
            'entropy' => ['file_entropy' => 7.8],
        ]);

        expect($result['level'])->toBe('CRITICAL');
    });

    it('calculateJobRisk returns CLEAN for empty input', function () {
        $result = $this->engine->calculateJobRisk([]);

        expect($result['level'])->toBe('CLEAN')
            ->and($result['score'])->toBe(0);
    });

    it('calculateJobRisk picks worst file risk', function () {
        $low  = ['score' => 10, 'level' => 'LOW',  'confidence' => 50, 'reasoning' => '', 'factors' => []];
        $high = ['score' => 70, 'level' => 'HIGH', 'confidence' => 80, 'reasoning' => '', 'factors' => []];

        $result = $this->engine->calculateJobRisk([$low, $high]);

        expect($result['level'])->toBe('HIGH')
            ->and($result['score'])->toBe(70);
    });
});
