<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AnalysisJob extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id', 'guest_token', 'status', 'input_type',
        'file_count', 'vt_api_key', 'skip_vt', 'options',
        'result', 'error_message', 'python_exit_code',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'options'      => 'array',
        'result'       => 'array',
        'skip_vt'      => 'boolean',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ── Status constants ───────────────────────────────────────────────────────

    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_FAILED     = 'failed';

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopePending($query)   { return $query->where('status', self::STATUS_PENDING); }
    public function scopeCompleted($query) { return $query->where('status', self::STATUS_COMPLETED); }
    public function scopeFailed($query)    { return $query->where('status', self::STATUS_FAILED); }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isCompleted(): bool { return $this->status === self::STATUS_COMPLETED; }
    public function isFailed(): bool    { return $this->status === self::STATUS_FAILED; }
    public function isPending(): bool   { return $this->status === self::STATUS_PENDING; }

    public function durationSeconds(): ?float
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInSeconds($this->completed_at);
        }
        return null;
    }

    // ── Relations ──────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(UploadedFile::class);
    }

    public function report(): HasOne
    {
        return $this->hasOne(AnalysisReport::class);
    }

    public function aiResponse(): HasOne
    {
        return $this->hasOne(AiResponse::class);
    }
}
