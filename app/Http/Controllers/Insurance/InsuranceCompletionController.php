<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\InsuranceGate;
use Illuminate\Http\Request;

class InsuranceCompletionController extends Controller
{
    public function complete(Job $job, Request $request)
    {
        abort_unless((int) $job->garage_id === (int) auth()->user()->garage_id, 403);

        // ✅ Idempotent (use real jobs.completed_at)
        if (!empty($job->completed_at)) {
            return response()->json([
                'ok' => true,
                'already_completed' => true,
                'completed_at' => (string) $job->completed_at,
            ]);
        }

        $gates = app(InsuranceGate::class)->forJob($job);

        if (($gates['completion_unlocked'] ?? false) !== true) {
            return response()->json([
                'ok' => false,
                'message' => 'Completion locked. Ensure the repair session is completed (no pending items).',
            ], 403);
        }

        $job->forceFill([
            'completed_at' => now(),
            'completed_by' => auth()->id(),

            // Optional: only keep if your app uses this vocab.
            // If you have a different status flow, remove this line.
            'status' => 'completed',
        ])->save();

        return response()->json([
            'ok' => true,
            'completed_at' => (string) $job->completed_at,
        ]);
    }
}