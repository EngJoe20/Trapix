<?php

namespace App\Services;

use App\Models\AnalysisJob;
use App\Models\AnalysisReport;
use App\Models\AiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * PythonBridgeService
 * -------------------
 * Executes the Python analysis engine for a given AnalysisJob.
 *
 * Communication contract:
 *   Input  → analysis_request.json  written to a temp working directory
 *   Output ← result.json + optional report.pdf  read back from the same dir
 *
 * The Python script is invoked via a subprocess in a sandboxed working directory.
 * stdout/stderr are captured and attached to the job record.
 */
class PythonBridgeService
{
    /**
     * Absolute path to the Python interpreter.
     * Override via PYTHON_EXECUTABLE in .env
     */
    private string $pythonBin;

    /**
     * Absolute path to main.py inside the Tools folder.
     */
    private string $scriptPath;

    /**
     * VirusTotal API Key from config
     */
    private ?string $vtApiKey;

    public function __construct()
    {
        $this->pythonBin  = config('trapix.python_executable', 'python');
        $this->scriptPath = config('trapix.python_script_path', base_path('../Tools/main.py'));
        $this->timeout    = (int) config('trapix.python_timeout', 300);
        $this->vtApiKey   = config('trapix.virustotal_api_key');
    }

    /**
     * Run the Python engine for a job.
     * Writes analysis_request.json, executes main.py, reads result.json.
     *
     * @return array{success: bool, result: array|null, pdf_path: string|null, exit_code: int, stderr: string}
     */
    public function run(AnalysisJob $job): array
    {
        // ── Build working directory ─────────────────────────────────────────────
        $workDir = Storage::disk(config('trapix.storage_disk', 'local'))->path("jobs/{$job->id}/processing");
        if (! is_dir($workDir)) {
            mkdir($workDir, 0755, true);
        }

        // ── Write analysis_request.json ────────────────────────────────────────
        $request = $this->buildRequest($job, $workDir);
        file_put_contents("{$workDir}/analysis_request.json", json_encode($request, JSON_PRETTY_PRINT));

        // ── Resolve the target path for the Python script ──────────────────────
        // Single file → pass path directly; folder → pass the jobs/{id}/ directory
        $files      = $job->files;
        $targetPath = count($files) === 1
            ? Storage::disk(config('trapix.storage_disk', 'local'))->path($files->first()->path)
            : Storage::disk(config('trapix.storage_disk', 'local'))->path("jobs/{$job->id}");

        // ── Build command ──────────────────────────────────────────────────────
        if (! file_exists($targetPath)) {
            Log::error("PythonBridge: Target path not found on disk", [
                'job_id' => $job->id,
                'path'   => $targetPath
            ]);
            return $this->failureResult("Internal error: Analysis target not found on disk.", -1);
        }

        $args = [
            $this->pythonBin,
            $this->scriptPath,
        ];

        if ($job->input_type === 'file') {
            $args[] = '--file';
        } else {
            $args[] = '--dir';
        }

        $args[] = $targetPath;

        // Optional flags from the job options
        if ($job->skip_vt) {
            $args[] = '--no-vt';
        }

        $vtKey = $job->vt_api_key ?: $this->vtApiKey;
        if ($vtKey) {
            $args[] = '--vt-key';
            $args[] = $vtKey;
        }

        // Tell the script where to put its output
        $args[] = '--output-dir';
        $args[] = $workDir;
        $args[] = '--format';
        $args[] = 'json';

        Log::info('PythonBridge: Launching subprocess', [
            'job'     => $job->id,
            'command' => implode(' ', $args),
            'workdir' => $workDir,
        ]);

        // ── Execute ─────────────────────────────────────────────────────────────
        $process = new Process($args, $workDir, [
            'PYTHONIOENCODING' => 'utf-8',
        ], null, $this->timeout);

        try {
            $process->run();
        } catch (\Exception $e) {
            Log::error('PythonBridge: Process execution exception', [
                'job'     => $job->id,
                'file'    => $job->files->first()?->original_name,
                'error'   => $e->getMessage(),
                'command' => implode(' ', $args)
            ]);
            return $this->failureResult("Subprocess exception: {$e->getMessage()}", -1);
        }

        $exitCode = $process->getExitCode();
        $stderr   = trim($process->getErrorOutput());
        $stdout   = trim($process->getOutput());

        if ($exitCode !== 0) {
            Log::warning('PythonBridge: Subprocess returned non-zero exit code', [
                'job'     => $job->id,
                'file'    => $job->files->first()?->original_name,
                'code'    => $exitCode,
                'stderr'  => $stderr,
                'stdout'  => $stdout,
                'command' => implode(' ', $args)
            ]);
            return $this->failureResult($stderr ?: 'Python script failed without stderr output', $exitCode);
        }

        // ── Parse result.json ─────────────────────────────────────────────────
        $resultFile = "{$workDir}/result.json";

        if (! file_exists($resultFile)) {
            Log::error('PythonBridge: result.json not found', ['job' => $job->id]);
            return $this->failureResult('result.json was not produced by the analysis script', $exitCode);
        }

        $result = json_decode(file_get_contents($resultFile), true);

        // ── Check for optional PDF ────────────────────────────────────────────
        $pdfPath = null;
        $pdfSrc  = $workDir . DIRECTORY_SEPARATOR . 'report.pdf';
        if (file_exists($pdfSrc)) {
            // Move to permanent reports storage
            $pdfDest = "reports/{$job->id}/report.pdf";
            Storage::disk('local')->put($pdfDest, file_get_contents($pdfSrc));
            $pdfPath = $pdfDest;
        }

        return [
            'success'  => true,
            'result'   => $result,
            'pdf_path' => $pdfPath,
            'exit_code' => $exitCode,
            'stderr'   => $stderr,
        ];
    }

