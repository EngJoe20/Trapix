<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Trapix Report - {{ $job->files->first()?->original_name ?? 'Unknown File' }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            font-size: 12px;
            line-height: 1.4;
        }

        h1 {
            font-size: 24px;
            border-bottom: 2px solid #2d3748;
            padding-bottom: 5px;
            margin-bottom: 15px;
            color: #1a202c;
        }

        h2 {
            font-size: 18px;
            border-bottom: 1px solid #cbd5e0;
            padding-bottom: 4px;
            margin-top: 25px;
            margin-bottom: 10px;
            color: #2d3748;
        }

        h3 {
            font-size: 14px;
            margin-top: 15px;
            margin-bottom: 5px;
            color: #4a5568;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 11px;
        }

        .badge-critical {
            background: #fed7d7;
            color: #c53030;
        }

        .badge-high {
            background: #feebc8;
            color: #c05621;
        }

        .badge-medium {
            background: #fefcbf;
            color: #b7791f;
        }

        .badge-low {
            background: #c6f6d5;
            color: #2f855a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            border: 1px solid #e2e8f0;
            padding: 6px 10px;
            text-align: left;
        }

        th {
            background-color: #f7fafc;
            font-weight: bold;
            color: #4a5568;
            width: 30%;
        }

        .header-box {
            background: #edf2f7;
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-box {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #c53030;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            color: #a0aec0;
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }

        pre {
            background: #f7fafc;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            overflow-wrap: break-word;
            word-wrap: break-word;
            white-space: pre-wrap;
            font-size: 10px;
        }
    </style>
</head>

<body>

    @php
        $file = $job->files->first();
        $fileName = $file?->original_name ?? 'Unknown File';
        $reportData = $job->report;
        $aiData = $job->aiResponse;

        $riskLevel = $reportData->risk_level ?? 'UNKNOWN';
        $badgeClass = match ($riskLevel) {
            'CRITICAL', 'HIGH', 'MALICIOUS' => 'badge-critical',
            'MEDIUM', 'SUSPICIOUS' => 'badge-high',
            'LOW' => 'badge-medium',
            'CLEAN' => 'badge-low',
            default => 'badge-medium',
        };

        $results = $job->result ?? [];
        // Flatten folder results to main
        if (isset($results['results'])) {
            $results = $results['results'][0] ?? [];
        }
    @endphp

    <h1>Trapix Malware Analysis Report</h1>

    <div class="header-box">
        <table style="margin-bottom: 0;">
            <tr>
                <th>File Name</th>
                <td>{{ $fileName }}</td>
            </tr>
            <tr>
                <th>Overall Risk</th>
                <td><span class="badge {{ $badgeClass }}">{{ $riskLevel }}</span></td>
            </tr>
            <tr>
                <th>Analysis Date</th>
                <td>{{ $job->completed_at ? $job->completed_at->format('Y-m-d H:i:s T') : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Job ID</th>
                <td>{{ $job->id }}</td>
            </tr>
            @if(isset($results['file_size']))
                <tr>
                    <th>File Size</th>
                    <td>{{ number_format((int) $results['file_size'] / 1024, 2) }} KB</td>
                </tr>
            @endif
        </table>
    </div>

    @if($aiData && $aiData->status === 'completed')
        <div class="alert-box">
            <h2 style="margin-top: 0; color: #c53030;">AI Expert Analysis</h2>
            @if(!empty($aiData->insights['summary']))
                <p><strong>Summary:</strong> {{ $aiData->insights['summary'] }}</p>
            @endif
            @if(!empty($aiData->insights['insights']))
                <p><strong>Key Findings:</strong></p>
                <ul>
                    @foreach(array_slice($aiData->insights['insights'], 0, 5) as $insight)
                        <li><strong>{{ $insight['category'] ?? 'Insight' }}:</strong> {{ $insight['detail'] ?? '' }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <h2>Hashes</h2>
    <table>
        @if(isset($results['hashes']))
            @foreach($results['hashes'] as $algo => $hash)
                <tr>
                    <th>{{ strtoupper($algo) }}</th>
                    <td style="font-family: monospace;">{{ $hash }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="2">No hash data available.</td>
            </tr>
        @endif
    </table>

    @if(isset($results['virustotal']))
        <h2>VirusTotal</h2>
        <table>
            <tr>
                <th>Detection Ratio</th>
                <td>{{ $results['virustotal']['detection_ratio'] ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Verdict</th>
                <td>{{ $results['virustotal']['verdict'] ?? 'N/A' }}</td>
            </tr>
            @if(isset($results['virustotal']['scan_date']))
                <tr>
                    <th>Scan Date</th>
                    <td>{{ $results['virustotal']['scan_date'] }}</td>
                </tr>
            @endif
        </table>
    @endif

    @if(isset($results['suspicious_apis']) && count($results['suspicious_apis']) > 0)
        <h2>Suspicious APIs Detected</h2>
        <table>
            <tr>
                <th style="width: 20%;">API</th>
                <th>Category / Reason</th>
            </tr>
            @foreach($results['suspicious_apis'] as $api => $info)
                <tr>
                    <td style="font-family: monospace; font-weight: bold;">{{ $api }}</td>
                    <td>{{ is_array($info) ? ($info['reason'] ?? 'Unknown') : $info }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if(isset($results['pe_info']) && !empty($results['pe_info']))
        <h2>PE File Information</h2>
        <table>
            @if(isset($results['pe_info']['machine_type']))
                <tr>
                    <th>Machine Type</th>
                    <td>{{ $results['pe_info']['machine_type'] }}</td>
                </tr>
            @endif
            @if(isset($results['pe_info']['compile_time']))
                <tr>
                    <th>Compile Time</th>
                    <td>{{ $results['pe_info']['compile_time'] }}</td>
                </tr>
            @endif
            @if(isset($results['pe_info']['subsystem']))
                <tr>
                    <th>Subsystem</th>
                    <td>{{ $results['pe_info']['subsystem'] }}</td>
                </tr>
            @endif
            @if(isset($results['pe_info']['imphash']))
                <tr>
                    <th>Imphash</th>
                    <td style="font-family: monospace;">{{ $results['pe_info']['imphash'] }}</td>
                </tr>
            @endif
        </table>
    @endif

    <div class="footer">
        Generated by Trapix Security Analyzer &copy; {{ date('Y') }}<br>
        Confidential Report - Do not distribute without authorization.
    </div>

</body>

</html>