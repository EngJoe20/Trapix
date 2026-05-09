<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportDownload extends Model
{
    protected $fillable = [
        'analysis_report_id', 'user_id', 'ip_address', 'user_agent',
    ];

    // ── Relations ──────────────────────────────────────────────────────────────

    public function report(): BelongsTo
    {
        return $this->belongsTo(AnalysisReport::class, 'analysis_report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
