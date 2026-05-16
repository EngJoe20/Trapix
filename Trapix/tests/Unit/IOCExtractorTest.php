<?php

use App\Services\Report\IOCExtractor;

describe('IOCExtractor', function () {

    beforeEach(function () {
        $this->extractor = new IOCExtractor();
    });

    it('returns empty array for empty file analysis', function () {
        $result = $this->extractor->extractIOCs([]);
        expect($result)->toBeArray()->toBeEmpty();
    });

    it('extracts IP addresses from structured IOCs', function () {
        $result = $this->extractor->extractIOCs([
            'iocs' => [
                'ip_addresses' => ['192.168.1.1', '10.0.0.5', '8.8.8.8'],
            ],
        ]);

        $types = array_column($result, 'type');
        expect(in_array('ip_address', $types))->toBeTrue();
        expect(count($result))->toBeGreaterThan(0);
    });

    it('extracts URLs from strings section', function () {
        $result = $this->extractor->extractIOCs([
            'strings' => [
                'ascii'   => ['http://malware.example.com/payload', 'GET /shellcode HTTP/1.1'],
                'unicode' => [],
            ],
        ]);

        $types = array_column($result, 'type');
        expect(in_array('url', $types))->toBeTrue();
    });

    it('deduplicates IOCs with same value', function () {
        $result = $this->extractor->extractIOCs([
            'iocs' => [
                'ip_addresses' => ['1.2.3.4', '1.2.3.4', '1.2.3.4'],
            ],
        ]);

        // After dedup only one entry should exist for 1.2.3.4
        $ipValues = array_filter($result, fn($ioc) => $ioc['value'] === '1.2.3.4');
        expect(count($ipValues))->toBe(1);
    });

    it('categorizeForReport returns expected structure', function () {
        $iocs = [
            ['type' => 'ip_address', 'value' => '1.2.3.4', 'severity' => 'HIGH',   'source' => 'test', 'context' => 'test'],
            ['type' => 'domain',     'value' => 'evil.com', 'severity' => 'MEDIUM', 'source' => 'test', 'context' => 'test'],
        ];

        $cat = $this->extractor->categorizeForReport($iocs);

        expect($cat)->toHaveKeys(['by_type', 'by_severity', 'summary'])
            ->and($cat['summary']['total'])->toBe(2)
            ->and($cat['summary']['high'])->toBe(1)
            ->and($cat['summary']['medium'])->toBe(1)
            ->and(isset($cat['by_type']['ip_address']))->toBeTrue();
    });

    it('exportCSV produces correct header row', function () {
        $csv = $this->extractor->exportCSV([]);
        expect($csv)->toContain('Type,Value,Severity,Source,Context');
    });

    it('exportCSV includes IOC data rows', function () {
        $iocs = [
            ['type' => 'ip_address', 'value' => '1.2.3.4', 'severity' => 'HIGH', 'source' => 'network', 'context' => 'C&C'],
        ];
        $csv = $this->extractor->exportCSV($iocs);
        expect($csv)->toContain('ip_address')
            ->and($csv)->toContain('1.2.3.4');
    });
});
