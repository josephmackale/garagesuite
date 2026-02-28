<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobQuotation;

use App\Models\JobApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class InsuranceApprovalController extends Controller
{
    private function authorizeGarage(Job $job): void
    {
        $garageId = Auth::user()->garage_id;
        abort_unless((int) $job->garage_id === (int) $garageId, 403);
    }

    private function ensureInsurance(Job $job): void
    {
        abort_unless($job->payer_type === 'insurance', 403, 'Not an insurance job.');
    }

    /**
     * Submit approval request (after quotation is submitted).
     */
    public function submit(Request $request, Job $job, \App\Services\ApprovalPackGenerator $generator)
    {
        \Log::info('APPROVAL_POST_DEBUG', [
            'job_id' => $job->id,
            'user_id' => auth()->id(),
            'garage_id' => auth()->user()->garage_id ?? null,
            'approval_status_before' => $job->approval_status ?? null,
        ]);

        $this->authorizeGarage($job);
        $this->ensureInsurance($job);

        $garageId = Auth::user()->garage_id;

        \Log::info('INS_APPROVAL_SUBMIT_HIT', [
            'job_id' => $job->id,
            'garage_id' => $garageId,
            'user_id' => Auth::id(),
        ]);

        // ✅ Gate: inspection must be completed
        if (empty($job->inspection_completed_at)) {
            throw ValidationException::withMessages([
                'inspection' => 'Inspection must be completed before submitting for approval.',
            ]);
        }

        // ✅ Gate: quotation must exist and be submitted (your current rule)
        $quote = JobQuotation::query()
            ->where('garage_id', $garageId)
            ->where('job_id', $job->id)
            ->orderByDesc('id')
            ->first();

        if (! $quote) {
            throw ValidationException::withMessages([
                'quotation' => 'Quotation not found for this job.',
            ]);
        }

        if (($quote->status ?? 'draft') !== 'submitted') {
            throw ValidationException::withMessages([
                'quotation' => 'Quotation must be submitted before requesting approval.',
            ]);
        }

        // ✅ Phase 2: Generate + Submit immutable snapshot pack (approval_packs)
        // This will ALSO validate: quotation lines exist
        $pack = $generator->generate($job);
        $pack = $generator->submit($pack);

        // ✅ Attach Inspection Photos into approval pack (min 4 required)
        $inspection = \App\Models\JobInspection::query()
            ->where('garage_id', $garageId)
            ->where('job_id', $job->id)
            ->orderByDesc('id')
            ->first();

        if (! $inspection) {
            throw ValidationException::withMessages([
                'inspection' => 'Inspection not found for this job.',
            ]);
        }

        // Count inspection photos (attachments on the inspection entity)
        $inspectionPhotoCount = \DB::table('media_attachments')
            ->where('garage_id', $garageId)
            ->where('attachable_type', \App\Models\JobInspection::class)
            ->where('attachable_id', $inspection->id)
            ->where('label', 'inspection_photo') // keep if you want strictly inspection photos
            ->count();

        if ($inspectionPhotoCount < 4) {
            throw ValidationException::withMessages([
                'photos' => "At least 4 inspection photos are required before submitting for approval. Found: {$inspectionPhotoCount}.",
            ]);
        }

        // Insert photos into approval_pack_photos (avoid duplicates) — MySQL-safe (no ROW_NUMBER)
        $rows = \DB::table('media_attachments as ma')
            ->join('media_items as mi', 'mi.id', '=', 'ma.media_item_id')
            ->where('ma.garage_id', $garageId)
            ->where('ma.attachable_type', \App\Models\JobInspection::class)
            ->where('ma.attachable_id', $inspection->id)
            ->where('ma.label', 'inspection_photo')
            ->orderBy('ma.id')
            ->select([
                'ma.id as source_attachment_id',
                'ma.media_item_id',
                'ma.label',
                'mi.disk as storage_disk',
                'mi.path as storage_path',
                'mi.mime_type',
                'mi.size_bytes as file_size',
            ])
            ->get();

        $sort = 0;

        foreach ($rows as $r) {
            $exists = \DB::table('approval_pack_photos')
                ->where('garage_id', $garageId)
                ->where('approval_pack_id', $pack->id)
                ->where('source_attachment_id', $r->source_attachment_id)
                ->exists();

            if ($exists) {
                continue;
            }

            \DB::table('approval_pack_photos')->insert([
                'garage_id'            => $garageId,
                'approval_pack_id'     => $pack->id,
                'media_item_id'        => $r->media_item_id,
                'category'             => 'inspection',
                'label'                => $r->label,
                'storage_disk'         => $r->storage_disk,
                'storage_path'         => $r->storage_path,
                'mime_type'            => $r->mime_type,
                'file_size'            => $r->file_size,
                'sort_order'           => $sort,
                'source_attachment_id' => $r->source_attachment_id,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            $sort++;
        }


        // ✅ Keep your existing JobApproval record (Phase 1 legacy + status tracking)
        $approval = JobApproval::updateOrCreate(
            ['garage_id' => $garageId, 'job_id' => $job->id],
            [
                'quotation_id' => $quote->id,
                'status'       => 'pending',
                'submitted_at' => now(),
                'created_by'   => Auth::id(),

                // OPTIONAL (safe): only if your job_approvals table has these columns
                // 'approval_pack_id' => $pack->id,
                // 'approval_pack_version' => $pack->version,
            ]
        );

        $job->approval_status = 'pending';
        $job->approval_submitted_at = $approval->submitted_at;
        $job->approval_approved_at = null;
        $job->approval_rejected_at = null;
        $job->save();

        // Compatible with both web and fetch()
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Approval request submitted.',
                'pack' => [
                    'id' => $pack->id,
                    'status' => $pack->status,
                    'version' => $pack->version,
                    'total_amount' => (string) $pack->total_amount,
                    'currency' => $pack->currency,
                    'submitted_at' => optional($pack->submitted_at)->toDateTimeString(),
                ],
            ]);
        }
        // ✅ Sync latest submitted pack decision (so pack + approval stay consistent)
        \App\Models\ApprovalPack::query()
            ->where('garage_id', $garageId)
            ->where('job_id', $job->id)
            ->where('status', 'submitted')
            ->orderByDesc('id')
            ->limit(1)
            ->update([
                'decision_at'    => $approval->approved_at,
                'decision_notes' => trim((string) ($data['approval_notes'] ?? '')) ?: 'Approved',
                'updated_at'     => now(),
            ]);

        return back()->with('success', 'Approval request submitted.');
    }

    /**
     * Approve job quotation scope.
     */
    public function approve(Request $request, Job $job)
    {
        \Log::error('🔥 APPROVE() METHOD ENTERED', [
            'job_id'  => $job->id,
            'user_id' => auth()->id(),
            'route'   => request()->route()->getName(),
            'payload' => $request->all(),
        ]);

        $this->authorizeGarage($job);
        $this->ensureInsurance($job);

        // ✅ Define once, early
        $garageId = (int) auth()->user()->garage_id;

        // ✅ Deterministic redirect target (kills 404 from back())
        $go = fn (string $key, string $msg) =>
            redirect()->route('jobs.insurance.show', $job)->with($key, $msg);

        \Log::info('APPROVE HIT', [
            'job_id'    => $job->id,
            'user_id'   => auth()->id(),
            'garage_id' => $garageId,
        ]);

        // Validate first (outside transaction is fine)
        $data = $request->validate([
            'approved_by'     => ['required', 'string', 'max:255'],
            'approval_ref'    => ['nullable', 'string', 'max:255'],
            'lpo_number'      => ['nullable', 'string', 'max:255'],
            'approval_notes'  => ['nullable', 'string'],
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($garageId, $job, $data, $go) {

            \Log::info('APPROVE TX START', ['job_id' => $job->id, 'garage_id' => $garageId]);

            // ---------------------------------------------
            // 1) Approve the JobApproval record
            // ---------------------------------------------
            $approval = \App\Models\JobApproval::query()
                ->where('garage_id', $garageId)
                ->where('job_id', $job->id)
                ->lockForUpdate()
                ->first();

            if (! $approval) {
                \Log::warning('APPROVE TX AUTO-CREATING JobApproval', [
                    'job_id' => $job->id,
                    'garage_id' => $garageId,
                    'user_id' => auth()->id(),
                ]);

                $approval = \App\Models\JobApproval::create([
                    'garage_id'    => $garageId,
                    'job_id'       => $job->id,
                    'status'       => 'pending',
                    'submitted_at' => now(),
                    'created_by'   => auth()->id(),
                ]);
            }

            \Log::info('APPROVE TX GOT JobApproval', [
                'job_id'      => $job->id,
                'approval_id' => $approval->id,
                'status'      => $approval->status,
            ]);

            $alreadyApproved = (($approval->status ?? null) === 'approved');
            // DO NOT return here — continue to ensure pack is approved + repair exists.

            if (! $alreadyApproved) {

                $approval->fill([
                    'approved_by'    => $data['approved_by'],
                    'approval_ref'   => $data['approval_ref'] ?? null,
                    'approval_notes' => $data['approval_notes'] ?? null,
                ]);

                $approval->status           = 'approved';
                $approval->approved_at      = now();
                $approval->rejected_at      = null;
                $approval->rejection_reason = null;
                $approval->actioned_by      = auth()->id();
                $approval->save();

                // ✅ Save LPO (approval-stage capture)
                $lpo = trim((string) ($data['lpo_number'] ?? ''));
                if ($lpo !== '') {
                    \Illuminate\Support\Facades\DB::table('job_insurance_details')
                        ->where('job_id', $job->id)
                        ->update([
                            'lpo_number' => $lpo,
                            'updated_at' => now(),
                        ]);
                }

                // ✅ Shadow fields on jobs table (your UI uses these)
                $job->forceFill([
                    'approval_status'      => 'approved',
                    'approval_approved_at' => $approval->approved_at,
                    'approval_rejected_at' => null,
                ])->save();
            }

            // ---------------------------------------------
            // 2) Approve latest Approval Pack (contract truth)
            // ---------------------------------------------
            $pack = \Illuminate\Support\Facades\DB::table('approval_packs')
                ->where('garage_id', $garageId)
                ->where('job_id', $job->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            \Log::info('APPROVE TX GOT PACK', [
                'job_id'   => $job->id,
                'pack_id'  => $pack->id ?? null,
                'status'   => $pack->status ?? null,
            ]);

            if (!$pack) {
                return $go('error', 'No approval pack found for this job.');
            }

            // ---------------------------------------------
            // 2.a) ✅ Ensure pack snapshot ITEMS + PHOTOS exist (idempotent)
            // ---------------------------------------------

            // ✅ 2.a.1) Ensure pack has ITEMS snapshot (from latest submitted quotation lines)
            $packItemsExist = \DB::table('approval_pack_items')
                ->where('garage_id', $garageId)
                ->where('approval_pack_id', $pack->id)
                ->exists();

            if (! $packItemsExist) {

                $quotation = \DB::table('job_quotations')
                    ->where('garage_id', $garageId)
                    ->where('job_id', $job->id)
                    ->where('status', 'submitted')
                    ->orderByDesc('id')
                    ->first();

                if (! $quotation) {
                    return $go('error', 'Cannot approve: no submitted quotation found.');
                }

                $qLineCount = (int) \DB::table('job_quotation_lines')
                    ->where('quotation_id', $quotation->id)
                    ->count();

                if ($qLineCount < 1) {
                    return $go('error', 'Cannot approve: submitted quotation has no lines.');
                }

                \DB::table('approval_pack_items')->insertUsing(
                    [
                        'garage_id',
                        'approval_pack_id',
                        'line_type',
                        'name',
                        'description',
                        'qty',
                        'unit_price',
                        'line_total',
                        'source_quotation_line_id',
                        'created_at',
                        'updated_at',
                    ],
                    \DB::table('job_quotation_lines')
                        ->selectRaw('
                            garage_id,
                            ? as approval_pack_id,
                            type as line_type,
                            description as name,
                            NULL as description,
                            qty,
                            unit_price,
                            amount as line_total,
                            id as source_quotation_line_id,
                            NOW() as created_at,
                            NOW() as updated_at
                        ', [$pack->id])
                        ->where('quotation_id', $quotation->id)
                        ->orderBy('sort_order')
                );

                \Log::info('✅ APPROVE: backfilled approval_pack_items from quotation', [
                    'job_id' => $job->id,
                    'pack_id' => $pack->id,
                    'quotation_id' => $quotation->id,
                    'lines' => $qLineCount,
                ]);
            }

            // ✅ 2.a.2) Ensure pack has INSPECTION PHOTOS snapshot (from job-level media_attachments label=inspection)
            $packPhotosExist = \DB::table('approval_pack_photos')
                ->where('garage_id', $garageId)
                ->where('approval_pack_id', $pack->id)
                ->where('category', 'inspection')
                ->exists();

            if (! $packPhotosExist) {

                $jobAttachType = \App\Models\Job::class; // "App\Models\Job"

                $jobPhotoCount = (int) \DB::table('media_attachments')
                    ->where('garage_id', $garageId)
                    ->where('attachable_type', $jobAttachType)
                    ->where('attachable_id', $job->id)
                    ->where('label', 'inspection')
                    ->count();

                if ($jobPhotoCount > 0) {
                    // 🔥 Snapshot with storage fields populated (so share link images load)
                    \DB::table('approval_pack_photos')->insertUsing(
                        [
                            'garage_id',
                            'approval_pack_id',
                            'media_item_id',
                            'category',
                            'label',
                            'storage_disk',
                            'storage_path',
                            'mime_type',
                            'file_size',
                            'sort_order',
                            'source_attachment_id',
                            'created_at',
                            'updated_at',
                        ],
                        \DB::table('media_attachments as ma')
                            ->join('media_items as mi', 'mi.id', '=', 'ma.media_item_id')
                            ->selectRaw('
                                ma.garage_id,
                                ? as approval_pack_id,
                                ma.media_item_id,
                                "inspection" as category,
                                ma.label,
                                mi.disk as storage_disk,
                                mi.path as storage_path,
                                mi.mime_type as mime_type,
                                mi.size_bytes as file_size,
                                0 as sort_order,
                                ma.id as source_attachment_id,
                                NOW() as created_at,
                                NOW() as updated_at
                            ', [$pack->id])
                            ->where('ma.garage_id', $garageId)
                            ->where('ma.attachable_type', $jobAttachType)
                            ->where('ma.attachable_id', $job->id)
                            ->where('ma.label', 'inspection')
                            ->orderBy('ma.id')
                    );

                    \Log::info('✅ APPROVE: backfilled approval_pack_photos from job inspection attachments', [
                        'job_id' => $job->id,
                        'pack_id' => $pack->id,
                        'photos' => $jobPhotoCount,
                    ]);
                } else {
                    \Log::warning('⚠️ APPROVE: no job-level inspection photos found to snapshot', [
                        'job_id' => $job->id,
                        'pack_id' => $pack->id,
                        'attachable_type' => $jobAttachType,
                        'label' => 'inspection',
                    ]);
                }
            }

            \Log::info('APPROVING PACK', [
                'pack_id' => $pack->id,
                'status'  => $pack->status ?? null,
                'job_id'  => $job->id,
            ]);

            // If pack isn't approved yet, approve it now (atomic + deterministic)
            if (($pack->status ?? null) !== 'approved') {

                $items = \Illuminate\Support\Facades\DB::table('approval_pack_items')
                    ->where('garage_id', $garageId)
                    ->where('approval_pack_id', $pack->id)
                    ->orderBy('id')
                    ->get();

                if ($items->count() < 1) {
                    return $go('error', 'Cannot approve: approval pack has no items.');
                }

                $totalAmount = 0.0;
                $snapshotParts = [];

                foreach ($items as $it) {
                    $qty       = (float) ($it->qty ?? 0);
                    $unit      = (float) ($it->unit_price ?? 0);
                    $lineTotal = (float) ($it->line_total ?? ($qty * $unit));

                    $totalAmount += $lineTotal;

                    $snapshotParts[] = implode('|', [
                        (string) ($it->line_type ?? ''),
                        (string) ($it->name ?? ''),
                        (string) ($it->description ?? ''),
                        number_format((float) ($it->qty ?? 0), 2, '.', ''),
                        number_format((float) ($it->unit_price ?? 0), 2, '.', ''),
                        number_format((float) ($it->line_total ?? $lineTotal), 2, '.', ''),
                        (string) ($it->tax_code ?? ''),
                        number_format((float) ($it->tax_amount ?? 0), 2, '.', ''),
                    ]);
                }

                $lockedHash = hash('sha256', implode("\n", $snapshotParts));

                $now = now();

                $affected = \DB::table('approval_packs')
                    ->where('garage_id', $garageId)
                    ->where('id', $pack->id)
                    ->update([
                        'status'               => 'approved',
                        'decision_at'          => $now,
                        'approval_approved_at' => $now,                           // ✅ your schema has this
                        'approved_by'          => auth()->id(),                    // ✅ bigint user id
                        'decision_notes'       => (string) ($data['approval_notes'] ?? ''), // ✅ store notes here
                        'locked_hash'          => $lockedHash,
                        'total_amount'         => number_format($totalAmount, 2, '.', ''),
                        'updated_at'           => $now,
                    ]);

                \Log::info('APPROVE PACK UPDATE RESULT', [
                    'job_id'    => $job->id,
                    'pack_id'   => $pack->id,
                    'affected'  => $affected,
                ]);

                // ✅ Reload + assert truth (so UI gates are deterministic)
                $pack = \DB::table('approval_packs')
                    ->where('garage_id', $garageId)
                    ->where('id', $pack->id)
                    ->first();

                \Log::info('APPROVE PACK AFTER RELOAD', [
                    'job_id'  => $job->id,
                    'pack_id' => $pack->id ?? null,
                    'status'  => $pack->status ?? null,
                ]);

                if (($pack->status ?? null) !== 'approved') {
                    \Log::error('❌ PACK DID NOT APPROVE (ABORT)', [
                        'job_id'  => $job->id,
                        'pack_id' => $pack->id ?? null,
                        'status'  => $pack->status ?? null,
                    ]);
                    return $go('error', 'Approval failed: pack status did not update. Check logs.');
                }

                // Lock quotation immediately
                if (!empty($pack->quotation_id)) {
                    \Illuminate\Support\Facades\DB::table('job_quotations')
                        ->where('garage_id', $garageId)
                        ->where('id', $pack->quotation_id)
                        ->update([
                            'locked_at'  => now(),
                            'updated_at' => now(),
                        ]);
                } else {
                    \Illuminate\Support\Facades\DB::table('job_quotations')
                        ->where('garage_id', $garageId)
                        ->where('job_id', $job->id)
                        ->orderByDesc('id')
                        ->limit(1)
                        ->update([
                            'locked_at'  => now(),
                            'updated_at' => now(),
                        ]);
                }

                // Reload pack
                $pack = \Illuminate\Support\Facades\DB::table('approval_packs')
                    ->where('garage_id', $garageId)
                    ->where('id', $pack->id)
                    ->first();
            }

            // ---------------------------------------------
            // 3) AUTO-CREATE REPAIR SESSION (pack approved)
            //    ✅ also self-heal if repair exists but has 0 items
            // ---------------------------------------------
            if (($pack->status ?? null) === 'approved') {

                $repairId = \Illuminate\Support\Facades\DB::table('job_repairs')
                    ->where('garage_id', $garageId)
                    ->where('job_id', $job->id)
                    ->where('approval_pack_id', $pack->id)
                    ->value('id');

                // ✅ Create repair session if missing
                if (! $repairId) {
                    $repairId = \Illuminate\Support\Facades\DB::table('job_repairs')->insertGetId([
                        'garage_id'        => $job->garage_id,
                        'job_id'           => $job->id,
                        'approval_pack_id' => $pack->id,
                        'status'           => 'in_progress',
                        'started_by'       => auth()->id(),
                        'started_at'       => now(),
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }

                // ✅ Self-heal: if repair exists but has 0 items (common after earlier empty-pack approvals), clone now.
                $repairItemCount = (int) \Illuminate\Support\Facades\DB::table('job_repair_items')
                    ->where('garage_id', $garageId)
                    ->where('job_repair_id', $repairId)
                    ->count();

                if ($repairItemCount < 1) {

                    // ✅ Safety: don't insert empty
                    $packItemCount = (int) \Illuminate\Support\Facades\DB::table('approval_pack_items')
                        ->where('garage_id', $garageId)
                        ->where('approval_pack_id', $pack->id)
                        ->count();

                    if ($packItemCount < 1) {
                        \Log::error('❌ REPAIR CLONE ABORT: approval pack has 0 items', [
                            'job_id'   => $job->id,
                            'pack_id'  => $pack->id,
                            'repair_id'=> $repairId,
                        ]);
                    } else {

                        \Illuminate\Support\Facades\DB::table('job_repair_items')->insertUsing(
                            [
                                'garage_id','job_repair_id','approval_pack_item_id',
                                'line_type','name','description',
                                'approved_qty','approved_unit_price','approved_line_total',
                                'execution_status','created_at','updated_at'
                            ],
                            \Illuminate\Support\Facades\DB::table('approval_pack_items')
                                ->selectRaw(
                                    '? as garage_id, ? as job_repair_id, id,
                                    line_type, name, description,
                                    qty, unit_price, line_total,
                                    "pending", NOW(), NOW()',
                                    [$job->garage_id, $repairId]
                                )
                                ->where('garage_id', $garageId)
                                ->where('approval_pack_id', $pack->id)
                        );

                        \Log::info('✅ REPAIR ITEMS CLONED FROM APPROVAL PACK', [
                            'job_id'    => $job->id,
                            'pack_id'   => $pack->id,
                            'repair_id' => $repairId,
                            'items'     => $packItemCount,
                        ]);
                    }
                }
            }

            \Log::info('✅ APPROVE DONE', ['job_id' => $job->id, 'garage_id' => $garageId]);

            return $go('success', 'Approval marked as APPROVED.');
        });
    }


    /**
     * Reject quotation scope.
     */
    public function reject(Request $request, Job $job)
    {
        $this->authorizeGarage($job);
        $this->ensureInsurance($job);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        $garageId = Auth::user()->garage_id;

        $approval = JobApproval::firstOrFailWhere([
            ['garage_id', '=', $garageId],
            ['job_id', '=', $job->id],
        ]);

        if ($approval->status === 'approved') {
            throw ValidationException::withMessages([
                'approval' => 'Cannot reject after approval. (Phase 1 rule)',
            ]);
        }

        $approval->status = 'rejected';
        $approval->rejection_reason = $data['rejection_reason'];
        $approval->rejected_at = now();
        $approval->approved_at = null;
        $approval->actioned_by = Auth::id();
        $approval->save();

        $job->approval_status = 'rejected';
        $job->approval_rejected_at = $approval->rejected_at;
        $job->approval_approved_at = null;
        $job->save();

        // ✅ Sync latest submitted pack decision (so pack + approval stay consistent)        
        return back()->with('success', 'Approval marked as REJECTED.');
    }
}
