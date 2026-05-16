<?php

namespace App\Services\Report;

/**
 * IOCExtractor
 *
 * Extracts, categorizes, and normalizes Indicators of Compromise (IOCs)
 * from malware analysis data. Supports multiple export formats (JSON, CSV, STIX).
 */
class IOCExtractor
{
    const IOC_TYPE_IP_ADDRESS      = 'ip_address';
    const IOC_TYPE_DOMAIN          = 'domain';
    const IOC_TYPE_URL             = 'url';
    const IOC_TYPE_FILE_HASH       = 'file_hash';
    const IOC_TYPE_REGISTRY_KEY    = 'registry_key';
    const IOC_TYPE_MUTEX           = 'mutex';
    const IOC_TYPE_FILE_PATH       = 'file_path';
    const IOC_TYPE_DLL             = 'dll';
    const IOC_TYPE_EMAIL           = 'email';
    const IOC_TYPE_BASE64_BLOB     = 'base64_blob';
    const IOC_TYPE_COMMAND         = 'command';
    const IOC_TYPE_NAMED_PIPE      = 'named_pipe';

    const SEVERITY_CRITICAL = 'CRITICAL';
    const SEVERITY_HIGH     = 'HIGH';
    const SEVERITY_MEDIUM   = 'MEDIUM';
    const SEVERITY_LOW      = 'LOW';

    /**
     * Extract all IOCs from file analysis result
     *
     * @param array $fileAnalysis Single file analysis from Python
     * @return array Categorized and enriched IOCs with metadata
     */
    public function extractIOCs(array $fileAnalysis): array
    {
        $iocs = [];

        // Extract from structured IOC section
        if (isset($fileAnalysis['iocs'])) {
            $iocs = array_merge($iocs, $this->extractStructuredIOCs($fileAnalysis['iocs']));
        }

        // Extract from suspicious APIs (infer command execution, injection, etc.)
        if (isset($fileAnalysis['suspicious_apis'])) {
            $iocs = array_merge($iocs, $this->extractFromAPIs($fileAnalysis['suspicious_apis']));
        }

        // Extract from strings (IPs, domains, URLs, commands)
        if (isset($fileAnalysis['strings'])) {
            $iocs = array_merge($iocs, $this->extractFromStrings($fileAnalysis['strings']));
        }

        // Extract from imports (DLLs, suspicious calls)
        if (isset($fileAnalysis['pe_info']['imports'])) {
            $iocs = array_merge($iocs, $this->extractFromImports($fileAnalysis['pe_info']['imports']));
        }

        // Extract from VirusTotal threat label
        if (isset($fileAnalysis['virustotal']['threat_label'])) {
            $iocs = array_merge($iocs, $this->extractMalwareFamily($fileAnalysis['virustotal']['threat_label']));
        }

        // Deduplicate and normalize
        $iocs = $this->deduplicateIOCs($iocs);

        return $iocs;
    }

