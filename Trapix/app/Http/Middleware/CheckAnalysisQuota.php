<?php

namespace App\Http\Middleware;

use App\Services\QuotaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckAnalysisQuota
 * ------------------
 * Middleware that checks the quota BEFORE the controller even runs.
 * Returns a 429 JSON error immediately if the user/guest is over their limit.
 *
 * Usage in routes:
 *   Route::middleware(['check.quota'])->post('/api/analysis', ...);
 */
class CheckAnalysisQuota
{
    public function __construct(private QuotaService $quota) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user       = $request->user();
        $guestToken = $request->input('guest_token')
                    ?? $request->session()->get('guest_token');

        $result = $this->quota->check($user, $guestToken);

        if (! $result['allowed']) {
            return response()->json([
                'error'        => $result['reason'],
                'upgrade'      => $result['upgrade'] ?? false,
                'pricing_url'  => route('pricing'),
            ], 429);
        }

        return $next($request);
    }
}
