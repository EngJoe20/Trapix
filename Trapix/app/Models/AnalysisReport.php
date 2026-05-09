<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalysisReport extends Model
{
    protected $fillable = [
        'analysis_job_id', 'disk', 'pdf_path', 'json_path',
        'risk_level', 'download_count',
    ];

    // ── Relations ──────────────────────────────────────────────────────────────

    public function job(): BelongsTo
    {
        return $this->belongsTo(AnalysisJob::class, 'analysis_job_id');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(ReportDownload::class);
    }
}
