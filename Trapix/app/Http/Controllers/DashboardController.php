<?php

namespace App\Http\Controllers;

use App\Models\AnalysisJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * DashboardController
 * -------------------
 * Returns the user's analysis history, quota status, and subscription info.
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user()->load('plan');

        // ── Quota info ─────────────────────────────────────────────────────────
        $plan      = $user->effectivePlan() ?? \App\Models\Plan::where('slug', 'free')->first();
        $remaining = $user->remainingAnalyses();

        // ── Recent analysis history (paginated) ────────────────────────────────
        $jobs = AnalysisJob::where('user_id', $user->id)
            ->with(['report', 'files'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('dashboard', compact('user', 'plan', 'remaining', 'jobs'));
    }

    public function __construct(
        private \App\Services\QuotaService $quotaService
    ) {}

    /**
     * GET /api/dashboard/quota
     * JSON quota info for frontend.
     */
    public function quota(Request $request)
    {
        $user = Auth::user();
        
        if ($user) {
            $plan = $user->effectivePlan() ?? \App\Models\Plan::where('slug', 'free')->first();
            return response()->json([
                'type'               => 'user',
                'plan_name'          => $plan?->name ?? 'Free',
                'limit'              => $plan?->monthly_analyses ?? 10,
                'used'               => $user->monthly_analysis_used,
                'remaining'          => $user->remainingAnalyses(),
                'unlimited'          => $plan?->hasUnlimitedAnalyses() ?? false,
                'max_upload_mb'      => $plan?->maxUploadMb() ?? 50,
            ]);
        }

        // Guest logic
        $guestToken = $request->input('guest_token') ?? $request->session()->get('guest_token');
        if ($guestToken) {
            $remaining = $this->quotaService->guestRemaining($guestToken);
            $used = \App\Services\QuotaService::GUEST_MAX - $remaining;
            return response()->json([
                'type'      => 'guest',
                'plan_name' => 'Guest',
                'limit'     => \App\Services\QuotaService::GUEST_MAX,
                'used'      => $used,
                'remaining' => $remaining,
                'unlimited' => false,
                'max_upload_mb' => 10,
            ]);
        }

        return response()->json(['error' => 'No session or user found'], 401);
    }

    /**
     * GET /dashboard/history  (API JSON for history page AJAX)
     */
    public function history(Request $request)
    {
        $user = Auth::user();

        $query = AnalysisJob::where('user_id', $user->id)
            ->with(['report'])
            ->orderByDesc('created_at');

        // ── Search ─────────────────────────────────────────────────────────────
        if ($search = $request->get('search')) {
            $query->whereHas('files', function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%");
            });
        }

        // ── Status filter ──────────────────────────────────────────────────────
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // ── Risk level filter ──────────────────────────────────────────────────
        if ($risk = $request->get('risk')) {
            $query->whereHas('report', function ($q) use ($risk) {
                $q->where('risk_level', $risk);
            });
        }

        $jobs = $query->paginate(20);

        return response()->json($jobs);
    }
}
