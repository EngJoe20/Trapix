<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OllamaProvider
 * --------------
 * AI adapter for local Ollama instances.
 */
class OllamaProvider implements AiProviderInterface
{
    private string $apiKey; // Not typically used for Ollama, but kept for interface
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey  = '';
        $this->model   = config('trapix.ollama_model', 'llama3');
        $this->baseUrl = config('trapix.ollama_base_url', 'http://localhost:11434');
    }

    public function providerName(): string { return 'ollama'; }
    public function modelName(): string    { return $this->model; }

    public function setConfig(?string $apiKey = null, ?string $baseUrl = null, ?string $model = null): self
    {
        if ($apiKey) {
            $this->apiKey = $apiKey;
        }
        if ($baseUrl) {
            $this->baseUrl = rtrim($baseUrl, '/');
        }
        if ($model) {
            $this->model = $model;
        }
        return $this;
    }

    public function analyze(array $analysisResult): array
    {
        if (empty($this->baseUrl)) {
            throw new \RuntimeException('Ollama Base URL is not set.');
        }

        $prompt = $this->buildAnalysisPrompt($analysisResult);

        try {
            // Ollama Generate endpoint expects a prompt and returns streamed or non-streamed text.
            // We use non-streamed JSON format.
            $response = Http::timeout(120) // Local models might be slow
                ->post("{$this->baseUrl}/api/generate", [
                    'model'  => $this->model,
                    'prompt' => $prompt,
                    'format' => 'json',
                    'stream' => false,
                ]);

            if ($response->failed()) {
                Log::error('Ollama API error', ['status' => $response->status(), 'body' => $response->body()]);
                throw new \RuntimeException('Ollama API returned ' . $response->status());
            }

            $data    = $response->json();
            $rawText = $data['response'] ?? '{}';
            
            $content = json_decode($rawText, true) ?? [];

            return [
                'insights'         => $content['insights'] ?? [],
                'summary'          => $content['summary'] ?? '',
                'recommendations'  => $content['recommendations'] ?? [],
                'mitre_techniques' => $content['mitre_techniques'] ?? [],
                'severity_score'   => (float) ($content['severity_score'] ?? 0),
                'tokens_used'      => (int) ($data['eval_count'] ?? 0),
                'cost_microcents'  => 0, // Local = Free
            ];
        } catch (\Throwable $e) {
            Log::error('OllamaProvider::analyze failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function generateReport(array $analysisResult, array $insights): ?string
    {
        return null;
    }

    private function buildAnalysisPrompt(array $result): string
    {
        $json = json_encode([
            'file_name'       => $result['file_name'] ?? 'unknown',
            'risk_level'      => $result['risk_level'] ?? 'Unknown',
            'virustotal'      => $result['virustotal'] ?? [],
            'packer'          => $result['packer'] ?? [],
            'suspicious_apis' => $result['suspicious_apis'] ?? [],
            'iocs'            => $result['iocs'] ?? [],
            'entropy'         => $result['entropy'] ?? [],
        ], JSON_PRETTY_PRINT);

        return <<<PROMPT
You are an expert malware analyst. Analyze the following malware triage data and return a JSON object with these exact fields:
- summary (string): concise analyst summary, max 3 sentences
- insights (array of objects): [{"category": "...", "detail": "..."}] — key findings
- recommendations (array of strings): actionable response steps
- mitre_techniques (array of strings): likely MITRE ATT&CK technique IDs
- severity_score (float 0.0-10.0): overall threat severity

Triage data:
{$json}

Return ONLY valid JSON format.
PROMPT;
    }
}
