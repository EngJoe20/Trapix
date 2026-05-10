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

        // ── 2. Store each uploaded file (with ZIP extraction logic) ────────────
        $finalFileCount = 0;
        foreach ($files as $file) {
            if ($this->isZipFile($file)) {
                $extractedCount = $this->extractAndStoreZip($file, $job);
                if ($extractedCount === 0) {
                    // Fallback: If zip extraction fails or yields 0 files, store it as a regular file
                    $this->storeUploadedFile($file, $job);
                    $finalFileCount++;
                } else {
                    $finalFileCount += $extractedCount;
                }
            } else {
                $this->storeUploadedFile($file, $job);
                $finalFileCount++;
            }
        }

        // ── 3. Update job if count changed or ZIP was extracted ────────────────
        if ($finalFileCount > 1) {
            $job->update([
                'input_type' => 'folder',
                'file_count' => $finalFileCount
            ]);
        }

        // ── 4. Dispatch background job ─────────────────────────────────────────
        RunPythonAnalysis::dispatch($job->id)
            ->onQueue($this->resolveQueue($userId));

        Log::info('AnalysisJob dispatched', ['job_id' => $job->id, 'final_files' => $finalFileCount]);

        return $job->fresh(['files']);
    }

    /**
     * Check if the uploaded file is a ZIP archive.
     */
    private function isZipFile(HttpUploadedFile $file): bool
    {
        $mime = $file->getMimeType();
        $ext  = strtolower($file->getClientOriginalExtension());
        
        return in_array($mime, ['application/zip', 'application/x-zip-compressed']) || $ext === 'zip';
    }

    /**
     * Extract ZIP contents and store each file as an UploadedFile.
     */
    private function extractAndStoreZip(HttpUploadedFile $zipFile, AnalysisJob $job): int
    {
        if (!class_exists('\ZipArchive')) {
            Log::error('ZipArchive extension is not installed.');
            return 0;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipFile->getRealPath()) !== true) {
            Log::error('Failed to open ZIP file', ['path' => $zipFile->getRealPath()]);
            return 0;
        }

        $extractPath = storage_path("app/temp/zip_extract_{$job->id}_" . Str::random(8));
        if (!is_dir($extractPath)) {
            mkdir($extractPath, 0755, true);
        }

        Log::info("Extracting ZIP to: {$extractPath}");
        
        if (!$zip->extractTo($extractPath)) {
            Log::error("Failed to extract ZIP", ['job_id' => $job->id]);
            $zip->close();
            return 0;
        }
        $zip->close();

        $filesCount = 0;
        $allFiles   = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($allFiles as $file) {
            if ($file->isDir()) continue;

            $filename = $file->getFilename();
            $pathname = $file->getPathname();
            
            // Skip system/internal files
            if (str_starts_with($filename, '.') || str_contains($pathname, '__MACOSX')) {
                continue;
            }

            $extension  = $file->getExtension();
            $storedName = Str::uuid() . ($extension ? '.' . $extension : '');
            $destPath   = "jobs/{$job->id}/{$storedName}";

            $content = file_get_contents($file->getRealPath());
            if ($content === false) {
                Log::error("Failed to read extracted file: {$file->getRealPath()}");
                continue;
            }

            Storage::disk('local')->put($destPath, $content);
            $fullDestPath = Storage::disk('local')->path($destPath);

            Log::info("Stored file: {$filename} -> {$destPath} (Size: " . strlen($content) . " bytes)");

            UploadedFile::create([
                'analysis_job_id' => $job->id,
                'original_name'   => $filename,
                'stored_name'     => $storedName,
                'disk'            => 'local',
                'path'            => $destPath,
                'mime_type'       => \Illuminate\Support\Facades\File::mimeType($fullDestPath) ?? 'application/octet-stream',
                'size_bytes'      => strlen($content),
                'sha256'          => hash('sha256', $content),
            ]);

            $filesCount++;
        }

        // Cleanup temp extraction folder
        \Illuminate\Support\Facades\File::deleteDirectory($extractPath);

        return $filesCount;
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