    /**
     * Persist the bridge result into the database.
     */
    public function persistResult(AnalysisJob $job, array $bridgeResult): void
    {
        // ── Extract risk level from summary ───────────────────────────────────
        $riskLevel = $bridgeResult['result']['summary']['risk_level'] ?? null;

        // ── Save JSON result next to the job ──────────────────────────────────
        $jsonPath = null;
        if (! empty($bridgeResult['result'])) {
            $jsonPath = "reports/{$job->id}/result.json";
            Storage::disk('local')->put($jsonPath, json_encode($bridgeResult['result'], JSON_PRETTY_PRINT));
        }

        // ── Upsert report record ──────────────────────────────────────────────
        AnalysisReport::updateOrCreate(
            ['analysis_job_id' => $job->id],
            [
                'pdf_path'   => $bridgeResult['pdf_path'],
                'json_path'  => $jsonPath,
                'risk_level' => $riskLevel,
            ]
        );

        // ── Update job ────────────────────────────────────────────────────────
        $job->update([
            'result'            => $bridgeResult['result'],
            'python_exit_code'  => $bridgeResult['exit_code'],
            'status'            => $bridgeResult['success']
                                    ? AnalysisJob::STATUS_COMPLETED
                                    : AnalysisJob::STATUS_FAILED,
            'error_message'     => $bridgeResult['success'] ? null : $bridgeResult['stderr'],
            'completed_at'      => now(),
        ]);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function buildRequest(AnalysisJob $job, string $workDir): array
    {
        $fileMap = [];
        foreach ($job->files as $f) {
            $fileMap[$f->stored_name] = $f->original_name;
        }

        return [
            'job_id'        => $job->id,
            'work_dir'      => $workDir,
            'skip_vt'       => $job->skip_vt,
            'vt_api_key'    => $job->vt_api_key,
            'original_name' => $job->files->first()?->original_name,
            'file_map'      => $fileMap,
            'options'       => $job->options ?? [],
        ];
    }

    private function failureResult(string $error, int $exitCode): array
    {
        return [
            'success'   => false,
            'result'    => null,
            'pdf_path'  => null,
            'exit_code' => $exitCode,
            'stderr'    => $error,
        ];
    }
}
