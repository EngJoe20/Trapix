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
     * Timeout in seconds for a single analysis run.
     */
    private int $timeout;

    public function __construct()
    {
        $this->pythonBin  = config('trapix.python_executable', 'python');
        $this->scriptPath = config('trapix.python_script_path', base_path('../Tools/main.py'));
        $this->timeout    = (int) config('trapix.python_timeout', 300);
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
        $workDir = storage_path("app/jobs/{$job->id}/processing");
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
            ? storage_path("app/{$files->first()->path}")
            : storage_path("app/jobs/{$job->id}");

        // ── Build command ──────────────────────────────────────────────────────
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

        if ($job->vt_api_key) {
            $args[] = '--vt-key';
            $args[] = $job->vt_api_key;
        }

        // Tell the script where to put its output
        $args[] = '--output-dir';
        $args[] = $workDir;
        $args[] = '--format';
        $args[] = 'json';

        Log::info('PythonBridge: Launching subprocess', [
            'job'  => $job->id,
            'args' => implode(' ', array_slice($args, 0, 5)) . ' ...',
        ]);

        // ── Execute ─────────────────────────────────────────────────────────────
        $process = new Process($args, $workDir, null, null, $this->timeout);

        try {
            $process->run();
        } catch (\Exception $e) {
            Log::error('PythonBridge: Process exception', ['job' => $job->id, 'err' => $e->getMessage()]);
            return $this->failureResult($e->getMessage(), -1);
        }

        $exitCode = $process->getExitCode();
        $stderr   = trim($process->getErrorOutput());

        if ($exitCode !== 0) {
            Log::warning('PythonBridge: Non-zero exit', ['job' => $job->id, 'code' => $exitCode, 'stderr' => $stderr]);
            return $this->failureResult($stderr ?: 'Python script returned non-zero exit code', $exitCode);
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
        $pdfSrc  = "{$workDir}/report.pdf";
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
        $riskLevel = $bridgeResult['result']['risk_level'] ?? null;

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
        return [
            'job_id'      => $job->id,
            'work_dir'    => $workDir,
            'skip_vt'     => $job->skip_vt,
            'vt_api_key'  => $job->vt_api_key,
            'options'     => $job->options ?? [],
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
