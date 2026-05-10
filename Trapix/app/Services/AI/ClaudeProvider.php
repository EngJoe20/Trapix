<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ClaudeProvider
 * --------------
 * AI adapter for Anthropic Claude models.
 */
class ClaudeProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey  = config('trapix.claude_api_key', '');
        $this->model   = config('trapix.claude_model', 'claude-3-5-sonnet-20240620');
        $this->baseUrl = 'https://api.anthropic.com/v1';
    }

    public function providerName(): string { return 'claude'; }
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
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Claude API Key is not set.');
        }

        $prompt = $this->buildAnalysisPrompt($analysisResult);

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->timeout(60)
            ->post("{$this->baseUrl}/messages", [
                'model'      => $this->model,
                'max_tokens' => 2048,
                'system'     => 'You are an expert malware analyst. Return ONLY a structured JSON object with the requested fields. No markdown formatting, just pure JSON.',
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            if ($response->failed()) {
                Log::error('Claude API error', ['status' => $response->status(), 'body' => $response->body()]);
                throw new \RuntimeException('Claude API returned ' . $response->status());
            }

            $data    = $response->json();
            $rawText = $data['content'][0]['text'] ?? '{}';
            
            // Clean markdown blocks if Claude accidentally included them
            $rawText = preg_replace('/```json\s*/', '', $rawText);
            $rawText = preg_replace('/```\s*/', '', $rawText);
            
            $content = json_decode($rawText, true) ?? [];
            $usage   = $data['usage'] ?? [];

            return [
                'insights'         => $content['insights'] ?? [],
                'summary'          => $content['summary'] ?? '',
                'recommendations'  => $content['recommendations'] ?? [],
                'mitre_techniques' => $content['mitre_techniques'] ?? [],
                'severity_score'   => (float) ($content['severity_score'] ?? 0),
                'tokens_used'      => (int) (($usage['input_tokens'] ?? 0) + ($usage['output_tokens'] ?? 0)),
                'cost_microcents'  => 0, // Implement cost logic if needed
            ];
        } catch (\Throwable $e) {
            Log::error('ClaudeProvider::analyze failed', ['error' => $e->getMessage()]);
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
Analyze the following malware triage data and return a JSON object with these exact fields:
- summary (string): concise analyst summary, max 3 sentences
- insights (array of objects): [{"category": "...", "detail": "..."}] — key findings
- recommendations (array of strings): actionable response steps
- mitre_techniques (array of strings): likely MITRE ATT&CK technique IDs
- severity_score (float 0.0–10.0): overall threat severity

Triage data:
{$json}

Return ONLY valid JSON.
PROMPT;
    }
}
