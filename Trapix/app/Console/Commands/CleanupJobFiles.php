<?php

namespace App\Console\Commands;

use App\Models\AnalysisJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * CleanupJobFiles
 * ---------------
 * Artisan command that removes old temporary processing files.
 * Schedule: daily.
 *
 * Usage:
 *   php artisan trapix:cleanup
 *   php artisan trapix:cleanup --days=14
 */
class CleanupJobFiles extends Command
{
    protected $signature   = 'trapix:cleanup {--days= : Override the default cleanup age (days)}';
    protected $description = 'Remove temporary analysis files older than N days';

    public function handle(): int
    {
        $days  = (int) ($this->option('days') ?? config('trapix.job_cleanup_days', 7));
        $cutoff = now()->subDays($days);

        $this->info("🧹 Cleaning analysis files older than {$days} days (before {$cutoff->toDateString()})...");

        $jobs = AnalysisJob::where('created_at', '<', $cutoff)
            ->whereIn('status', [AnalysisJob::STATUS_COMPLETED, AnalysisJob::STATUS_FAILED])
            ->get();

        $count = 0;
        foreach ($jobs as $job) {
            $dir = "jobs/{$job->id}";
            if (Storage::disk('local')->exists($dir)) {
                Storage::disk('local')->deleteDirectory($dir);
                $count++;
            }
        }

        $this->info("✅ Cleaned {$count} job directories.");
        return Command::SUCCESS;
    }
}
