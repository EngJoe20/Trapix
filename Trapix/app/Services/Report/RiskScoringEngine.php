<?php

namespace App\Services\Report;

/**
 * RiskScoringEngine
 *
 * Calculates professional malware risk scores (0-100) based on comprehensive analysis data.
 * Generates risk classifications (CRITICAL, HIGH, MEDIUM, LOW, CLEAN) with confidence levels.
 */
class RiskScoringEngine
{
    const RISK_CRITICAL = 'CRITICAL';
    const RISK_HIGH     = 'HIGH';
    const RISK_MEDIUM   = 'MEDIUM';
    const RISK_LOW      = 'LOW';
    const RISK_CLEAN    = 'CLEAN';

    /**
     * Calculate comprehensive risk score for file analysis
     *
     * @param array $fileAnalysis Single file analysis result from Python analyzer
     * @return array {score: 0-100, level: CRITICAL|HIGH|MEDIUM|LOW|CLEAN, confidence: 0-100, reasoning: string}
     */
    public function calculateFileRisk(array $fileAnalysis): array
    {
        $score = 0;
        $factors = [];

        // ─── VirusTotal Detections (40% weight) ───────────────────────────────
        $vtScore = $this->scoreVirusTotal($fileAnalysis['virustotal'] ?? [], $factors);
        $score += $vtScore * 0.40;

        // ─── Entropy + Packing (25% weight) ────────────────────────────────────
        $packScore = $this->scorePackingAndEntropy($fileAnalysis, $factors);
        $score += $packScore * 0.25;

        // ─── Suspicious APIs (20% weight) ─────────────────────────────────────
        $apiScore = $this->scoreSuspiciousAPIs($fileAnalysis['suspicious_apis'] ?? [], $factors);
        $score += $apiScore * 0.20;

        // ─── IOC Indicators (15% weight) ──────────────────────────────────────
        $iocScore = $this->scoreIOCIndicators($fileAnalysis, $factors);
        $score += $iocScore * 0.15;

        $score = min(100, max(0, $score));

        // ─── Classify Risk Level ────────────────────────────────────────────
        $level = $this->classifyRisk($score, $fileAnalysis);

        // ─── Confidence Level (based on number of detection sources) ────────
        $confidence = $this->calculateConfidence($factors);

        // ─── Generate Risk Reasoning ────────────────────────────────────────
        $reasoning = $this->generateReasoning($score, $level, $factors, $fileAnalysis);

        return [
            'score'       => (int) $score,
            'level'       => $level,
            'confidence'  => (int) $confidence,
            'reasoning'   => $reasoning,
            'factors'     => $factors,
        ];
    }

    /**
     * Score VirusTotal detection ratio (40% weight)
     */
    private function scoreVirusTotal(array $vt, array &$factors): float
    {
        if (!($vt['queried'] ?? false)) {
            $factors[] = 'VirusTotal data unavailable';
            return 0;
        }

        if (!($vt['found'] ?? false)) {
            $factors[] = 'Unknown to VirusTotal (never scanned)';
            return 10;
        }

        $malicious = (int) ($vt['malicious'] ?? 0);
        $total     = (int) ($vt['total_engines'] ?? 1);

        $ratio = $total > 0 ? ($malicious / $total) * 100 : 0;

        // Scoring: 1-5 detections = low, 6-15 = medium, 16-30 = high, 30+ = critical
        if ($malicious >= 30) {
            $score = 95;
            $factors[] = "VirusTotal: {$malicious}/{$total} detections (CRITICAL)";
        } elseif ($malicious >= 16) {
            $score = 80;
            $factors[] = "VirusTotal: {$malicious}/{$total} detections (HIGH)";
        } elseif ($malicious >= 6) {
            $score = 50;
            $factors[] = "VirusTotal: {$malicious}/{$total} detections (MEDIUM)";
        } elseif ($malicious >= 1) {
            $score = 25;
            $factors[] = "VirusTotal: {$malicious}/{$total} detections (LOW)";
        } else {
            $score = 5;
            $factors[] = 'VirusTotal: No detections (CLEAN)';
        }

        if (!empty($vt['threat_label'])) {
            $factors[] = "Threat Classification: {$vt['threat_label']}";
        }

        return $score;
    }

