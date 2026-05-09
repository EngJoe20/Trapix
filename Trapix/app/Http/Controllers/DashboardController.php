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
        $plan      = $user->effectivePlan();
        $remaining = $user->remainingAnalyses();

        // ── Recent analysis history (paginated) ────────────────────────────────
        $jobs = AnalysisJob::where('user_id', $user->id)
            ->with(['report', 'files'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('dashboard', compact('user', 'plan', 'remaining', 'jobs'));
    }

    /**
     * GET /api/dashboard/quota
     * JSON quota info for frontend.
     */
    public function quota(Request $request)
    {
        $user = Auth::user();
        $plan = $user->effectivePlan();

        return response()->json([
            'plan_name'          => $plan?->name ?? 'Free',
            'monthly_analyses'   => $plan?->monthly_analyses ?? 10,
            'used_this_month'    => $user->monthly_analysis_used,
            'remaining'          => $user->remainingAnalyses(),
            'unlimited'          => $plan?->hasUnlimitedAnalyses() ?? false,
            'quota_reset_date'   => $user->quota_reset_date?->toDateString(),
            'max_upload_mb'      => $plan?->maxUploadMb() ?? 50,
            'ai_access'          => $plan?->ai_access ?? false,
            'report_dl_unlimited'=> $plan?->report_downloads_unlimited ?? false,
        ]);
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
