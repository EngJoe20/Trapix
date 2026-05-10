<?php

namespace App\Services\AI;

use App\Models\AnalysisJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * GeminiProvider
 * --------------
 * AI adapter that first invokes the local Expert System (AI Agent/malware_analyzer.py),
 * then falls back to Google AI Studio (Gemini API) for complex behavioral analysis.
 *
 * This is the primary AI provider for Trapix.
 */
class GeminiProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $model;
    private string $pythonBin;
    private string $agentPath;

    public function __construct()
    {
        $this->apiKey    = config('trapix.gemini_api_key', '');
        $this->model     = config('trapix.gemini_model', 'gemini-2.5-flash');
        $this->pythonBin = config('trapix.python_executable', 'python');
        $this->agentPath = config('trapix.ai_agent_path', base_path('../../AI Agent'));
    }

    public function providerName(): string { return 'gemini'; }
    public function modelName(): string    { return $this->model; }

    /**
     * Set the dynamic per-user configuration for this provider instance.
     *
     * @param string|null $apiKey
     * @param string|null $baseUrl
     * @param string|null $model
     * @return self
     */
    public function setConfig(?string $apiKey = null, ?string $baseUrl = null, ?string $model = null): self
    {
        if ($apiKey) {
            $this->apiKey = $apiKey;
        }
        if ($model) {
            $this->model = $model;
        }
        return $this;
    }

    /**
     * Run the hybrid expert system analysis.
     *
     * Flow:
     * 1. Write result JSON to a temp file in the processing directory.
     * 2. Invoke `malware_analyzer.py --file <result.json>` as subprocess.
     * 3. Read `analysis_<result.json>` output.
     * 4. If subprocess fails → call Gemini API directly.
     */
    public function analyze(array $analysisResult): array
    {
        // ── Determine which result to analyze ──────────────────────────────────
        // analysisResult is the full job result (may contain 'results' array for folders)
        $firstResult = $analysisResult['results'][0] ?? $analysisResult;

        // ── Write temp file for the Python script ──────────────────────────────
        $tempDir  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'trapix_ai_' . uniqid();
        mkdir($tempDir, 0755, true);
        $inputFile = $tempDir . DIRECTORY_SEPARATOR . 'result.json';
        $outputFile = $tempDir . DIRECTORY_SEPARATOR . 'analysis_result.json';

        file_put_contents($inputFile, json_encode($firstResult, JSON_PRETTY_PRINT));

        // ── Try the local Expert System first ──────────────────────────────────
        $expertData = $this->runExpertSystem($inputFile, $outputFile);

        if ($expertData) {
            Log::info('GeminiProvider: Expert system succeeded', ['keys' => array_keys($expertData)]);
            return $this->normalizeExpertData($expertData, $firstResult);
        }

        // ── Fallback: call Gemini API directly ────────────────────────────────
        Log::warning('GeminiProvider: Expert system failed, falling back to Gemini API');
        return $this->callGeminiApi($firstResult);
    }

    /**
     * PDF generation is handled by the Python bridge (bridge.py).
     * Here we just return null — the PDF was already created before this is called.
     */
    public function generateReport(array $analysisResult, array $insights): ?string
    {
        return null;
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Invoke the local AI Agent expert system subprocess.
     */
    private function runExpertSystem(string $inputFile, string $outputFile): ?array
    {
        $scriptPath = $this->agentPath . DIRECTORY_SEPARATOR . 'malware_analyzer.py';

        if (! file_exists($scriptPath)) {
            Log::warning('GeminiProvider: malware_analyzer.py not found', ['path' => $scriptPath]);
            return null;
        }

        $env = ['GEMINI_API_KEY' => $this->apiKey];
        $process = new Process(
            [$this->pythonBin, $scriptPath, '--file', $inputFile],
            dirname($scriptPath),
            $env,
            null,
            120  // 2 minute timeout for AI analysis
        );

        try {
            $process->run();
        } catch (\Exception $e) {
            Log::error('GeminiProvider: subprocess exception', ['error' => $e->getMessage()]);
            return null;
        }

        if (! $process->isSuccessful()) {
            Log::warning('GeminiProvider: Expert system subprocess failed', [
                'exit_code' => $process->getExitCode(),
                'stderr'    => $process->getErrorOutput(),
            ]);
            return null;
        }

        // The script saves output as analysis_{basename} in the same directory
        $expectedOutput = dirname($inputFile) . DIRECTORY_SEPARATOR . 'analysis_result.json';
        if (! file_exists($expectedOutput)) {
            Log::warning('GeminiProvider: Expected output file not found', ['path' => $expectedOutput]);
            return null;
        }

        $data = json_decode(file_get_contents($expectedOutput), true);
        return is_array($data) ? $data : null;
    }

    /**
     * Call Gemini API directly when the expert system is unavailable.
     */
    private function callGeminiApi(array $result): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('GEMINI_API_KEY is not set. Please add it to your .env file.');
        }

        $strings = array_merge(
            array_slice($result['strings']['ascii'] ?? [], 0, 200),
            array_slice($result['strings']['unicode'] ?? [], 0, 100)
        );

        $context = [
            'file_name'              => $result['file_name'] ?? 'unknown',
            'is_packed'              => $result['packer']['is_packed'] ?? false,
            'virustotal_ratio'       => $result['virustotal']['detection_ratio'] ?? 'N/A',
            'suspicious_apis_flagged' => $result['suspicious_apis'] ?? [],
            'all_imports'            => $result['pe_info']['imports'] ?? [],
            'extracted_strings'      => $strings,
        ];

        $systemInstruction = 'You are a senior malware analyst assistant. Analyze the provided data and return ONLY a valid JSON object with these fields: summary (string), insights (array of {category, detail}), recommendations (array of strings), mitre_techniques (array of strings like "T1027"), severity_score (float 0-10). No markdown, no code blocks, just raw JSON.';

        $prompt = 'Analyze this malware triage data:\n\n' . json_encode($context, JSON_PRETTY_PRINT);

        $url     = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
        $payload = [
            'system_instruction' => ['parts' => [['text' => $systemInstruction]]],
            'contents'           => [['parts' => [['text' => $prompt]]]],
            'generationConfig'   => ['temperature' => 0.2, 'responseMimeType' => 'application/json'],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 60,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || ! $response) {
            Log::error('GeminiProvider: Gemini API call failed', ['http_code' => $httpCode]);
            throw new \RuntimeException("Gemini API returned HTTP {$httpCode}");
        }

        $data    = json_decode($response, true);
        $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
        $parsed  = json_decode($rawText, true) ?? [];

        return [
            'insights'         => $parsed['insights'] ?? [],
            'summary'          => $parsed['summary'] ?? '',
            'recommendations'  => $parsed['recommendations'] ?? [],
            'mitre_techniques' => $parsed['mitre_techniques'] ?? [],
            'severity_score'   => (float) ($parsed['severity_score'] ?? 0),
            'tokens_used'      => 0,
            'cost_microcents'  => 0,
        ];
    }

    /**
     * Convert the Expert System output format into the AiProviderInterface contract.
     */
    private function normalizeExpertData(array $expertData, array $originalResult): array
    {
        $behavioralInsights = $expertData['behavioral_insights'] ?? [];
        $threatClass        = $expertData['threat_classification'] ?? [];
        $riskAssessment     = $expertData['risk_assessment'] ?? [];

        // Convert behavioral insights to the standard {category, detail} format
        $insights = array_map(fn($b) => [
            'category' => $b['category'] ?? 'Behavioral',
            'detail'   => "[{$b['severity']}] {$b['title']}: {$b['detail']}",
        ], $behavioralInsights);

        // Merge analyst + defender recommendations
        $analystRecs  = $threatClass['recommended_actions']['analyst'] ?? [];
        $defenderRecs = $threatClass['recommended_actions']['defender'] ?? [];
        $recommendations = array_merge($analystRecs, $defenderRecs);

        // Extract MITRE techniques from behavioral insights
        $mitre = array_filter(array_map(
            fn($b) => $b['mitre_attack']['technique'] ?? null,
            $behavioralInsights
        ));

        $riskLevel = $riskAssessment['overall_risk_level'] ?? 'UNKNOWN';
        $vtRatio   = $riskAssessment['virustotal_detection_percent'] ?? 0;
        $score     = min(10, ($vtRatio / 10) + (count($behavioralInsights) * 0.5));

        return [
            'insights'              => array_values($insights),
            'summary'               => "File: {$expertData['analysis_metadata']['file_name']} | Risk: {$riskLevel} | VT: {$riskAssessment['virustotal_detection_ratio']}",
            'recommendations'       => array_values($recommendations),
            'mitre_techniques'      => array_values(array_unique($mitre)),
            'severity_score'        => round($score, 2),
            'tokens_used'           => 0,
            'cost_microcents'       => 0,
            // Extra expert-system-specific fields stored alongside standard fields
            'behavioral_insights'   => $behavioralInsights,
            'threat_classification' => $threatClass,
            'iocs'                  => $expertData['iocs'] ?? [],
            'suspicious_strings'    => $expertData['suspicious_strings'] ?? [],
            'import_analysis'       => $expertData['import_analysis'] ?? [],
        ];
    }
}