    /**
     * Score packing and entropy (25% weight)
     */
    private function scorePackingAndEntropy(array $file, array &$factors): float
    {
        $score = 0;

        // Packing detection (major risk factor)
        if ($file['packer']['is_packed'] ?? false) {
            $score += 70;
            $packer = $file['packer']['packer_name'] ?? 'Unknown';
            $confidence = $file['packer']['confidence'] ?? '?';
            $factors[] = "Packed Executable: {$packer} ({$confidence} confidence)";
        }

        // Entropy analysis
        $entropy = (float) ($file['entropy']['file_entropy'] ?? 0);
        if ($entropy > 7.5) {
            $score += 30;
            $entropyFormatted = number_format($entropy, 2);
            $factors[] = "High entropy detected ({$entropyFormatted} - suspicious compression/encryption)";
        } elseif ($entropy > 7.0) {
            $score += 15;
            $entropyFormatted = number_format($entropy, 2);
            $factors[] = "Elevated entropy ({$entropyFormatted})";
        }

        return min(100, $score);
    }

    /**
     * Score suspicious API usage (20% weight)
     */
    private function scoreSuspiciousAPIs(array $apis, array &$factors): float
    {
        if (empty($apis)) {
            return 0;
        }

        $score = 0;
        $critical_apis = [];
        $high_apis = [];
        $medium_apis = [];

        foreach ($apis as $api => $info) {
            $severity = is_array($info) ? ($info['severity'] ?? 'medium') : 'medium';
            $category = is_array($info) ? ($info['category'] ?? '') : '';

            if (in_array($severity, ['critical', 'CRITICAL'])) {
                $critical_apis[] = $api;
                $score += 25;
            } elseif (in_array($severity, ['high', 'HIGH'])) {
                $high_apis[] = $api;
                $score += 15;
            } else {
                $medium_apis[] = $api;
                $score += 5;
            }
        }

        if (!empty($critical_apis)) {
            $factors[] = 'Critical APIs detected: ' . implode(', ', array_slice($critical_apis, 0, 3));
        }
        if (!empty($high_apis)) {
            $factors[] = 'High-risk APIs detected: ' . implode(', ', array_slice($high_apis, 0, 3));
        }

        $factors[] = "Total suspicious APIs: " . count($apis);

        return min(100, $score);
    }

    /**
     * Score IOC indicators (15% weight)
     */
    private function scoreIOCIndicators(array $file, array &$factors): float
    {
        $score = 0;
        $ioc_count = 0;

        $iocs = $file['iocs'] ?? [];

        foreach (['ip_addresses', 'domains', 'registry_keys', 'dlls', 'mutexes', 'file_paths'] as $type) {
            $count = count($iocs[$type] ?? []);
            $ioc_count += $count;
            if ($count > 0) {
                $score += min(30, $count * 5);
            }
        }

        if ($ioc_count > 0) {
            $factors[] = "IOC artifacts extracted: {$ioc_count} indicators";
        }

        return min(100, $score);
    }

    /**
     * Classify risk level based on score and escalation rules
     */
    private function classifyRisk(float $score, array $file): string
    {
        // Escalation rules: elevate severity for dangerous combinations
        $isPacked = $file['packer']['is_packed'] ?? false;
        $hasShellAPIs = $this->hasShellExecutionAPIs($file['suspicious_apis'] ?? []);
        $hasInjectionAPIs = $this->hasProcessInjectionAPIs($file['suspicious_apis'] ?? []);
        $vtData = $file['virustotal'] ?? [];
        $isHighDetection = ($vtData['malicious'] ?? 0) > 10;

        // If packed + shell execution APIs + high VT detections → escalate to CRITICAL
        if ($isPacked && $hasShellAPIs && $isHighDetection) {
            return self::RISK_CRITICAL;
        }

        // If injection + high entropy + high detections → escalate to CRITICAL
        if ($hasInjectionAPIs && ($file['entropy']['file_entropy'] ?? 0) > 7.5 && $isHighDetection) {
            return self::RISK_CRITICAL;
        }

        // High standalone VT detections (> 15) → escalate to HIGH
        if (($vtData['malicious'] ?? 0) >= 15) {
            return self::RISK_HIGH;
        }

        // Standard classification
        if ($score >= 85) {
            return self::RISK_CRITICAL;
        } elseif ($score >= 65) {
            return self::RISK_HIGH;
        } elseif ($score >= 40) {
            return self::RISK_MEDIUM;
        } elseif ($score >= 15) {
            return self::RISK_LOW;
        } else {
            return self::RISK_CLEAN;
        }
    }

