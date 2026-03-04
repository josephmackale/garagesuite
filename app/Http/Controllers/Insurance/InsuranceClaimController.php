<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\InsuranceGate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\JobInsuranceDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\ApprovalPack;
use App\Http\Controllers\InvoiceController;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\URL;
use App\Models\MediaItem;
use App\Models\InsuranceClaim;

class InsuranceClaimController extends Controller
{
    public function show(Job $job, InsuranceGate $gate)
    {
        $gates = $gate->forJob($job);

        // Load core relationships safely
        $job->loadMissing([
            'vehicle',
            'customer',
            'insuranceDetail.insurer',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Latest Invoice
        |--------------------------------------------------------------------------
        */
        $invoice = \App\Models\Invoice::query()
            ->where('garage_id', (int) $job->garage_id)
            ->where('job_id', $job->id)
            ->latest('id')
            ->first();

        $details = $job->insuranceDetail;

        /*
        |--------------------------------------------------------------------------
        | Latest APPROVED Approval Pack (Claim must use approved scope only)
        |--------------------------------------------------------------------------
        */
        $approvalPack = \DB::table('approval_packs')
            ->where('garage_id', (int) $job->garage_id)
            ->where('job_id', $job->id)
            ->where('status', 'approved')
            ->orderByDesc('id')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Approval Share Links (Signed)
        |--------------------------------------------------------------------------
        */
        $approvalShareUrl = null;
        $approvalPdfUrl   = null;

        if ($approvalPack) {
            $approvalShareUrl = \URL::signedRoute('insurance.approval-packs.share', [
                'pack' => $approvalPack->id,
            ]);

            $approvalPdfUrl = \URL::signedRoute('insurance.approval-packs.pdf.share', [
                'pack' => $approvalPack->id,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Claim Pack Share Link (7 day expiry)
        |--------------------------------------------------------------------------
        */
        $claimShareUrl = \URL::temporarySignedRoute(
            'insurance.claim-packs.share',
            now()->addDays(7),
            ['job' => $job->id]
        );

        /*
        |--------------------------------------------------------------------------
        | Claim Pack Photos (✅ Single Source of Truth)
        |--------------------------------------------------------------------------
        | Both INSPECTION and COMPLETION come from media_attachments (Job scoped).
        | Labels:
        |   - inspection
        |   - completion
        |
        | Blade expects "rows" with storage_disk/storage_path; we shape rows to match.
        */
        $inspectionRows = collect();
        $completionRows = collect();
        $inspectionPhotoCount = 0;
        $completionPhotoCount = 0;

        try {
            if (\Schema::hasTable('media_attachments') && \Schema::hasTable('media_items')) {

                $base = \DB::table('media_attachments as ma')
                    ->join('media_items as mi', 'mi.id', '=', 'ma.media_item_id')
                    ->where('ma.garage_id', (int) $job->garage_id)
                    ->where('ma.attachable_type', \App\Models\Job::class)
                    ->where('ma.attachable_id', (int) $job->id)
                    // Dedup: if the same media_item gets attached again, keep latest
                    ->whereIn('ma.id', function ($q) use ($job) {
                        $q->from('media_attachments')
                            ->selectRaw('MAX(id)')
                            ->where('garage_id', (int) $job->garage_id)
                            ->where('attachable_type', \App\Models\Job::class)
                            ->where('attachable_id', (int) $job->id)
                            ->groupBy('media_item_id', 'label');
                    })
                    ->select([
                        \DB::raw('ma.id as id'),
                        'ma.media_item_id',
                        \DB::raw("ma.label as category"),      // inspection|completion
                        \DB::raw("ma.label as label"),
                        \DB::raw('mi.disk as storage_disk'),
                        \DB::raw('mi.path as storage_path'),
                        'mi.mime_type',
                        \DB::raw('ma.id as sort_order'),
                        \DB::raw('ma.created_at as created_at'),
                    ])
                    ->orderBy('ma.id');

                $inspectionRows = (clone $base)->where('ma.label', 'inspection')->get();
                $completionRows = (clone $base)->where('ma.label', 'completion')->get();

                $inspectionPhotoCount = (int) $inspectionRows->count();
                $completionPhotoCount = (int) $completionRows->count();
            }
        } catch (\Throwable $e) {
            $inspectionRows = collect();
            $completionRows = collect();
            $inspectionPhotoCount = 0;
            $completionPhotoCount = 0;
        }

        // Back-compat alias (if blade references this)
        $completionPhotos = $completionRows;

        /*
        |--------------------------------------------------------------------------
        | Return View
        |--------------------------------------------------------------------------
        */
        return view('jobs.insurance.claim.show', compact(
            'job',
            'gates',
            'invoice',
            'details',
            'approvalPack',
            'approvalShareUrl',
            'approvalPdfUrl',
            'claimShareUrl',
            'inspectionRows',
            'inspectionPhotoCount',
            'completionRows',
            'completionPhotos',
            'completionPhotoCount'
        ));
    }

    public function card(Job $job, InsuranceGate $gate)
    {
        $gates = $gate->forJob($job);

        $details = DB::table('job_insurance_details')
            ->where('garage_id', (int) $job->garage_id)
            ->where('job_id', $job->id)
            ->first();

        return view('jobs.insurance.claim.card', compact('job', 'gates', 'details'));
    }

    protected function claimArchiveDir(Job $job): string
    {
        return "garages/{$job->garage_id}/insurance/claims/job-{$job->id}";
    }

    protected function safePlate(Job $job): string
    {
        $plate = (string) optional($job->vehicle)->plate_number;
        $plate = trim($plate);

        // Remove everything except letters and numbers
        $plate = preg_replace('/[^A-Za-z0-9]/', '', $plate) ?? '';

        $plate = strtoupper($plate);

        return $plate !== '' ? $plate : 'PLATE-NA';
    }

    /**
     * ✅ Reusable generator:
     * Builds the claim pack ZIP into storage/app/tmp/... and returns:
     * [absoluteZipPath, zipFileName, relativeTmpPath]
     */
    protected function buildClaimPackZip(Job $job, InsuranceGate $gate): array
    {
        $garageId = (int) $job->garage_id;
        $gates = $gate->forJob($job);

        // Load summary dependencies
        $job->loadMissing(['vehicle', 'customer', 'insuranceDetail.insurer']);

        // Latest invoice (Eloquent)
        $invoice = Invoice::query()
            ->where('garage_id', (int) $job->garage_id)
            ->where('job_id', $job->id)
            ->latest('id')
            ->first();

        // Latest approved pack
        $approvalPack = ApprovalPack::query()
            ->where('garage_id', (int) $job->garage_id)
            ->where('job_id', $job->id)
            ->where('status', 'approved')
            ->latest('id')
            ->with(['items'])
            ->first();

        // -----------------------------
        // Create temp folder + ZIP path
        // -----------------------------
        $date = now()->format('Ymd_His');
        $plate = $this->safePlate($job);
        $zipName = "claim_pack_{$plate}_job_{$job->id}_{$date}.zip";

        $tmpDir = "garages/{$job->garage_id}/insurance/claims/job-{$job->id}";
        Storage::disk('local')->makeDirectory($tmpDir);

        // Browsershot needs real OS directory
        $absTmpDir = storage_path("app/{$tmpDir}");
        if (!is_dir($absTmpDir)) {
            @mkdir($absTmpDir, 0755, true);
        }

        $zipRelPath = "{$tmpDir}/{$zipName}";
        $zipAbsPath = storage_path("app/{$zipRelPath}");

        // -----------------------------
        // Build Summary PDF (DomPDF)
        // -----------------------------
        $summaryPdf = Pdf::loadView('jobs.insurance.claim.pack.summary', [
            'job' => $job,
            'invoice' => $invoice,
            'approvalPack' => $approvalPack,
            'gates' => $gates,
        ])->setPaper('a4');

        // -----------------------------
        // Build Invoice PDF (Browsershot)
        // -----------------------------
        $invoicePdfAbs = null;

        if ($invoice) {
            $invoiceCtrl = app(InvoiceController::class);
            $html = $invoiceCtrl->renderInvoicePdfHtml($invoice);

            $invoicePdfAbs = "{$absTmpDir}/invoice-{$invoice->id}.pdf";

            if (file_exists($invoicePdfAbs)) {
                @unlink($invoicePdfAbs);
            }

            Browsershot::html($html)
                ->setNodeBinary('/home/iwebgarage/.nvm/versions/node/v20.19.6/bin/node')
                ->setNpmBinary('/home/iwebgarage/.nvm/versions/node/v20.19.6/bin/npm')
                ->setChromePath('/usr/bin/google-chrome-stable')
                ->noSandbox()
                ->format('A4')

                // ✅ critical for full-width A4 behavior
                ->emulateMedia('print')
                ->windowSize(1200, 1800)

                ->margins(12, 12, 12, 12)   // keep if you want margins
                ->showBackground()
                ->scale(1)                  // optional but good
                ->waitUntilNetworkIdle()    // optional but good (if CSS/images load)
                ->timeout(120)
                ->savePdf($invoicePdfAbs);
        }

        // -----------------------------
        // Collect photos (✅ Single Source of Truth: media_attachments)
        // -----------------------------
        $inspectionRows = collect();
        $completionRows = collect();

        try {
            if (\Schema::hasTable('media_attachments') && \Schema::hasTable('media_items')) {

                $base = DB::table('media_attachments as ma')
                    ->join('media_items as mi', 'mi.id', '=', 'ma.media_item_id')
                    ->where('ma.garage_id', (int) $job->garage_id)
                    ->where('ma.attachable_type', \App\Models\Job::class)
                    ->where('ma.attachable_id', (int) $job->id)
                    // Dedup: keep latest per media_item_id + label
                    ->whereIn('ma.id', function ($q) use ($job) {
                        $q->from('media_attachments')
                            ->selectRaw('MAX(id)')
                            ->where('garage_id', (int) $job->garage_id)
                            ->where('attachable_type', \App\Models\Job::class)
                            ->where('attachable_id', (int) $job->id)
                            ->groupBy('media_item_id', 'label');
                    })
                    ->select([
                        DB::raw('ma.id as id'),
                        'ma.media_item_id',
                        DB::raw("ma.label as category"),      // inspection|completion
                        DB::raw("ma.label as label"),
                        DB::raw('mi.disk as storage_disk'),
                        DB::raw('mi.path as storage_path'),
                        'mi.mime_type',
                        DB::raw('ma.id as sort_order'),
                    ])
                    ->orderBy('ma.id');

                $inspectionRows = (clone $base)->where('ma.label', 'inspection')->get();
                $completionRows = (clone $base)->where('ma.label', 'completion')->get();
            }
        } catch (\Throwable $e) {
            $inspectionRows = collect();
            $completionRows = collect();
        }

        // -----------------------------
        // Create ZIP
        // -----------------------------
        $zip = new \ZipArchive();

        if ($zip->open($zipAbsPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create Claim Pack ZIP');
        }

        $zip->addFromString('01_claim_summary.pdf', $summaryPdf->output());

        if ($invoicePdfAbs && file_exists($invoicePdfAbs)) {
            $zip->addFile($invoicePdfAbs, '02_invoice.pdf');
        }

        $diskDefault = 'public';

        // -----------------------------
        // BEFORE photos (inspection)
        // -----------------------------
        foreach ($inspectionRows as $i => $p) {

            $disk = $p->storage_disk ?: $diskDefault;
            $path = $p->storage_path ?: null;

            if (!$path || !Storage::disk($disk)->exists($path)) {
                continue;
            }

            $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
            $idx = str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT);

            $zip->addFromString(
                "photos/inspection/{$idx}.{$ext}",
                Storage::disk($disk)->get($path)
            );
        }

        // -----------------------------
        // AFTER photos (completion)
        // -----------------------------
        foreach ($completionRows as $i => $p) {

            $disk = $p->storage_disk ?: $diskDefault;
            $path = $p->storage_path ?: null;

            if (!$path || !Storage::disk($disk)->exists($path)) {
                continue;
            }

            $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
            $idx = str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT);

            $zip->addFromString(
                "photos/completion/{$idx}.{$ext}",
                Storage::disk($disk)->get($path)
            );
        }

        $zip->close();

        // ✅ Get claim row (auto-create draft on first generate)
        $claim = InsuranceClaim::query()
            ->where('garage_id', $garageId)
            ->where('job_id', (int) $job->id)
            ->first();

        if (!$claim) {
            $claim = InsuranceClaim::create([
                'garage_id'   => $garageId,
                'job_id'      => (int) $job->id,
                'status'      => 'draft',   // important: not submitted yet
                'submitted_at'=> null,
            ]);
        }

        $nextVersion = ((int) ($claim->pack_version ?? 0)) + 1;

        $claim->pack_version       = $nextVersion;
        $claim->pack_generated_at  = now();
        $claim->pack_last_filename = $zipName;
        $claim->pack_path          = $zipRelPath;
        $claim->save();

        return [
            'abs' => $zipAbsPath,
            'name' => $zipName,
            'rel' => $zipRelPath,
        ];
    }

    public function generatePack(Job $job, InsuranceGate $gate)
    {
        $garageId = (int) $job->garage_id;

        // ✅ Ensure claim row exists (auto-create draft on first generate)
        $claim = \App\Models\InsuranceClaim::firstOrCreate(
            [
                'garage_id' => $garageId,
                'job_id'    => (int) $job->id,
            ],
            [
                'status'       => 'draft',
                'submitted_at' => null,
            ]
        );
        // ✅ Build the zip (make buildClaimPackZip RETURN the zip path + filename if possible)
        // We’ll support both: (a) returns string path, or (b) returns ['path'=>..,'filename'=>..]
        $result = $this->buildClaimPackZip($job, $gate);

        $zipPath = null;
        $zipFilename = null;

        if (is_string($result)) {
            $zipPath = $result;
            $zipFilename = basename($result);
            } elseif (is_array($result)) {
                // support buildClaimPackZip() current return shape
                $zipPath = $result['rel'] ?? ($result['path'] ?? null);
                $zipFilename = $result['name'] ?? ($result['filename'] ?? ($zipPath ? basename($zipPath) : null));
            }

        // ✅ If builder didn’t return anything, we still proceed but won’t write pack fields.
        // (Better to update buildClaimPackZip to return path.)
        if ($zipPath) {
            // next version
            $current = (int) ($claim->pack_version ?? 0);
            $newVersion = $current + 1;

            // ✅ Persist claim pack meta
            $claim->forceFill([
                'pack_version'       => $newVersion,
                'pack_path'          => $zipPath,
                'pack_last_filename' => $zipFilename,
                'pack_generated_at'  => now(),
            ])->save();

            // ✅ Optional: shadow fields for legacy UI checks (safe even if unused)
            DB::table('job_insurance_details')
                ->where('job_id', $job->id)
                ->update([
                    'claim_pack_version' => $newVersion,
                    'claim_pack_path'    => $zipPath,
                    'updated_at'         => now(),
                ]);
        }

        return redirect()
            ->route('jobs.insurance.claim.show', $job->id)
            ->with('success', 'Claim Pack generated successfully.');
    }
    
    public function downloadPack(Job $job, InsuranceGate $gate)
    {
        $claim = InsuranceClaim::query()
            ->where('garage_id', (int) $job->garage_id)
            ->where('job_id', (int) $job->id)
            ->first();

        if (!$claim) {
            abort(404, 'Claim not submitted.');
        }

        // If file exists → reuse it
        if ($claim->pack_path && $claim->pack_last_filename) {
            $existingPath = storage_path("app/{$claim->pack_path}");

            if (file_exists($existingPath)) {
                return response()->download($existingPath, $claim->pack_last_filename);
            }
        }

        // Otherwise regenerate (still only allowed because claim exists)
        [$zipAbsPath, $zipName, $zipRelPath] = $this->buildClaimPackZip($job, $gate);

        return response()->download($zipAbsPath, $zipName);
    }

    public function sharePack(Job $job)
    {
        $claim = InsuranceClaim::query()
            ->where('garage_id', (int) $job->garage_id)
            ->where('job_id', (int) $job->id)
            ->first();

        if (!$claim || !$claim->pack_path || !$claim->pack_last_filename) {
            abort(404, 'Claim pack not generated.');
        }

        $path = storage_path("app/{$claim->pack_path}");

        if (!file_exists($path)) {
            abort(404, 'Claim pack file missing.');
        }

        return response()->download($path, $claim->pack_last_filename);
    }

    public function attachCompletionPhotos(Request $request, Job $job)
    {
        \Log::info('attachCompletionPhotos payload', [
            'all'   => $request->all(),
            'files' => array_keys($request->allFiles()),
        ]);
        $request->validate([
            'media_item_ids' => ['required', 'array', 'min:1'],
            'media_item_ids.*' => ['integer'],
        ]);

        $garageId = (int) $job->garage_id;

        // ✅ Require repair session (your real-world rule)
        $repair = \DB::table('job_repairs')
            ->where('garage_id', $garageId)
            ->where('job_id', $job->id)
            ->orderByDesc('id')
            ->first();

        if (!$repair) {
            return back()->with('error', 'Start a repair session before adding completion photos.');
        }

        $ids = array_values(array_unique(array_map('intval', $request->media_item_ids)));

        // ✅ Load media items (need storage info)
        $mediaItems = \App\Models\MediaItem::query()
            ->where('garage_id', $garageId)
            ->whereIn('id', $ids)
            ->get(['id', 'disk', 'path', 'mime_type']);

        if ($mediaItems->count() === 0) {
            return back()->with('error', 'No valid media selected.');
        }

        \DB::transaction(function () use ($garageId, $job, $mediaItems) {

            foreach ($mediaItems as $mi) {

                // Attach to JOB (for UI / claim pack completion section)
                \DB::table('media_attachments')->updateOrInsert(
                    [
                        'garage_id'        => $garageId,
                        'media_item_id'    => (int) $mi->id,
                        'attachable_type'  => \App\Models\Job::class,
                        'attachable_id'    => (int) $job->id,
                        'label'            => 'completion',
                    ],
                    [
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        return back()->with('success', $mediaItems->count() . ' completion photo(s) attached.');
    }

    public function submit(Request $request, \App\Models\Job $job)
    {
        // ✅ Ensure tenant access (use your existing guard)
        abort_unless(auth()->check(), 401);

        abort_unless(
            (int) $job->garage_id === (int) auth()->user()->garage_id,
            403,
            'Unauthorized garage access.'
        ); 

        // ✅ STRICT v1: prevent double submit
        $existing = InsuranceClaim::where('garage_id', $job->garage_id)
            ->where('job_id', $job->id)
            ->first();

        if ($existing && !empty($existing->submitted_at)) {
            return back()->with('error', 'Claim already submitted for this job.');
        }

        // ✅ Server-side truth checks (keep minimal, use DB truth you already rely on)
        // 1) Must have completion unlocked (or completed_at) depending on your workflow
        // Adapt these to your existing fields/gates:
        if (empty($job->completed_at)) {
            return back()->with('error', 'Complete the job before submitting a claim.');
        }

        // 2) Must have an invoice (and ideally finalized)
        $invoiceId = DB::table('invoices')
            ->where('garage_id', $job->garage_id)
            ->where('job_id', $job->id)
            ->orderByDesc('id')
            ->value('id');

        if (!$invoiceId) {
            return back()->with('error', 'Generate the invoice before submitting a claim.');
        }

        // 3) Must have latest approved approval pack (optional if your flow requires)
        $approvalPackId = DB::table('approval_packs')
            ->where('garage_id', $job->garage_id)
            ->where('job_id', $job->id)
            ->where('status', 'approved')
            ->orderByDesc('id')
            ->value('id');

        // If approval is mandatory in your flow, enforce it:
        // if (!$approvalPackId) return back()->with('error', 'Approve scope before submitting claim.');

        // ✅ Create claim + lock financials
        DB::transaction(function () use ($request, $job, $invoiceId, $approvalPackId) {

            $claimNumber = $this->nextClaimNumber($job->garage_id);


        // ✅ Find existing claim row (created during generate), otherwise create
        $claim = InsuranceClaim::where('garage_id', $job->garage_id)
            ->where('job_id', $job->id)
            ->first();

        if (!$claim) {
            $claim = new InsuranceClaim([
                'garage_id' => $job->garage_id,
                'job_id'    => $job->id,
            ]);
        }

        // ✅ Only set claim_number if missing (don’t overwrite if already set)
        if (empty($claim->claim_number)) {
            $claim->claim_number = $claimNumber;
        }

        // ✅ Submit (updates the same row if it already exists)
        $claim->status           = 'submitted';
        $claim->approval_pack_id = $approvalPackId;
        $claim->invoice_id       = $invoiceId;
        $claim->notes            = $request->input('notes');
        $claim->submitted_at     = now();
        $claim->submitted_by     = auth()->id();
        $claim->save();

            // ✅ Freeze invoice edits (choose 1 mechanism)
            // Option A (recommended): add invoices.locked_at + locked_by later
            // For now (minimal): use existing status or a boolean column if you already have one.
            DB::table('invoices')->where('id', $invoiceId)->update([
                'updated_at' => now(),
                // 'is_locked' => 1,  // if you already have it
            ]);

            // ✅ Unlock Settlement ONLY after claim submitted (DB truth)
            // If you already have a field like jobs.claim_unlocked or jobs.settlement_unlocked, set it.
            // Otherwise, we’ll compute gates from existence of insurance_claims.
            DB::table('jobs')->where('id', $job->id)->update([
                'updated_at' => now(),
                // 'claim_submitted_at' => now(), // only if column exists
            ]);
        });

        return redirect()
            ->route('jobs.insurance.show', $job)
            ->with('success', 'Claim submitted. Settlement is now unlocked.');
    }

    /**
     * Generate next claim number per garage: CLM-YYYY-00001
     */
    protected function nextClaimNumber(int $garageId): string
    {
        $year = now()->format('Y');

        $last = DB::table('insurance_claims')
            ->where('garage_id', $garageId)
            ->where('claim_number', 'like', "CLM-{$year}-%")
            ->orderByDesc('id')
            ->value('claim_number');

        $nextSeq = 1;
        if ($last && preg_match('/CLM-\d{4}-(\d+)/', $last, $m)) {
            $nextSeq = ((int)$m[1]) + 1;
        }

        return 'CLM-' . $year . '-' . str_pad((string)$nextSeq, 5, '0', STR_PAD_LEFT);
    }
}