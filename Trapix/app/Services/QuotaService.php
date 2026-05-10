<?php

namespace App\Services;

use App\Models\User;
use App\Models\GuestQuotaToken;
use Illuminate\Http\Request;

/**
 * QuotaService
 * ------------
 * Centralizes all quota / rate-limit decisions.
 *
 * Guest quota  → tracked in guest_quota_tokens table (3 analyses total)
 * Free user    → tracked in users.monthly_analysis_used (10/month default)
 * Paid user    → tracked per their plan's monthly_analyses field (0 = unlimited)
 */
class QuotaService
{
    const GUEST_MAX = 3;

    // ── Guest Quota ─────────────────────────────────────────────────────────────

    /**
     * Check if a guest token still has analyses remaining.
     */
    public function guestCanAnalyze(string $token): bool
    {
        $record = GuestQuotaToken::firstOrCreate(
            ['token' => $token],
            ['ip_address' => request()->ip(), 'analysis_count' => 0]
        );

        return $record->analysis_count < self::GUEST_MAX;
    }

    /**
     * Increment the guest analysis counter.
     */
    public function incrementGuest(string $token): void
    {
        GuestQuotaToken::where('token', $token)->increment('analysis_count');
    }

    /**
     * Remaining analyses for a guest token.
     */
    public function guestRemaining(string $token): int
    {
        $count = GuestQuotaToken::where('token', $token)->value('analysis_count') ?? 0;
        return max(0, self::GUEST_MAX - $count);
    }

    // ── Authenticated User Quota ────────────────────────────────────────────────

    public function userCanAnalyze(User $user): bool
    {
        return $user->canAnalyze();
    }

    public function incrementUser(User $user): void
    {
        $user->incrementAnalysisUsage();
    }

    // ── Unified check (handles both guest and user) ─────────────────────────────

    /**
     * Returns [allowed: bool, reason: string|null, upgrade: bool, require_login: bool, redirect_to: string|null]
     */
    public function check(?User $user, ?string $guestToken): array
    {
        if ($user) {
            if (! $this->userCanAnalyze($user)) {
                $plan = $user->effectivePlan();
                $limit = $plan?->monthly_analyses ?? 10;
                return [
                    'allowed'       => false,
                    'reason'        => "Monthly limit of {$limit} analyses reached. Please upgrade your plan.",
                    'upgrade'       => true,
                    'require_login' => false,
                    'redirect_to'   => '/pricing',
                ];
            }
            return ['allowed' => true, 'reason' => null, 'upgrade' => false, 'require_login' => false, 'redirect_to' => null];
        }

        // Guest user
        if (! $guestToken) {
            return ['allowed' => false, 'reason' => 'Invalid session token.', 'upgrade' => false, 'require_login' => true, 'redirect_to' => '/login'];
        }

        if (! $this->guestCanAnalyze($guestToken)) {
            return [
                'allowed'       => false,
                'reason'        => 'You\'ve used all ' . self::GUEST_MAX . ' free scans. Create a free account to get 10 scans/month.',
                'upgrade'       => false,
                'require_login' => true,
                'redirect_to'   => '/register',
            ];
        }

        return ['allowed' => true, 'reason' => null, 'upgrade' => false, 'require_login' => false, 'redirect_to' => null];
    }
}
