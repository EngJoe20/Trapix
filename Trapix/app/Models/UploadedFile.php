<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadedFile extends Model
{
    protected $fillable = [
        'analysis_job_id', 'original_name', 'stored_name',
        'disk', 'path', 'mime_type', 'size_bytes', 'sha256',
    ];

    // ── Relations ──────────────────────────────────────────────────────────────

    public function job(): BelongsTo
    {
        return $this->belongsTo(AnalysisJob::class, 'analysis_job_id');
    }
}
