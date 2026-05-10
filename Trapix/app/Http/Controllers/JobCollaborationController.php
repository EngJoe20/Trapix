<?php

namespace App\Http\Controllers;

use App\Models\AnalysisJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobCollaborationController extends Controller
{
    /**
     * Update the notes and tags for an analysis job.
     * POST /api/analysis/{jobId}/collaboration
     */
    public function update(Request $request, string $jobId)
    {
        $job = AnalysisJob::findOrFail($jobId);

        // Access control: only the owner or an admin can edit collaboration fields
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!$user->isAdmin() && $job->user_id !== $user->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
            'tags'  => 'nullable|array',
            'tags.*'=> 'string|max:50',
        ]);

        $job->update([
            'notes' => $validated['notes'] ?? null,
            'tags'  => $validated['tags'] ?? [],
        ]);

        return response()->json(['success' => true, 'notes' => $job->notes, 'tags' => $job->tags]);
    }
}