    /**
     * Extract IOCs from structured IOCs section
     */
    private function extractStructuredIOCs(array $iocData): array
    {
        $extracted = [];

        // IP Addresses
        foreach ($iocData['ip_addresses'] ?? [] as $ip) {
            if ($this->isValidIP($ip)) {
                $extracted[] = [
                    'type'      => self::IOC_TYPE_IP_ADDRESS,
                    'value'     => $ip,
                    'severity'  => self::SEVERITY_HIGH,
                    'source'    => 'network_indicators',
                    'context'   => 'Potential C&C or external communication',
                ];
            }
        }

        // Domains
        foreach ($iocData['domains'] ?? [] as $domain) {
            if ($this->isValidDomain($domain)) {
                $extracted[] = [
                    'type'      => self::IOC_TYPE_DOMAIN,
                    'value'     => $domain,
                    'severity'  => self::SEVERITY_HIGH,
                    'source'    => 'network_indicators',
                    'context'   => 'Potential C&C or malicious domain',
                ];
            }
        }

        // Registry Keys
        foreach ($iocData['registry_keys'] ?? [] as $key) {
            if (!empty($key)) {
                $severity = $this->getRegistryKeySeverity($key);
                $extracted[] = [
                    'type'      => self::IOC_TYPE_REGISTRY_KEY,
                    'value'     => $key,
                    'severity'  => $severity,
                    'source'    => 'persistence_indicators',
                    'context'   => 'Registry key access - potential persistence mechanism',
                ];
            }
        }

        // Mutexes
        foreach ($iocData['mutexes'] ?? [] as $mutex) {
            if (!empty($mutex)) {
                $extracted[] = [
                    'type'      => self::IOC_TYPE_MUTEX,
                    'value'     => $mutex,
                    'severity'  => self::SEVERITY_MEDIUM,
                    'source'    => 'execution_indicators',
                    'context'   => 'Mutex - unique malware instance marker or synchronization primitive',
                ];
            }
        }

        // DLLs
        foreach ($iocData['dlls'] ?? [] as $dll) {
            if (!empty($dll)) {
                $severity = $this->getDLLSeverity($dll);
                $extracted[] = [
                    'type'      => self::IOC_TYPE_DLL,
                    'value'     => $dll,
                    'severity'  => $severity,
                    'source'    => 'execution_indicators',
                    'context'   => 'DLL - loaded dynamic library',
                ];
            }
        }

        // File Paths
        foreach ($iocData['file_paths'] ?? [] as $path) {
            if (!empty($path)) {
                $severity = $this->getFilePathSeverity($path);
                $extracted[] = [
                    'type'      => self::IOC_TYPE_FILE_PATH,
                    'value'     => $path,
                    'severity'  => $severity,
                    'source'    => 'file_indicators',
                    'context'   => 'File system artifact - potential staging/execution location',
                ];
            }
        }

        // Base64 blobs
        foreach ($iocData['base64_blobs'] ?? [] as $blob) {
            if (!empty($blob) && strlen($blob) > 20) {
                $extracted[] = [
                    'type'      => self::IOC_TYPE_BASE64_BLOB,
                    'value'     => substr($blob, 0, 50) . '...',
                    'severity'  => self::SEVERITY_HIGH,
                    'source'    => 'obfuscation_indicators',
                    'context'   => 'Base64-encoded payload - likely obfuscated malicious code',
                    'full_value' => $blob,
                ];
            }
        }

        // Emails
        foreach ($iocData['emails'] ?? [] as $email) {
            if ($this->isValidEmail($email)) {
                $extracted[] = [
                    'type'      => self::IOC_TYPE_EMAIL,
                    'value'     => $email,
                    'severity'  => self::SEVERITY_MEDIUM,
                    'source'    => 'network_indicators',
                    'context'   => 'Email address - potential C&C or operator contact',
                ];
            }
        }

        return $extracted;
    }

    /**
     * Extract threat indicators from suspicious APIs
     */
    private function extractFromAPIs(array $apis): array
    {
        $extracted = [];

        $apiCategories = [
            'execution'   => ['ShellExecuteW', 'CreateProcessW', 'WinExec'],
            'injection'   => ['VirtualAlloc', 'WriteProcessMemory', 'CreateRemoteThread'],
            'persistence' => ['RegSetValueExW', 'SetWindowsHookExW'],
            'defense'     => ['GetFileAttributesW', 'FindFirstFileW', 'GetProcessHeap'],
        ];

        foreach ($apis as $api => $info) {
            $severity = is_array($info) ? ($info['severity'] ?? 'medium') : 'medium';
            $category = is_array($info) ? ($info['category'] ?? 'unknown') : 'unknown';

            // Map to behavioral IOC
            $context = match ($category) {
                'execution'   => 'Execution capability detected',
                'injection'   => 'Process injection capability - evasion/persistence',
                'persistence' => 'Persistence mechanism detected',
                'defense'     => 'Anti-analysis/anti-debugging capability',
                default       => 'Suspicious API call',
            };

            $extracted[] = [
                'type'      => self::IOC_TYPE_COMMAND,
                'value'     => $api,
                'severity'  => $this->mapSeverity($severity),
                'source'    => 'api_indicators',
                'context'   => $context,
                'category'  => $category,
            ];
        }

        return $extracted;
    }

