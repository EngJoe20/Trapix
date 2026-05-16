<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trapix Enterprise Malware Analysis Report</title>
    <style>
        /* ─── Reset & Base ───────────────────────────────────────────────── */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #e2e8f0;
            background: #0f172a;
            line-height: 1.5;
        }

        code {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 9px;
        }

        pre {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 9px;
            white-space: pre-wrap;
            word-break: break-all;
        }

        /* ─── Cover Page ─────────────────────────────────────────────────── */
        .cover-page {
            text-align: center;
            padding: 80px 40px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 700px;
        }

        .cover-page .logo {
            font-size: 28px;
            font-weight: 800;
            color: #4ade80;
            letter-spacing: 4px;
            margin-bottom: 20px;
        }

        .cover-page h1 {
            font-size: 26px;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 8px;
            letter-spacing: 2px;
        }

        .cover-page .subtitle {
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 50px;
        }

        .metadata dl {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 24px;
            text-align: left;
            max-width: 480px;
            margin: 0 auto;
        }

        .metadata dt {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
        }

        .metadata dd {
            font-size: 11px;
            color: #e2e8f0;
            font-weight: 600;
        }

        /* ─── Page Breaks ────────────────────────────────────────────────── */
        .page-break {
            page-break-after: always;
        }

        /* ─── Sections ───────────────────────────────────────────────────── */
        .section {
            padding: 24px 32px;
            margin-bottom: 16px;
        }

        .section-header {
            border-left: 4px solid #4ade80;
            padding-left: 12px;
            margin-bottom: 16px;
        }

        .section-header h2 {
            font-size: 14px;
            font-weight: 700;
            color: #f8fafc;
            letter-spacing: 1.5px;
        }

        h3 {
            font-size: 12px;
            font-weight: 700;
            color: #e2e8f0;
            margin: 14px 0 8px;
        }

        h4 {
            font-size: 11px;
            font-weight: 600;
            color: #cbd5e1;
            margin: 10px 0 6px;
        }

        p {
            font-size: 11px;
            color: #94a3b8;
            margin: 6px 0;
        }

        /* ─── Badges ─────────────────────────────────────────────────────── */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .badge-critical {
            background: #450a0a;
            color: #fca5a5;
            border: 1px solid #dc2626;
        }

        .badge-high {
            background: #431407;
            color: #fdba74;
            border: 1px solid #ea580c;
        }

        .badge-medium {
            background: #422006;
            color: #fde68a;
            border: 1px solid #ca8a04;
        }

        .badge-low {
            background: #052e16;
            color: #86efac;
            border: 1px solid #16a34a;
        }

        .badge-clean {
            background: #052e16;
            color: #86efac;
            border: 1px solid #16a34a;
        }

        .badge-unknown {
            background: #0c1a2e;
            color: #7dd3fc;
            border: 1px solid #0891b2;
        }

        /* ─── Severity text colours ──────────────────────────────────────── */
        .severity-critical {
            color: #ef4444;
            font-weight: 700;
        }

        .severity-high {
            color: #f97316;
            font-weight: 700;
        }

        .severity-medium {
            color: #eab308;
            font-weight: 600;
        }

        .severity-low {
            color: #22c55e;
        }

        /* ─── Summary Grid ───────────────────────────────────────────────── */
        .summary-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
        }

        .summary-item {
            display: table-cell;
            width: 25%;
            padding: 14px;
            background: #1e293b;
            border: 1px solid #334155;
            text-align: center;
            vertical-align: middle;
        }

        .summary-item-label {
            display: block;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 6px;
        }

        .summary-item-value {
            display: block;
            font-size: 16px;
            font-weight: 700;
            color: #f8fafc;
        }

        /* ─── Executive Summary Box ──────────────────────────────────────── */
        .executive-summary {
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 16px;
        }

        .executive-summary-title {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #f8fafc;
        }

        .executive-summary-content {
            font-size: 11px;
            color: #cbd5e1;
        }

        .alert-critical {
            background: #1c0a0a;
            border: 1px solid #dc2626;
        }

        .alert-high {
            background: #1c0f07;
            border: 1px solid #ea580c;
        }

        .alert-medium {
            background: #1c1607;
            border: 1px solid #ca8a04;
        }

        .alert-low {
            background: #07160c;
            border: 1px solid #16a34a;
        }

        .alert-clean {
            background: #07160c;
            border: 1px solid #16a34a;
        }

        .alert-unknown {
            background: #07111c;
            border: 1px solid #0891b2;
        }

        /* ─── Info Box ───────────────────────────────────────────────────── */
        .info-box {
            padding: 14px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 4px;
            font-size: 11px;
            color: #94a3b8;
            margin: 8px 0;
        }

        .alert-box {
            padding: 14px;
            border-radius: 4px;
            margin: 8px 0;
        }

        .alert-info {
            background: #07111c;
            border: 1px solid #0891b2;
            color: #7dd3fc;
        }

        /* ─── Tables ─────────────────────────────────────────────────────── */
        .table-compact {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .table-compact th {
            background: #1e293b;
            color: #94a3b8;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #334155;
        }

        .table-compact td {
            padding: 7px 10px;
            border-bottom: 1px solid #1e293b;
            color: #e2e8f0;
            vertical-align: top;
        }

        .table-compact tr:nth-child(even) td {
            background: #0f1a2c;
        }

        /* ─── Detection Bar ──────────────────────────────────────────────── */
        .detection-ratio-container {
            margin: 10px 0;
        }

        .detection-ratio-label {
            font-size: 11px;
            color: #e2e8f0;
            margin-bottom: 6px;
        }

        .detection-ratio-bar {
            height: 12px;
            background: #1e293b;
            border-radius: 6px;
            overflow: hidden;
        }

        .detection-ratio-fill {
            height: 100%;
            background: #dc2626;
            border-radius: 6px;
        }

        /* ─── API items ──────────────────────────────────────────────────── */
        .api-item {
            padding: 8px 12px;
            margin: 4px 0;
            border-left: 3px solid #334155;
            background: #1e293b;
            border-radius: 0 4px 4px 0;
            font-size: 10px;
        }

        .api-item.critical {
            border-left-color: #dc2626;
        }

        .api-item.high {
            border-left-color: #ea580c;
        }

        .api-item.medium {
            border-left-color: #ca8a04;
        }

        /* ─── IOC Items ──────────────────────────────────────────────────── */
        .ioc-item {
            padding: 6px 10px;
            margin: 3px 0;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 4px;
            font-size: 9px;
        }

        .ioc-item-type {
            display: inline-block;
            width: 32px;
            font-weight: 700;
            font-size: 8px;
        }

        .ioc-item-value {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 9px;
            color: #4ade80;
            word-break: break-all;
        }

        /* ─── MITRE Techniques ───────────────────────────────────────────── */
        .mitre-technique {
            padding: 10px 14px;
            margin: 6px 0;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 4px;
        }

        .mitre-technique-tactic {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 2px;
        }

        .mitre-technique-name {
            font-size: 11px;
            font-weight: 700;
            color: #f8fafc;
        }

        .mitre-technique-evidence {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 4px;
        }

        /* ─── Footer ─────────────────────────────────────────────────────── */
        footer {
            padding: 20px 32px;
            border-top: 1px solid #334155;
            text-align: center;
            margin-top: 32px;
        }

        .footer-brand {
            font-size: 12px;
            font-weight: 700;
            color: #4ade80;
        }

        .footer-disclaimer {
            font-size: 8px;
            color: #475569;
            margin-top: 8px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>

<body>

    @php
        // Initialize report builder
        $reportBuilder = app(\App\Services\Report\ReportBuilder::class);
        $report = $reportBuilder->build($job);

        $metadata = $report['report_metadata'];
        $jobInfo = $report['job_information'];
        $executive = $report['executive_summary'];
        $risk = $report['risk_assessment'];
        $threat = $report['threat_overview'];
        $fileAnalyses = $report['file_analyses'];
        $vtIntel = $report['virustotal_intel'];
        $apis = $report['suspicious_apis'];
        $iocs = $report['ioc_extraction'];
        $behavioralIntel = $report['behavioral_intel'];
        $mitre = $report['mitre_mapping'];
        $recommendations = $report['recommendations'];
    @endphp

    <!-- ═══════════════════════════════════════════════════════════════════════════════
     COVER PAGE
     ═══════════════════════════════════════════════════════════════════════════════ -->

    <div class="cover-page">
        <div class="logo">
            @php
                $logoPath = public_path('images/logo.png');
                $logoData = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
            @endphp
            @if($logoData)
                <img src="{{ $logoData }}" style="height:70px;" alt="TRAPIX">
            @else
                🛡️ TRAPIX
            @endif
        </div>
        <h1>MALWARE ANALYSIS REPORT</h1>
        <p class="subtitle">Enterprise Threat Intelligence & Forensic Analysis</p>

        <div class="metadata">
            <dl>
                <dt>Report Date:</dt>
                <dd>{{ $metadata['report_generated_at'] }}</dd>
                <dt>Job ID:</dt>
                <dd><code>{{ $jobInfo['job_id'] }}</code></dd>
                <dt>Analyst:</dt>
                <dd>{{ $jobInfo['user_name'] }}</dd>
                <dt>Risk Level:</dt>
                <dd><span
                        class="badge badge-{{ strtolower($risk['overall_risk']['level']) }}">{{ $risk['overall_risk']['level'] }}</span>
                </dd>
            </dl>
        </div>

        <p style="margin-top: 60px; font-size: 9px; color: #64748b;">
            <strong>Confidential Information</strong><br>
            This report contains sensitive security information. Unauthorized distribution is prohibited.
        </p>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════════
     EXECUTIVE SUMMARY
     ═══════════════════════════════════════════════════════════════════════════════ -->

    <div class="page-break"></div>
    <div class="section">
        <div class="section-header">
            <h2>EXECUTIVE SUMMARY</h2>
        </div>

        <div class="executive-summary alert-{{ strtolower($risk['overall_risk']['level']) }}">
            <div class="executive-summary-title">{{ $risk['overall_risk']['level'] }} THREAT ASSESSMENT</div>
            <div class="executive-summary-content">
                {{ $executive['assessment'] }}
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-item-label">Risk Score</span>
                <span class="summary-item-value" style="color: {{ match ($risk['overall_risk']['level']) {
    'CRITICAL' => '#dc2626',
    'HIGH' => '#ea580c',
    'MEDIUM' => '#ca8a04',
    'LOW' => '#16a34a',
    default => '#0891b2'
} }}">{{ $risk['overall_risk']['score'] }}/100</span>
            </div>
            <div class="summary-item">
                <span class="summary-item-label">Confidence Level</span>
                <span class="summary-item-value">{{ $risk['overall_risk']['confidence'] }}%</span>
            </div>
            <div class="summary-item">
                <span class="summary-item-label">Threat Label</span>
                <span class="summary-item-value" style="font-size: 10px;">{{ $threat['threat_label'] }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-item-label">Files Analyzed</span>
                <span class="summary-item-value">{{ $jobInfo['file_count'] }}</span>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════════
     THREAT OVERVIEW
     ═══════════════════════════════════════════════════════════════════════════════ -->

    <div class="page-break"></div>
    <div class="section">
        <div class="section-header">
            <h2>THREAT OVERVIEW</h2>
        </div>

        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-item-label">Packed Executable</span>
                <span class="summary-item-value">{{ $threat['is_packed'] ? '⚠️ YES' : '✓ NO' }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-item-label">High Entropy</span>
                <span class="summary-item-value">{{ $threat['high_entropy'] ? '⚠️ YES' : '✓ NO' }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-item-label">VirusTotal Ratio</span>
                <span class="summary-item-value">{{ $threat['vt_detection_ratio'] }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-item-label">Overall Level</span>
                <span class="summary-item-value"><span
                        class="badge badge-{{ strtolower($threat['overall_level']) }}">{{ $threat['overall_level'] }}</span></span>
            </div>
        </div>

        <h3>Summary</h3>
        <p>{{ $threat['summary'] }}</p>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════════
     RISK ASSESSMENT
     ═══════════════════════════════════════════════════════════════════════════════ -->

    <div class="page-break"></div>
    <div class="section">
        <div class="section-header">
            <h2>RISK ASSESSMENT DETAILS</h2>
        </div>

        <h3>Risk Score Breakdown</h3>
        <pre>{{ $risk['overall_risk']['reasoning'] }}</pre>

        @if(!empty($risk['overall_risk']['factors']))
            <h3>Detection Factors</h3>
            <ul style="margin-left: 20px;">
                @foreach(array_slice($risk['overall_risk']['factors'], 0, 10) as $factor)
                    <li style="margin: 4px 0; font-size: 11px;">{{ $factor }}</li>
                @endforeach
            </ul>
        @endif

        <h3>Per-File Risk Summary</h3>
        <table class="table-compact">
            <thead>
                <tr>
                    <th>File</th>
                    <th>Risk Level</th>
                    <th>Score</th>
                    <th>Confidence</th>
                </tr>
            </thead>
            <tbody>
                @foreach($fileAnalyses as $file)
                    <tr>
                        <td style="font-family: monospace; font-size: 9px;">{{ substr($file['file_name'], 0, 40) }}</td>
                        <td><span
                                class="badge badge-{{ strtolower($file['risk_assessment']['level']) }}">{{ $file['risk_assessment']['level'] }}</span>
                        </td>
                        <td>{{ $file['risk_assessment']['score'] }}</td>
                        <td>{{ $file['risk_assessment']['confidence'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════════
     FILE ANALYSIS DETAILS
     ═══════════════════════════════════════════════════════════════════════════════ -->

    <div class="page-break"></div>
    <div class="section">
        <div class="section-header">
            <h2>FILE ANALYSIS DETAILS</h2>
        </div>

        @foreach($fileAnalyses as $file)
            <div style="page-break-inside: avoid; margin-bottom: 20px;">
                <h3>📄 {{ $file['file_name'] }}</h3>

                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="summary-item-label">File Size</span>
                        <span class="summary-item-value">{{ number_format((int) $file['file_size']) }} bytes</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-item-label">Type</span>
                        <span class="summary-item-value">{{ $file['file_type'] }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-item-label">Risk Level</span>
                        <span class="summary-item-value"><span
                                class="badge badge-{{ strtolower($file['risk_assessment']['level']) }}">{{ $file['risk_assessment']['level'] }}</span></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-item-label">Entropy</span>
                        <span
                            class="summary-item-value">{{ number_format((float) ($file['entropy']['file_entropy'] ?? 0), 2) }}</span>
                    </div>
                </div>

                <!-- File Hashes -->
                <h4>Cryptographic Hashes</h4>
                <table class="table-compact">
                    <tr>
                        <th style="width: 20%;">Algorithm</th>
                        <th>Hash</th>
                    </tr>
                    @foreach(($file['hashes'] ?? []) as $algo => $hash)
                        <tr>
                            <td><strong>{{ strtoupper($algo) }}</strong></td>
                            <td style="font-family: monospace; font-size: 9px; word-break: break-all;">{{ $hash }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════════
     VIRUSTOTAL INTELLIGENCE
     ═══════════════════════════════════════════════════════════════════════════════ -->

    <div class="page-break"></div>
    <div class="section">
        <div class="section-header">
            <h2>VIRUSTOTAL INTELLIGENCE</h2>
        </div>

        @if(!$vtIntel['queried'])
            <div class="alert-box alert-info">
                <strong>ℹ️ Note:</strong> {{ $vtIntel['reason'] }}
            </div>
        @elseif(!$vtIntel['found'])
            <div class="info-box">
                <strong>✓ File Unknown to VirusTotal</strong><br>
                This file has not been previously scanned by VirusTotal. This can indicate a new or uncommon sample.
            </div>
        @else
            @php
                $totalEngines = max(1, $vtIntel['total_engines'] ?? 1);
                $malicious = $vtIntel['malicious'] ?? 0;
                $vtPercent = round(($malicious / $totalEngines) * 100, 1);
                $barWidth = min(100, ($malicious / $totalEngines) * 100);
            @endphp
            <h3>Detection Ratio</h3>
            <div class="detection-ratio-container">
                <div class="detection-ratio-label">
                    <strong>{{ $malicious }} detections out of {{ $totalEngines }} engines</strong>
                    ({{ $vtPercent }}%)
                </div>
                <div class="detection-ratio-bar">
                    <div class="detection-ratio-fill" style="width: {{ $barWidth }}%"></div>
                </div>
            </div>

            <table class="table-compact" style="margin-top: 12px;">
                <tr>
                    <th>Metric</th>
                    <th>Count</th>
                </tr>
                <tr>
                    <td>Malicious Detections</td>
                    <td><span class="severity-critical">{{ $vtIntel['malicious'] }}</span></td>
                </tr>
                <tr>
                    <td>Suspicious Detections</td>
                    <td><span class="severity-medium">{{ $vtIntel['suspicious'] }}</span></td>
                </tr>
                <tr>
                    <td>Total AV Engines</td>
                    <td>{{ $vtIntel['total_engines'] }}</td>
                </tr>
                <tr>
                    <td>Threat Classification</td>
                    <td><code style="font-size: 10px;">{{ $vtIntel['threat_label'] }}</code></td>
                </tr>
                <tr>
                    <td>Last Scan Date</td>
                    <td>{{ $vtIntel['scan_date'] }}</td>
                </tr>
            </table>

            @if(!empty($vtIntel['vendor_detections']))
                <h3 style="margin-top: 14px;">Top Vendor Detections</h3>
                <ul style="margin-left: 20px; font-size: 10px;">
                    @foreach(array_slice($vtIntel['vendor_detections'], 0, 10) as $detection)
                        <li style="margin: 3px 0;">{{ $detection }}</li>
                    @endforeach
                </ul>
            @endif
        @endif
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════════
     SUSPICIOUS APIs
     ═══════════════════════════════════════════════════════════════════════════════ -->

    <div class="page-break"></div>
    <div class="section">
        <div class="section-header">
            <h2>SUSPICIOUS APIS DETECTED</h2>
        </div>

        @if($apis['total_count'] > 0)
            <p><strong>Total Suspicious APIs Found:</strong> {{ $apis['total_count'] }}</p>

            @foreach($apis['by_category'] as $category => $categoryAPIs)
                <h3 style="text-transform: capitalize;">{{ $category }} ({{ count($categoryAPIs) }} APIs)</h3>

                @foreach($categoryAPIs as $api)
                    <div class="api-item {{ strtolower($api['severity']) }}">
                        <strong>{{ $api['api'] }}</strong>
                        <span class="badge badge-{{ strtolower($api['severity']) }}"
                            style="margin-left: 8px;">{{ $api['severity'] }}</span>
                        @if($api['reason'])
                            <div style="margin-top: 4px; font-size: 10px; color: #94a3b8;">{{ $api['reason'] }}</div>
                        @endif
                    </div>
                @endforeach
            @endforeach
        @else
            <div class="info-box">
                ✓ No suspicious APIs detected
            </div>
        @endif
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════════
     INDICATORS OF COMPROMISE (IOCs)
     ═══════════════════════════════════════════════════════════════════════════════ -->

    <div class="page-break"></div>
    <div class="section">
        <div class="section-header">
            <h2>INDICATORS OF COMPROMISE</h2>
        </div>

        @if($iocs['summary']['total'] > 0)
            <h3>IOC Summary</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <span class="summary-item-label">Total IOCs</span>
                    <span class="summary-item-value">{{ $iocs['summary']['total'] }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-item-label">Critical</span>
                    <span class="summary-item-value severity-critical">{{ $iocs['summary']['critical'] }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-item-label">High</span>
                    <span class="summary-item-value severity-high">{{ $iocs['summary']['high'] }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-item-label">Medium</span>
                    <span class="summary-item-value severity-medium">{{ $iocs['summary']['medium'] }}</span>
                </div>
            </div>

            @foreach($iocs['by_type'] as $type => $typeIOCs)
                <h4 style="text-transform: capitalize; margin-top: 12px;">{{ str_replace('_', ' ', $type) }}
                    ({{ count($typeIOCs) }})</h4>

                @foreach(array_slice($typeIOCs, 0, 20) as $ioc)
                    <div class="ioc-item">
                        <span
                            class="ioc-item-type severity-{{ strtolower($ioc['severity']) }}">{{ strtoupper(substr($ioc['type'], 0, 3)) }}</span>
                        <span class="ioc-item-value">{{ $ioc['value'] }}</span>
                        <div style="margin-top: 3px; font-size: 9px; color: #94a3b8;">{{ $ioc['context'] }}</div>
                    </div>
                @endforeach

                @if(count($typeIOCs) > 20)
                    <p style="font-size: 10px; color: #94a3b8; margin: 6px 0;">... and {{ count($typeIOCs) - 20 }} more</p>
                @endif
            @endforeach
        @else
            <div class="info-box">
                ✓ No indicators of compromise extracted
            </div>
        @endif
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════════
     MITRE ATT&CK FRAMEWORK MAPPING
     ═══════════════════════════════════════════════════════════════════════════════ -->

    <div class="page-break"></div>
    <div class="section">
        <div class="section-header">
            <h2>MITRE ATT&CK FRAMEWORK MAPPING</h2>
        </div>

        @if(!empty($mitre['techniques']))
            <p>{{ $mitre['summary'] }}</p>

            @foreach($mitre['techniques'] as $technique)
                <div class="mitre-technique">
                    <div class="mitre-technique-tactic">{{ $technique['tactic'] }}</div>
                    <div class="mitre-technique-name">→ {{ $technique['technique'] }}</div>
                    <div class="mitre-technique-evidence"><strong>Evidence:</strong> {{ $technique['evidence'] }}</div>
                    <div style="margin-top: 4px; font-size: 10px;">
                        <span class="badge badge-{{ strtolower($technique['severity']) }}">{{ $technique['severity'] }}</span>
                    </div>
                </div>
            @endforeach
        @else
            <div class="info-box">
                No MITRE ATT&CK techniques mapped (insufficient behavioral data)
            </div>
        @endif
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════════
     BEHAVIORAL INTELLIGENCE
     ═══════════════════════════════════════════════════════════════════════════════ -->

    <div class="page-break"></div>
    <div class="section">
        <div class="section-header">
            <h2>BEHAVIORAL INTELLIGENCE & CAPABILITIES</h2>
        </div>

        @if(!empty($behavioralIntel['capabilities']))
            @foreach($behavioralIntel['capabilities'] as $capability)
                <div class="alert-box alert-{{ strtolower($capability['severity']) }}">
                    <h4>{{ $capability['capability'] }}</h4>
                    <p style="margin: 4px 0; font-size: 10px;">{{ $capability['description'] }}</p>
                    <span class="badge badge-{{ strtolower($capability['severity']) }}">{{ $capability['severity'] }}</span>
                </div>
            @endforeach
        @else
            <div class="info-box">
                Behavioral analysis data not available. Run with AI analysis enabled for detailed behavioral insights.
            </div>
        @endif
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════════
     RECOMMENDATIONS
     ═══════════════════════════════════════════════════════════════════════════════ -->

    <div class="page-break"></div>
    <div class="section">
        <div class="section-header">
            <h2>RECOMMENDATIONS & INCIDENT RESPONSE</h2>
        </div>

        @foreach($recommendations as $idx => $recommendation)
                <div class="alert-box alert-{{ strtolower(match ($recommendation['priority']) {
                'IMMEDIATE' => 'critical',
                'URGENT' => 'high',
                default => 'medium'
            }) }}">
                    <strong style="text-transform: uppercase;">{{ $recommendation['priority'] }} -
                        {{ $recommendation['action'] }}</strong>
                    <p style="margin-top: 6px; font-size: 10px; color: #cbd5e0;">Category:
                        {{ ucfirst($recommendation['type']) }}
                    </p>
                </div>
        @endforeach

        <h3>Escalation Path</h3>
        <ol style="margin-left: 20px; font-size: 11px;">
            @php
                $escalationSteps = [
                    'CRITICAL' => [
                        '1. Notify CISO immediately',
                        '2. Activate incident response team',
                        '3. Begin network isolation procedures',
                        '4. Preserve evidence for forensics',
                        '5. Consider law enforcement notification'
                    ],
                    'HIGH' => [
                        '1. Alert Security Operations Manager',
                        '2. Begin advanced analysis and containment',
                        '3. Monitor for lateral movement',
                        '4. Prepare incident response procedures'
                    ],
                    'MEDIUM' => [
                        '1. Continue monitoring',
                        '2. Apply threat intelligence',
                        '3. Schedule follow-up analysis'
                    ],
                    'LOW' => [
                        '1. Document findings',
                        '2. Update threat databases',
                        '3. Continue routine monitoring'
                    ]
                ];
                $steps = $escalationSteps[$risk['overall_risk']['level']] ?? $escalationSteps['MEDIUM'];
            @endphp
            @foreach($steps as $step)
                <li style="margin: 4px 0;">{{ $step }}</li>
            @endforeach
        </ol>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════════════
     FINAL VERDICT & FOOTER
     ═══════════════════════════════════════════════════════════════════════════════ -->

    <div class="page-break"></div>
    <div class="section">
        <div class="section-header">
            <h2>FINAL VERDICT</h2>
        </div>

        <div class="executive-summary alert-{{ strtolower($risk['overall_risk']['level']) }}">
            <div style="font-size: 18px; font-weight: 700; color: {{ match ($risk['overall_risk']['level']) {
    'CRITICAL' => '#dc2626',
    'HIGH' => '#ea580c',
    'MEDIUM' => '#ca8a04',
    'LOW' => '#16a34a',
    default => '#0891b2'
} }}">
                {{ $risk['overall_risk']['level'] }} THREAT
            </div>
            <p style="margin-top: 8px; font-size: 11px;">
                This file is classified as <strong>{{ $risk['overall_risk']['level'] }}</strong> with
                <strong>{{ $risk['overall_risk']['confidence'] }}%</strong> confidence based on comprehensive analysis.
            </p>
            <p style="font-size: 10px; margin-top: 8px; color: #94a3b8;">
                {{ $executive['assessment'] }}
            </p>
        </div>

        <h3>Analysis Metadata</h3>
        <table class="table-compact">
            <tr>
                <th>Analysis Started</th>
                <td>{{ $metadata['analysis_started_at'] }}</td>
            </tr>
            <tr>
                <th>Analysis Completed</th>
                <td>{{ $metadata['analysis_completed_at'] }}</td>
            </tr>
            <tr>
                <th>Total Duration</th>
                <td>{{ $metadata['total_duration_seconds'] ?? 'N/A' }} seconds</td>
            </tr>
            <tr>
                <th>Report Version</th>
                <td>{{ $metadata['report_version'] }}</td>
            </tr>
        </table>
    </div>

    <!-- ═════════════════════════════════════════════════════════════════════════════════
     FOOTER
     ═════════════════════════════════════════════════════════════════════════════════ -->

    <footer>
        <div class="footer-brand">Trapix Security Analyzer</div>
        <p style="margin: 6px 0; font-size: 10px;">
            Report Generated: {{ $metadata['report_generated_at'] }}<br>
            Job ID: <code style="font-size: 9px;">{{ $jobInfo['job_id'] }}</code>
        </p>
        <div class="footer-disclaimer">
            This report contains confidential security analysis information. Unauthorized access, use, distribution, or
            reproduction is strictly prohibited.
            This analysis is provided for informational purposes and should be reviewed by qualified security
            professionals.
            The accuracy and completeness of this analysis depend on the quality of the sample provided and available
            threat intelligence.
        </div>
    </footer>

</body>

</html>