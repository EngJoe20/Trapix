<?php

namespace App\Http\Controllers;

use App\Models\AnalysisJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ResultController
 * ----------------
 * Serves the full analysis result page for a completed job.
 * Access control matches AnalysisController::authorizeJobAccess().
 */
class ResultController extends Controller
{
    public function show(Request $request, string $jobId)
    {
        $job = AnalysisJob::with(['files', 'report', 'aiResponse'])->findOrFail($jobId);

        $this->authorizeAccess($job, $request);

        return view('result', compact('job'));
    }

    private function authorizeAccess(AnalysisJob $job, Request $request): void
    {
        $user = Auth::user();

        if ($user?->isAdmin()) return;

        if ($user && $job->user_id === $user->id) return;

        if (! $user) {
            $token = $request->input('guest_token')
                ?? $request->session()->get('guest_token');
            if ($job->guest_token && $job->guest_token === $token) return;
        }

        abort(403, 'Access denied.');
    }
}
