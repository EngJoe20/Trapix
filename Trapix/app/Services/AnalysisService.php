<?php

namespace App\Services;

use App\Models\AnalysisJob;
use App\Models\AnalysisReport;
use App\Models\UploadedFile;
use App\Jobs\RunPythonAnalysis;
use Illuminate\Http\UploadedFile as HttpUploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * AnalysisService
 * ---------------
 * Orchestrates the full upload → store → dispatch → result cycle.
 * Controllers should call only this service; never interact with jobs or storage directly.
 */
class AnalysisService
{
    public function __construct(
        private QuotaService $quota,
        private StorageService $storageService,
    ) {}

    /**
     * Create an analysis job, store files, and dispatch the background worker.
     *
     * @param  array<HttpUploadedFile>  $files
     * @param  int|null                 $userId
     * @param  string|null              $guestToken
     * @param  array                    $options   (skip_vt, vt_api_key, etc.)
     */
    public function createJob(
        array $files,
        ?int $userId,
        ?string $guestToken,
        array $options = []
    ): AnalysisJob {
        // ── 1. Create the job record ───────────────────────────────────────────
        $job = AnalysisJob::create([
            'user_id'     => $userId,
            'guest_token' => $guestToken,
            'status'      => AnalysisJob::STATUS_PENDING,
            'input_type'  => count($files) === 1 ? 'file' : 'folder',
            'file_count'  => count($files),
            'skip_vt'     => $options['skip_vt'] ?? false,
            'vt_api_key'  => $options['vt_api_key'] ?? null,
            'options'     => $options,
        ]);

        // ── 2. Store each uploaded file ────────────────────────────────────────
        foreach ($files as $file) {
            $this->storeUploadedFile($file, $job);
        }

        // ── 3. Dispatch background job ─────────────────────────────────────────
        RunPythonAnalysis::dispatch($job->id)
            ->onQueue($this->resolveQueue($userId));

        Log::info('AnalysisJob dispatched', ['job_id' => $job->id, 'files' => count($files)]);

        return $job->fresh(['files']);
    }

    /**
     * Store one file in the configured disk under jobs/{jobId}/ directory.
     */
    private function storeUploadedFile(HttpUploadedFile $file, AnalysisJob $job): UploadedFile
    {
        $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path       = "jobs/{$job->id}/{$storedName}";

        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        return UploadedFile::create([
            'analysis_job_id' => $job->id,
            'original_name'   => $file->getClientOriginalName(),
            'stored_name'     => $storedName,
            'disk'            => 'local',
            'path'            => $path,
            'mime_type'       => $file->getMimeType(),
            'size_bytes'      => $file->getSize(),
            'sha256'          => hash_file('sha256', $file->getRealPath()),
        ]);
    }

    /**
     * Priority queue for Pro/Enterprise users.
     */
    private function resolveQueue(?int $userId): string
    {
        if (! $userId) {
            return 'default';
        }

        $user = \App\Models\User::find($userId);
        $plan = $user?->effectivePlan();

        return ($plan?->priority_processing) ? 'high' : 'default';
    }

    /**
     * Retrieve a job with its relationships — for the results page.
     */
    public function getJobResult(string $jobId): ?AnalysisJob
    {
        return AnalysisJob::with(['files', 'report', 'aiResponse'])->find($jobId);
    }
}
