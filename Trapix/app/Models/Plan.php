<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description',
        'monthly_analyses', 'max_upload_bytes',
        'report_downloads_unlimited', 'report_download_limit',
        'ai_access', 'priority_processing',
        'price_monthly_cents', 'is_active',
    ];

    protected $casts = [
        'report_downloads_unlimited' => 'boolean',
        'ai_access'                  => 'boolean',
        'priority_processing'        => 'boolean',
        'is_active'                  => 'boolean',
    ];

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isFree(): bool
    {
        return $this->price_monthly_cents === 0;
    }

    public function hasUnlimitedAnalyses(): bool
    {
        return $this->monthly_analyses === 0;
    }

    public function maxUploadMb(): float
    {
        return round($this->max_upload_bytes / 1048576, 1);
    }

    // ── Relations ──────────────────────────────────────────────────────────────

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