    /**
     * Extract IOCs from string analysis
     */
    private function extractFromStrings(array $stringData): array
    {
        $extracted = [];

        $ascii = $stringData['ascii'] ?? [];
        $unicode = $stringData['unicode'] ?? [];
        $allStrings = array_merge($ascii, $unicode);

        foreach ($allStrings as $string) {
            if (empty($string) || strlen($string) < 5) {
                continue;
            }

            // Check for IP
            if ($this->isValidIP($string)) {
                $extracted[] = [
                    'type'      => self::IOC_TYPE_IP_ADDRESS,
                    'value'     => $string,
                    'severity'  => self::SEVERITY_HIGH,
                    'source'    => 'strings',
                    'context'   => 'IP found in binary strings',
                ];
                continue;
            }

            // Check for domain
            if ($this->isValidDomain($string)) {
                $extracted[] = [
                    'type'      => self::IOC_TYPE_DOMAIN,
                    'value'     => $string,
                    'severity'  => self::SEVERITY_HIGH,
                    'source'    => 'strings',
                    'context'   => 'Domain found in binary strings',
                ];
                continue;
            }

            // Check for email
            if ($this->isValidEmail($string)) {
                $extracted[] = [
                    'type'      => self::IOC_TYPE_EMAIL,
                    'value'     => $string,
                    'severity'  => self::SEVERITY_MEDIUM,
                    'source'    => 'strings',
                    'context'   => 'Email found in binary strings',
                ];
                continue;
            }

            // Check for URLs
            if (str_starts_with($string, 'http://') || str_starts_with($string, 'https://')) {
                $extracted[] = [
                    'type'      => self::IOC_TYPE_URL,
                    'value'     => $string,
                    'severity'  => self::SEVERITY_HIGH,
                    'source'    => 'strings',
                    'context'   => 'URL found in binary strings',
                ];
                continue;
            }

            // Check for registry paths
            if (str_starts_with($string, 'HKEY_')) {
                $severity = $this->getRegistryKeySeverity($string);
                $extracted[] = [
                    'type'      => self::IOC_TYPE_REGISTRY_KEY,
                    'value'     => $string,
                    'severity'  => $severity,
                    'source'    => 'strings',
                    'context'   => 'Registry path found in binary strings',
                ];
                continue;
            }

            // Check for Windows temp paths (staging indicators)
            if (preg_match('/(\\\\Temp|\\\\Appdata|AppData|%TEMP%|%APPDATA%)/i', $string)) {
                $extracted[] = [
                    'type'      => self::IOC_TYPE_FILE_PATH,
                    'value'     => $string,
                    'severity'  => self::SEVERITY_MEDIUM,
                    'source'    => 'strings',
                    'context'   => 'Temporary directory reference - potential staging location',
                ];
            }
        }

        return $extracted;
    }

    /**
     * Extract from PE imports
     */
    private function extractFromImports(array $imports): array
    {
        $extracted = [];

        foreach ($imports as $dll => $apis) {
            if (!empty($dll)) {
                $severity = $this->getDLLSeverity($dll);
                $extracted[] = [
                    'type'      => self::IOC_TYPE_DLL,
                    'value'     => $dll,
                    'severity'  => $severity,
                    'source'    => 'imports',
                    'context'   => 'Imported DLL - part of executable dependencies',
                    'apis'      => is_array($apis) ? count($apis) : 0,
                ];
            }
        }

        return $extracted;
    }

    /**
     * Extract malware family from VirusTotal threat label
     */
    private function extractMalwareFamily(string $threatLabel): array
    {
        if (empty($threatLabel)) {
            return [];
        }

        return [[
            'type'      => self::IOC_TYPE_FILE_HASH,
            'value'     => $threatLabel,
            'severity'  => self::SEVERITY_CRITICAL,
            'source'    => 'virustotal',
            'context'   => 'Classified malware family/variant',
        ]];
    }

    /**
     * Deduplicate IOCs by value (case-insensitive for domains/IPs)
     */
    private function deduplicateIOCs(array $iocs): array
    {
        $seen = [];
        $deduped = [];

        foreach ($iocs as $ioc) {
            $key = strtolower($ioc['value']);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $deduped[] = $ioc;
            }
        }

