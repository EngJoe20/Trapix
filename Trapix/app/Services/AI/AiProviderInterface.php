<?php

namespace App\Services\AI;

/**
 * AiProviderInterface
 * -------------------
 * Defines the contract every AI provider adapter must implement.
 * Swap OpenAI → Claude → Ollama without touching business logic.
 */
interface AiProviderInterface
{
    /**
     * Analyze the JSON result from the Python engine and return structured insights.
     *
     * @param  array  $analysisResult  The result dict from Python (file_analyzer output)
     * @return array{
     *   insights: array,
     *   summary: string,
     *   recommendations: list<string>,
     *   mitre_techniques: list<string>,
     *   severity_score: float,
     *   tokens_used: int,
     *   cost_microcents: int,
     * }
     */
    public function analyze(array $analysisResult): array;

    /**
     * Generate a PDF narrative report based on the AI insights.
     * Returns the path to the generated PDF file (absolute) or null.
     */
    public function generateReport(array $analysisResult, array $insights): ?string;

    /** Provider identifier (e.g. "openai", "claude", "gemini", "ollama") */
    public function providerName(): string;

    /** Model identifier (e.g. "gpt-4o", "claude-3-5-sonnet", "gemini-1.5-pro") */
    public function modelName(): string;
}