    /**
     * Determine confidence level (0-100) based on number of detection sources
     */
    private function calculateConfidence(array $factors): float
    {
        $confidence_factors = count($factors);

        // More factors = higher confidence
        // 1-2 factors = 40-50%, 3-4 = 60-70%, 5+ = 80-95%
        if ($confidence_factors >= 5) {
            return 90;
        } elseif ($confidence_factors >= 3) {
            return 75;
        } elseif ($confidence_factors >= 1) {
            return 55;
        } else {
            return 30;
        }
    }

    /**
     * Generate human-readable risk reasoning
     */
    private function generateReasoning(float $score, string $level, array $factors, array $file): string
    {
        $lines = [];

        match ($level) {
            self::RISK_CRITICAL => $lines[] = '🔴 CRITICAL THREAT: Multiple high-severity indicators detected.',
            self::RISK_HIGH     => $lines[] = '🟠 HIGH RISK: Significant malware indicators present.',
            self::RISK_MEDIUM   => $lines[] = '🟡 MEDIUM RISK: Suspicious behavior detected.',
            self::RISK_LOW      => $lines[] = '🟢 LOW RISK: Minor indicators present, likely not malicious.',
            self::RISK_CLEAN    => $lines[] = '✅ CLEAN: No significant threats detected.',
        };

        $lines[] = '';
        $lines[] = 'Detection Summary:';
        foreach (array_slice($factors, 0, 5) as $factor) {
            $lines[] = "  • {$factor}";
        }

        if (count($factors) > 5) {
            $lines[] = "  • ... and " . (count($factors) - 5) . " more factors";
        }

        $lines[] = '';
        $lines[] = 'Confidence: ' . match (true) {
            $score >= 85 => 'VERY HIGH',
            $score >= 65 => 'HIGH',
            $score >= 40 => 'MEDIUM',
            $score >= 15 => 'LOW',
            default      => 'VERY LOW',
        };

        return implode("\n", $lines);
    }

    /**
     * Check for shell execution APIs
     */
    private function hasShellExecutionAPIs(array $apis): bool
    {
        $shell_apis = [
            'ShellExecuteW', 'ShellExecuteA',
            'CreateProcessW', 'CreateProcessA',
            'WinExec',
            'system', 'exec',
        ];

        return !empty(array_intersect(array_keys($apis), $shell_apis));
    }

    /**
     * Check for process injection APIs
     */
    private function hasProcessInjectionAPIs(array $apis): bool
    {
        $injection_apis = [
            'VirtualAlloc', 'VirtualAllocEx',
            'WriteProcessMemory',
            'CreateRemoteThread',
            'NtCreateThreadEx',
            'SetWindowsHookExW',
            'SetWindowsHookExA',
        ];

        return !empty(array_intersect(array_keys($apis), $injection_apis));
    }

    /**
     * Calculate overall job risk (maximum of all file risks, with deduplication)
     */
    public function calculateJobRisk(array $allFileRisks): array
    {
        if (empty($allFileRisks)) {
            return [
                'score'      => 0,
                'level'      => self::RISK_CLEAN,
                'confidence' => 0,
                'reasoning'  => 'No files analyzed.',
                'file_count' => 0,
            ];
        }

        // Overall risk = maximum file risk (worst case)
        $maxRisk = array_reduce($allFileRisks, function ($carry, $risk) {
            $carryScore = is_array($carry) ? $carry['score'] : 0;
            $riskScore = is_array($risk) ? $risk['score'] : 0;
            return $riskScore > $carryScore ? $risk : $carry;
        }, $allFileRisks[0]);

        $maxRisk['file_count'] = count($allFileRisks);
        $maxRisk['reasoning'] = "Overall job risk determined by highest-severity file.\n\n" . ($maxRisk['reasoning'] ?? '');

        return $maxRisk;
    }
}
