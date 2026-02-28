<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use App\Services\InsuranceGate;
use Illuminate\Support\Facades\DB;

class InsuranceCardsController extends Controller
{
    /**
     * Phase 2: server rehydrate endpoint for quotation card
     * Returns fresh server-rendered HTML for swapping into the page.
     */
    public function quotationCard(Request $request, Job $job)
    {
        abort_unless(auth()->check(), 401);
        abort_unless((int) $job->garage_id === (int) auth()->user()->garage_id, 403);

        $gates = app(\App\Services\InsuranceGate::class)->forJob($job);

        $quotation = \App\Models\JobQuotation::query()
            ->where('garage_id', $job->garage_id)
            ->where('job_id', $job->id)
            ->latest('id')
            ->first();

        // ✅ ADD THIS
        $inspection = \App\Models\JobInspection::query()
            ->where('garage_id', $job->garage_id)
            ->where('job_id', $job->id)
            ->latest('id')
            ->first();

        $html = view('jobs.insurance.quotation.card', [
            'job'        => $job,
            'gates'      => $gates,
            'quotation'  => $quotation,
            'inspection' => $inspection, // ← this fixes the crash
        ])->render();

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
    /**
     * Phase 2: server rehydrate endpoint for approval card
     * Returns fresh server-rendered HTML for swapping into the page.
     */
    public function approvalCard(Request $request, Job $job)
    {
        if (!auth()->check()) {
            // For fetch() calls we must NOT redirect — return 401 JSON
            return response('Unauthorized', 401);
        }

        abort_unless((int)$job->garage_id === (int)auth()->user()->garage_id, 403);

        $gates = app(InsuranceGate::class)->forJob($job);

        $pack = DB::table('approval_packs')
            ->where('job_id', $job->id)
            ->orderByDesc('id')
            ->first();

        // ⚠️ IMPORTANT: render PARTIAL only (no layout)
        return response()->view('jobs.insurance.approval.panel', [
            'job'   => $job,
            'gates' => $gates,
            'pack'  => $pack,
            'packShareUrl' => null,
        ]);
    }

    public function repairCard(Request $request, Job $job)
    {
        \Log::info('HIT repairCard', [
            'auth' => auth()->check(),
            'user_id' => auth()->id(),
            'user_garage_id' => optional(auth()->user())->garage_id,
            'job_id' => $job->id,
            'job_garage_id' => $job->garage_id,
            'path' => request()->path(),
        ]);

        if (!auth()->check()) {
            return response('Unauthorized', 401);
        }

        abort_unless((int)$job->garage_id === (int)auth()->user()->garage_id, 403);

        $gates = app(\App\Services\InsuranceGate::class)->forJob($job);

        // 🔥 Load latest approval pack
        $pack = \Illuminate\Support\Facades\DB::table('approval_packs')
            ->where('garage_id', $job->garage_id)
            ->where('job_id', $job->id)
            ->orderByDesc('id')
            ->first();

        // 🔥 Load latest repair session (THIS was missing)
        $repairSession = \App\Models\JobRepair::query()
            ->where('garage_id', $job->garage_id)
            ->where('job_id', $job->id)
            ->latest('id')
            ->first();

        $repairItems = collect();

        if ($repairSession) {
            $repairItems = \App\Models\JobRepairItem::query()
                ->where('garage_id', $job->garage_id)
                ->where('job_repair_id', $repairSession->id)
                ->orderBy('id')
                ->get();
        }

        // Optional: load technicians if blade uses them
        $technicians = \App\Models\User::query()
            ->where('garage_id', $job->garage_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->view('jobs.insurance.repair.card', [
            'job'           => $job,
            'gates'         => $gates,
            'pack'          => $pack,
            'repairSession' => $repairSession,
            'repairItems'   => $repairItems,
            'technicians'   => $technicians,
        ]);
    }
}