        return $deduped;
    }

    /**
     * Categorize IOCs by type and severity for report display
     */
    public function categorizeForReport(array $iocs): array
    {
        $categorized = [
            'by_type'     => [],
            'by_severity' => [],
            'summary'     => [],
        ];

        foreach ($iocs as $ioc) {
            // By type
            $type = $ioc['type'];
            if (!isset($categorized['by_type'][$type])) {
                $categorized['by_type'][$type] = [];
            }
            $categorized['by_type'][$type][] = $ioc;

            // By severity
            $severity = $ioc['severity'];
            if (!isset($categorized['by_severity'][$severity])) {
                $categorized['by_severity'][$severity] = [];
            }
            $categorized['by_severity'][$severity][] = $ioc;
        }

        // Generate summary
        $categorized['summary'] = [
            'total'     => count($iocs),
            'critical'  => count($categorized['by_severity'][self::SEVERITY_CRITICAL] ?? []),
            'high'      => count($categorized['by_severity'][self::SEVERITY_HIGH] ?? []),
            'medium'    => count($categorized['by_severity'][self::SEVERITY_MEDIUM] ?? []),
            'low'       => count($categorized['by_severity'][self::SEVERITY_LOW] ?? []),
        ];

        return $categorized;
    }

    /**
     * Export IOCs to JSON format
     */
    public function exportJSON(array $iocs): string
    {
        return json_encode($iocs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Export IOCs to CSV format
     */
    public function exportCSV(array $iocs): string
    {
        $csv = "Type,Value,Severity,Source,Context\n";
        foreach ($iocs as $ioc) {
            $row = [
                $ioc['type'],
                $ioc['value'],
                $ioc['severity'],
                $ioc['source'],
                $ioc['context'],
            ];
            $csv .= '"' . implode('","', array_map('addslashes', $row)) . "\"\n";
        }
        return $csv;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Validation & Helper Methods
    // ─────────────────────────────────────────────────────────────────────

    private function isValidIP(string $str): bool
    {
        return filter_var($str, FILTER_VALIDATE_IP) !== false;
    }

    private function isValidDomain(string $str): bool
    {
        return preg_match(
            '/^(?!-)[a-zA-Z0-9-]{1,63}(?<!-)(\.(?!-)[a-zA-Z0-9-]{1,63}(?<!-))*\.[a-zA-Z]{2,}$/',
            $str
        ) === 1;
    }

    private function isValidEmail(string $str): bool
    {
        return filter_var($str, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function mapSeverity(string $severity): string
    {
        return match (strtoupper($severity)) {
            'CRITICAL' => self::SEVERITY_CRITICAL,
            'HIGH'     => self::SEVERITY_HIGH,
            'MEDIUM'   => self::SEVERITY_MEDIUM,
            'LOW'      => self::SEVERITY_LOW,
            default    => self::SEVERITY_MEDIUM,
        };
    }

    private function getRegistryKeySeverity(string $key): string
    {
        $criticalPaths = ['Run', 'RunOnce', 'startup', 'Shell', 'ContextMenuHandlers', 'CurrentVersion\\Explorer\\Shell Folders'];

        foreach ($criticalPaths as $path) {
            if (stripos($key, $path) !== false) {
                return self::SEVERITY_HIGH;
            }
        }

        return self::SEVERITY_MEDIUM;
    }

    private function getDLLSeverity(string $dll): string
    {
        $suspiciousDLLs = ['kernel32', 'ntdll', 'user32', 'ws2_32'];
        $dangerousDLLs = ['advapi32', 'wininet', 'urlmon', 'winmm'];

        $lower = strtolower($dll);
        if (in_array($lower, $dangerousDLLs)) {
            return self::SEVERITY_HIGH;
        }
        if (in_array($lower, $suspiciousDLLs)) {
            return self::SEVERITY_MEDIUM;
        }

        return self::SEVERITY_LOW;
    }

    private function getFilePathSeverity(string $path): string
    {
        $tempPatterns = ['temp', 'appdata', 'windows\\system', 'programfiles'];

        $lower = strtolower($path);
        foreach ($tempPatterns as $pattern) {
            if (stripos($path, $pattern) !== false) {
                return self::SEVERITY_HIGH;
            }
        }

        return self::SEVERITY_MEDIUM;
    }
}
