<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
        'plan_id', 'monthly_analysis_used', 'quota_reset_date',
        'role', 'avatar', 'api_token',
    ];

    protected $hidden = [
        'password', 'remember_token', 'api_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'quota_reset_date'    => 'date',
            'password'            => 'hashed',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────────

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->subscriptions()->active()->with('plan')->latest()->first();
    }

    public function analysisJobs(): HasMany
    {
        return $this->hasMany(AnalysisJob::class);
    }

    public function aiIntegrations(): HasMany
    {
        return $this->hasMany(AiIntegration::class);
    }

    // ── Quota Helpers ──────────────────────────────────────────────────────────

    /**
     * The effective plan — from active subscription or the plan_id column.
     */
    public function effectivePlan(): ?Plan
    {
        return $this->activeSubscription()?->plan ?? $this->plan;
    }

    /**
     * Check whether the user has analyses remaining this month.
     */
    public function canAnalyze(): bool
    {
        $plan = $this->effectivePlan();

        if (! $plan) {
            // Default free-tier fallback: 10 analyses/month
            return $this->monthly_analysis_used < 10;
        }

        if ($plan->hasUnlimitedAnalyses()) {
            return true;
        }

        $this->resetQuotaIfNeeded();

        return $this->monthly_analysis_used < $plan->monthly_analyses;
    }

    /**
     * Reset monthly quota if a new month has begun.
     */
    public function resetQuotaIfNeeded(): void
    {
        if (! $this->quota_reset_date || now()->gte($this->quota_reset_date)) {
            $this->update([
                'monthly_analysis_used' => 0,
                'quota_reset_date'      => now()->addMonthNoOverflow()->startOfDay(),
            ]);
        }
    }

    /**
     * Increment monthly usage count.
     */
    public function incrementAnalysisUsage(): void
    {
        $this->increment('monthly_analysis_used');
    }

    /**
     * How many analyses remain this month (null = unlimited).
     */
    public function remainingAnalyses(): ?int
    {
        $plan = $this->effectivePlan();

        if (! $plan || $plan->hasUnlimitedAnalyses()) {
            return null;
        }

        $this->resetQuotaIfNeeded();

        return max(0, $plan->monthly_analyses - $this->monthly_analysis_used);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
