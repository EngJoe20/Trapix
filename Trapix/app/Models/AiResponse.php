<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiResponse extends Model
{
    protected $fillable = [
        'analysis_job_id', 'provider', 'model',
        'insights', 'pdf_path', 'tokens_used',
        'cost_microcents', 'status', 'error',
    ];

    protected $casts = [
        'insights' => 'array',
    ];

    // ── Relations ──────────────────────────────────────────────────────────────

    public function job(): BelongsTo
    {
        return $this->belongsTo(AnalysisJob::class, 'analysis_job_id');
    }
}
