<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Models\ApprovalPack;
use App\Models\Job;
use App\Services\ApprovalPackGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalPackController extends Controller
{
    /**
     * Generate a new draft approval pack
     */
    public function generate(Request $request, Job $job, ApprovalPackGenerator $generator): JsonResponse
    {

        // ✅ HARD LOCK: once approval is completed, no new pack generation
        $gate = app(\App\Services\InsuranceGate::class);

        if ($gate->quoteApproved($job)) {
            return response()->json([
                'ok' => false,
                'message' => 'Approval already completed. You cannot generate a new approval pack.',
                'code' => 'pack.locked.approved',
            ], 403);
        }

        $pack = $generator->generate($job);

        return response()->json([
            'ok' => true,
            'pack' => [
                'id'           => $pack->id,
                'status'       => $pack->status,
                'version'      => $pack->version,
                'total_amount' => (string) $pack->total_amount,
                'currency'     => $pack->currency,
            ],
        ]);
    }

    /**
     * Submit pack to insurer
     */
    public function submit(Request $request, ApprovalPack $pack, ApprovalPackGenerator $generator): JsonResponse
    {
        $garageId = (int) auth()->user()->garage_id;

        // ✅ Scope pack to garage (prevents cross-tenant submit)
        abort_unless((int) $pack->garage_id === $garageId, 404);

        // ✅ HARD LOCK: once approval is completed, no re-submitting packs
        $job = Job::query()
            ->where('garage_id', $garageId)
            ->findOrFail($pack->job_id);

        $gate = app(\App\Services\InsuranceGate::class);

        if ($gate->quoteApproved($job)) {
            return response()->json([
                'ok' => false,
                'message' => 'Approval already completed. You cannot submit another pack.',
                'code' => 'pack.locked.approved',
            ], 403);
        }

        $packId = (int) $pack->id;
        $jobId  = (int) $job->id;

        return DB::transaction(function () use ($garageId, $packId, $jobId, $generator, $pack) {

            // Lock pack row (idempotency + double-click protection)
            $lockedPack = ApprovalPack::query()
                ->where('garage_id', $garageId)
                ->lockForUpdate()
                ->findOrFail($packId);

            // If already submitted/approved, just return success (idempotent)
            if (in_array($lockedPack->status, ['submitted', 'approved'], true)) {
                return response()->json([
                    'ok' => true,
                    'pack' => [
                        'id' => $lockedPack->id,
                        'status' => $lockedPack->status,
                        'submitted_at' => optional($lockedPack->submitted_at)->toDateTimeString(),
                    ],
                ]);
            }

            // If pack items already exist, do not rebuild
            $itemsCount = DB::table('approval_pack_items')
                ->where('garage_id', $garageId)
                ->where('approval_pack_id', $lockedPack->id)
                ->count();

            if ($itemsCount === 0) {

                /**
                 * ✅ DB Truth: pull latest quotation + lines
                 * Adjust table/columns ONLY if your names differ:
                 * - quotation table: job_quotations
                 * - lines table: job_quotation_lines
                 */
                $quotation = DB::table('job_quotations')
                    ->where('garage_id', $garageId)
                    ->where('job_id', $jobId)
                    // If you have statuses, keep it deterministic:
                    // ->whereIn('status', ['draft', 'submitted', 'approved'])
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                abort_unless($quotation, 422, 'No quotation found for this job.');

                $lines = DB::table('job_quotation_lines')
                    ->where('garage_id', $garageId)
                    ->where('job_id', $jobId)
                    ->where('quotation_id', $quotation->id)
                    ->orderBy('id')
                    ->get();

                abort_unless($lines->count() > 0, 422, 'Quotation has no lines.');

                $now = now();

                $rows = [];
                foreach ($lines as $line) {

                    // ---- Adjust these column names if needed ----
                    $qty       = (float) ($line->qty ?? $line->quantity ?? 1);
                    $unitPrice = (float) ($line->unit_price ?? $line->price ?? 0);
                    $total     = (float) ($line->line_total ?? $line->total ?? ($qty * $unitPrice));

                    $desc      = (string) ($line->description ?? $line->name ?? 'Line item');
                    $type      = (string) ($line->item_type ?? $line->type ?? 'line');
                    // -------------------------------------------

                    // Defensive: skip garbage/empty totals
                    if ($total <= 0) {
                        continue;
                    }

                    $rows[] = [
                        'garage_id'        => $garageId,
                        'approval_pack_id' => $lockedPack->id,

                        // approval_pack_items columns (keep these aligned to your table)
                        'item_type'    => $type,
                        'description'  => $desc,
                        'qty'          => $qty,
                        'unit_price'   => $unitPrice,
                        'line_total'   => $total,

                        // Permanent anchor (if these columns exist; if not, remove these 2 lines)
                        'source_type'  => 'job_quotation_line',
                        'source_id'    => $line->id,

                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ];
                }

                abort_unless(count($rows) > 0, 422, 'No billable quotation lines to pack.');

                // Insert once (pack is locked, so no duplicates from double click)
                DB::table('approval_pack_items')->insert($rows);

                // Optional: keep pack totals in sync immediately (if you store totals on packs)
                $sum = array_reduce($rows, fn($c, $r) => $c + (float)$r['line_total'], 0.0);

                DB::table('approval_packs')
                    ->where('garage_id', $garageId)
                    ->where('id', $lockedPack->id)
                    ->update([
                        // adjust if your column names differ
                        'total_amount' => $sum,
                        'updated_at'   => $now,
                    ]);
            }

            // ✅ Now submit using your existing generator (status/version/submitted_at logic stays centralized)
            $submittedPack = $generator->submit($lockedPack);

            return redirect()
                ->route('jobs.insurance.show', $jobId)
                ->with('success', 'Approval pack submitted.');
                
        });
    }

    public function pdf(Job $job, ApprovalPack $pack)
    {
        $garageId = (int) auth()->user()->garage_id;

        // 🔒 Hard scope checks
        abort_unless((int)$job->garage_id === $garageId, 403);
        abort_unless((int)$pack->garage_id === $garageId, 403);
        abort_unless((int)$pack->job_id === (int)$job->id, 404);

        // 🔒 Only APPROVED packs are printable (contract truth)
        abort_unless(($pack->status ?? null) === 'approved', 403, 'Only approved packs can be printed.');

        /*
        |--------------------------------------------------------------------------
        | Load Job Context (flattened row — avoids lazy loading during PDF render)
        |--------------------------------------------------------------------------
        */
        $jobRow = \DB::table('jobs as j')
            ->leftJoin('customers as c', 'c.id', '=', 'j.customer_id')
            ->leftJoin('vehicles as v', 'v.id', '=', 'j.vehicle_id')
            ->where('j.garage_id', $garageId)
            ->where('j.id', $job->id)
            ->select([
                'j.id as job_id',
                'j.job_number',
                'j.payer_type',
                'j.created_at',
                'c.name as customer_name',
                'v.registration_number as vehicle_reg',
                'v.make as vehicle_make',
                'v.model as vehicle_model',
                'v.year as vehicle_year',
            ])
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Approved Scope Items (legal snapshot)
        |--------------------------------------------------------------------------
        */
        $items = \DB::table('approval_pack_items')
            ->where('garage_id', $garageId)
            ->where('approval_pack_id', $pack->id)
            ->orderBy('id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Photos — resolve absolute filesystem path for DOMPDF
        |--------------------------------------------------------------------------
        */
        $photos = \DB::table('approval_pack_photos')
            ->where('garage_id', $garageId)
            ->where('approval_pack_id', $pack->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function ($p) {
                // Convert storage path → absolute file path so DOMPDF can read it
                $absolute = storage_path('app/' . ltrim($p->storage_path, '/'));
                $p->absolute_path = file_exists($absolute) ? $absolute : null;
                return $p;
            });

        /*
        |--------------------------------------------------------------------------
        | Approval + LPO (display only — NOT contract truth)
        |--------------------------------------------------------------------------
        */
        $approval = \DB::table('job_approvals')
            ->where('garage_id', $garageId)
            ->where('job_id', $job->id)
            ->orderByDesc('id')
            ->first();

        $lpo = \DB::table('job_insurance_details')
            ->where('job_id', $job->id)
            ->value('lpo_number');

        /*
        |--------------------------------------------------------------------------
        | Render A4 PDF
        |--------------------------------------------------------------------------
        */
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'jobs.insurance.approval.pack_pdf',
            [
                'pack'     => $pack,
                'jobRow'   => $jobRow,
                'items'    => $items,
                'photos'   => $photos,
                'approval' => $approval,
                'lpo'      => $lpo,
            ]
        )->setPaper('a4', 'portrait');

        /*
        |--------------------------------------------------------------------------
        | Deterministic Filename
        |--------------------------------------------------------------------------
        */
        $filename = sprintf(
            'Approval-Pack-%s-V%s.pdf',
            $jobRow->job_number ?: $job->id,
            $pack->version ?: 1
        );

        return $pdf->download($filename);
    }

    public function share(ApprovalPack $pack)
    {
        // No auth here. Signed URL is the protection.
        $job = \DB::table('jobs as j')
            ->leftJoin('customers as c', 'c.id', '=', 'j.customer_id')
            ->leftJoin('vehicles as v', 'v.id', '=', 'j.vehicle_id')
            ->where('j.id', $pack->job_id)
            ->select([
                'j.id as job_id',
                'j.job_number',
                'j.payer_type',
                'j.inspection_completed_at',
                'c.name as customer_name',
                'v.registration_number as vehicle_reg',
                'v.make as vehicle_make',
                'v.model as vehicle_model',
                'v.year as vehicle_year',
            ])
            ->first();

        $items = \DB::table('approval_pack_items')
            ->where('approval_pack_id', $pack->id)
            ->orderBy('id')
            ->get();

        $photos = \DB::table('approval_pack_photos')
            ->where('approval_pack_id', $pack->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('jobs.insurance.approval.pack_share', compact('pack', 'job', 'items', 'photos'));
    }
    

    public function sharePdf(ApprovalPack $pack)
    {
        // Signed URL is the protection; no auth here.

        $jobRow = \DB::table('jobs as j')
            ->leftJoin('customers as c', 'c.id', '=', 'j.customer_id')
            ->leftJoin('vehicles as v', 'v.id', '=', 'j.vehicle_id')
            ->where('j.id', $pack->job_id)
            ->select([
                'j.id as job_id',
                'j.job_number',
                'c.name as customer_name',
                'v.registration_number as vehicle_reg',
                'v.make as vehicle_make',
                'v.model as vehicle_model',
                'v.year as vehicle_year',
            ])
            ->first();

        $items = \DB::table('approval_pack_items')
            ->where('approval_pack_id', $pack->id)
            ->orderBy('id')
            ->get();

        $photos = \DB::table('approval_pack_photos')
            ->where('approval_pack_id', $pack->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'jobs.insurance.approval.pack_pdf',
            compact('pack','jobRow','items','photos')
        )->setPaper('a4');

        $jobIdOrNumber = $jobRow?->job_number ?: ($jobRow?->job_id ?: $pack->job_id);

        return $pdf->download(
            'Approval-Pack-Job-' . $jobIdOrNumber . '-V' . ($pack->version ?: 1) . '.pdf'
        );
    }

}
