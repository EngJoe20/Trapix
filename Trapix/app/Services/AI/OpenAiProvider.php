<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAiProvider
 * --------------
 * AI adapter for OpenAI (GPT-4o / GPT-4 Turbo).
 * Implement additional providers by copying this class and changing
 * the HTTP call and provider/model identifiers.
 */
class OpenAiProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey  = config('trapix.openai_api_key', '');
        $this->model   = config('trapix.openai_model', 'gpt-4o-mini');
        $this->baseUrl = 'https://api.openai.com/v1';
    }

    public function providerName(): string { return 'openai'; }
    public function modelName(): string    { return $this->model; }

    public function analyze(array $analysisResult): array
    {
        $prompt = $this->buildAnalysisPrompt($analysisResult);

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $this->model,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role'    => 'system',
                            'content' => 'You are an expert malware analyst. Return a structured JSON analysis.',
                        ],
                        [
                            'role'    => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens' => 2048,
                ]);

            if ($response->failed()) {
                Log::error('OpenAI API error', ['status' => $response->status(), 'body' => $response->body()]);
                throw new \RuntimeException('OpenAI API returned ' . $response->status());
            }

            $data    = $response->json();
            $content = json_decode($data['choices'][0]['message']['content'] ?? '{}', true);
            $usage   = $data['usage'] ?? [];

            return [
                'insights'         => $content['insights'] ?? [],
                'summary'          => $content['summary'] ?? '',
                'recommendations'  => $content['recommendations'] ?? [],
                'mitre_techniques' => $content['mitre_techniques'] ?? [],
                'severity_score'   => (float) ($content['severity_score'] ?? 0),
                'tokens_used'      => (int) ($usage['total_tokens'] ?? 0),
                'cost_microcents'  => $this->estimateCost($usage),
            ];
        } catch (\Throwable $e) {
            Log::error('OpenAiProvider::analyze failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function generateReport(array $analysisResult, array $insights): ?string
    {
        // PDF generation via AI is planned for a future iteration.
        // Hook: pass markdown narrative to a PDF renderer here.
        return null;
    }

    // ── Private helpers ────────────────────────────────────────────────────────

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
        Analyze the following malware triage data and return a JSON object with these fields:
        - summary (string): concise analyst summary, max 3 sentences
        - insights (array of objects): [{category, detail}] — key findings
        - recommendations (array of strings): actionable response steps
        - mitre_techniques (array of strings): likely MITRE ATT&CK technique IDs
        - severity_score (float 0–10): overall threat severity

        Triage data:
        {$json}
        PROMPT;
    }

    private function estimateCost(array $usage): int
    {
        // GPT-4o-mini: ~$0.15/1M input tokens, ~$0.60/1M output tokens
        $input  = ($usage['prompt_tokens'] ?? 0) * 0.00000015;
        $output = ($usage['completion_tokens'] ?? 0) * 0.00000060;
        return (int) (($input + $output) * 1_000_000_00); // micro-cents
    }
}
