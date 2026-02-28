<?php

namespace App\Services;

use App\Models\ApprovalPack;
use App\Models\Job;
use App\Models\MediaAttachment;
use App\Models\MediaItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ApprovalPackGenerator
{
    /**
     * Generate a NEW pack (draft by default) and snapshot:
     * - quotation lines -> approval_pack_items
     * - photos -> approval_pack_photos
     *
     * Safe, garage-scoped, and transactional.
     */
    public function generate(Job $job, array $opts = []): ApprovalPack
    {
        $user = Auth::user();
        $garageId = $opts['garage_id'] ?? ($user?->garage_id);

        if (empty($garageId)) {
            throw ValidationException::withMessages([
                'auth' => 'No garage context. Provide garage_id in $opts when running from CLI/tinker.',
            ]);
        }

        $this->assertJobBelongsToGarage($job, $garageId);
        $this->assertPrereqs($job);

        return DB::transaction(function () use ($job, $garageId, $opts, $user) {

            $quotation = $this->resolveQuotation($job, $garageId);
            $version   = $this->nextVersion($job, $garageId);

            $pack = ApprovalPack::create([
                'garage_id'     => $garageId,
                'job_id'        => $job->id,
                'quotation_id'  => $quotation?->id,
                'status'        => 'draft',
                'version'       => $version,
                'currency'      => $opts['currency'] ?? 'KES',
                'generated_by'  => $opts['generated_by'] ?? ($user?->id),
                'generated_at'  => now(),
                'total_amount'  => 0,
            ]);

            // 1) Snapshot quotation lines
            $total = $this->snapshotItems($pack, $quotation, $garageId);

            // 2) Snapshot photos (Vault/inspection/job photos)
            $this->snapshotPhotos($pack, $job, $garageId);

            // Update totals after snapshots
            $pack->update([
                'total_amount' => $total,
            ]);

            return $pack;
        });
    }

    /**
     * Mark pack submitted.
     * PERMANENT FIX:
     * - transactional
     * - idempotent
     * - auto-heals missing snapshot items
     */
    public function submit(ApprovalPack $pack, array $opts = []): ApprovalPack
    {
        $user = Auth::user();
        $garageId = $opts['garage_id'] ?? ($user?->garage_id);

        if (empty($garageId)) {
            throw ValidationException::withMessages([
                'auth' => 'No garage context. Provide garage_id in $opts when running from CLI/tinker.',
            ]);
        }

        if ((int)$pack->garage_id !== (int)$garageId) {
            abort(403);
        }

        return DB::transaction(function () use ($pack, $garageId, $user) {

            $lockedPack = ApprovalPack::query()
                ->where('garage_id', $garageId)
                ->lockForUpdate()
                ->findOrFail($pack->id);

            // ✅ Idempotent: already submitted/approved still gets healed
            if (in_array($lockedPack->status, ['submitted', 'approved'], true)) {
                $this->ensureSnapshotIntegrity($lockedPack, (int)$garageId);
                return $lockedPack->fresh();
            }

            if ($lockedPack->status !== 'draft') {
                throw ValidationException::withMessages([
                    'approval_pack' => 'Only a draft pack can be submitted.',
                ]);
            }

            // ✅ Ensure items exist (self-heal if missing)
            $this->ensureSnapshotIntegrity($lockedPack, (int)$garageId);

            $updates = [
                'status'       => 'submitted',
                'submitted_at' => now(),
            ];

            if (Schema::hasColumn('approval_packs', 'submitted_by')) {
                $updates['submitted_by'] = $user?->id;
            }

            $lockedPack->update($updates);

            return $lockedPack->fresh();
        });
    }

    /**
     * Ensures approval_pack_items snapshot exists and pack total matches items.
     * If items are missing, rebuild from quotation lines.
     */
    protected function ensureSnapshotIntegrity(ApprovalPack $pack, int $garageId): void
    {
        // If items exist, just sync total_amount from DB truth
        $itemsCount = DB::table('approval_pack_items')
            ->where('garage_id', $garageId)
            ->where('approval_pack_id', $pack->id)
            ->count();

        if ($itemsCount === 0) {
            // Re-resolve quotation from job and rebuild items
            $job = Job::query()
                ->where('garage_id', $garageId)
                ->findOrFail($pack->job_id);

                $quotation = null;

                // Prefer pack's quotation_id if set
                if (!empty($pack->quotation_id) && Schema::hasTable('job_quotations')) {
                    $quotation = DB::table('job_quotations')
                        ->where('garage_id', $garageId)
                        ->where('id', $pack->quotation_id)
                        ->first();
                }

                $quotation = $quotation ?: $this->resolveQuotation($job, $garageId);

                $total = $this->snapshotItems($pack, $quotation, $garageId);

            // Keep totals correct
            $pack->update(['total_amount' => $total]);
            return;
        }

        // Otherwise: enforce DB truth total
        $sum = (float) DB::table('approval_pack_items')
            ->where('garage_id', $garageId)
            ->where('approval_pack_id', $pack->id)
            ->sum('line_total');

        // Only write if drift
        if ((float)$pack->total_amount !== $sum) {
            $pack->update(['total_amount' => $sum]);
        }
    }

    /* ----------------------------- Helpers ----------------------------- */

    protected function assertJobBelongsToGarage(Job $job, int $garageId): void
    {
        if ((int)$job->garage_id !== (int)$garageId) {
            abort(403);
        }
    }

    protected function assertPrereqs(Job $job): void
    {
        // You already store inspection_completed_at on jobs (per your DB output)
        if (empty($job->inspection_completed_at)) {
            throw ValidationException::withMessages([
                'inspection' => 'Inspection must be completed before generating an approval pack.',
            ]);
        }
    }

    /**
     * Resolves the quotation for this job.
     * Prefers relationship if present, otherwise queries job_quotations.
     */
    protected function resolveQuotation(Job $job, int $garageId)
    {
        // Relationship-based (if you have $job->quotation)
        if (method_exists($job, 'quotation')) {
            $q = $job->quotation()->where('garage_id', $garageId)->latest('id')->first();
            if ($q) return $q;
        }

        // Fallback: query table directly (you showed job_quotations exists)
        if (Schema::hasTable('job_quotations')) {
            return DB::table('job_quotations')
                ->where('garage_id', $garageId)
                ->where('job_id', $job->id)
                ->orderByDesc('id')
                ->first();
        }

        return null;
    }

    protected function nextVersion(Job $job, int $garageId): int
    {
        $latest = ApprovalPack::query()
            ->where('garage_id', $garageId)
            ->where('job_id', $job->id)
            ->max('version');

        return (int)$latest + 1;
    }

    /**
     * Snapshot quotation lines into approval_pack_items.
     * Returns computed total_amount.
     *
     * NOTE: This is written to be resilient.
     * If your quotation line table/name differs, you only adjust this method.
     */
    protected function snapshotItems(ApprovalPack $pack, $quotation, int $garageId): float
    {
        $rows = [];

        // 1) Try relationship on Eloquent quotation model: lines() or items()
        if (is_object($quotation)) {
            if (method_exists($quotation, 'lines')) {
                $rows = $quotation->lines()->get()->toArray();
            } elseif (method_exists($quotation, 'items')) {
                $rows = $quotation->items()->get()->toArray();
            }
        }

        // 2) If quotation came from DB::table (stdClass) OR no relation, try common table names
        if (empty($rows) && $quotation && isset($quotation->id)) {

            foreach (['job_quotation_lines', 'job_quotation_items', 'quotation_lines', 'quotation_items'] as $table) {

                if (!Schema::hasTable($table)) {
                    continue;
                }

                // Auto-detect the FK column name used by this table
                $fkCandidates = ['job_quotation_id', 'quotation_id', 'jobquotation_id', 'job_quotationid'];

                $fk = null;
                foreach ($fkCandidates as $candidate) {
                    if (Schema::hasColumn($table, $candidate)) {
                        $fk = $candidate;
                        break;
                    }
                }

                // If we can't detect FK, skip this table
                if (!$fk) {
                    continue;
                }

                $rows = DB::table($table)
                    ->where('garage_id', $garageId)
                    ->where($fk, $quotation->id)
                    ->orderBy('id')
                    ->get()
                    ->map(fn($r) => (array)$r)
                    ->all();

                if (!empty($rows)) {
                    break;
                }
            }
        }


        // If still empty, enforce: cannot generate pack without at least 1 line
        if (empty($rows)) {
            throw ValidationException::withMessages([
                'quotation' => 'Quotation lines are required before generating an approval pack.',
            ]);
        }

        $total = 0.0;

        foreach ($rows as $r) {
            // job_quotation_lines uses: type, description, qty, unit_price, amount
            $name = $r['name'] ?? $r['title'] ?? $r['item_name'] ?? $r['description'] ?? 'Line Item';
            $desc = $r['notes'] ?? null; // keep description as the main label; notes only if present

            $qty  = (float)($r['qty'] ?? $r['quantity'] ?? 1);
            $unit = (float)($r['unit_price'] ?? $r['price'] ?? 0);

            // IMPORTANT: prefer amount (your real schema), then line_total, then fallback
            $line = (float)($r['amount'] ?? $r['line_total'] ?? ($qty * $unit));

            $total += $line;

            DB::table('approval_pack_items')->insert([
                'garage_id'                => $garageId,
                'approval_pack_id'         => $pack->id,
                'line_type'                => $r['line_type'] ?? $r['type'] ?? 'part',
                'name'                     => (string)$name,
                'description'              => $desc ? (string)$desc : null,
                'qty'                      => $qty,
                'unit_price'               => $unit,
                'line_total'               => $line,
                'tax_code'                 => $r['tax_code'] ?? null,
                'tax_amount'               => $r['tax_amount'] ?? null,
                'source_quotation_line_id' => $r['id'] ?? null,
                'created_at'               => now(),
                'updated_at'               => now(),
            ]);
        }

        return $total;
    }

    /**
     * Snapshot INSPECTION photos into approval_pack_photos (Option A).
     *
     * Rules:
     *  - ONLY media_attachments.label = 'inspection_photo'
     *  - DEDUPE by media_item_id (take a single attachment row per media item)
     *  - ALWAYS write category='inspection' and label='inspection_photo'
     *  - Idempotent: clears existing inspection snapshot rows for this pack, then rebuilds
     */
    protected function snapshotPhotos(ApprovalPack $pack, Job $job, int $garageId): void
    {
        // Only proceed if Job has mediaAttachments() (Vault system)
        if (!method_exists($job, 'mediaAttachments')) {
            return;
        }

        // 1) Pull INSPECTION attachments only
        $atts = $job->mediaAttachments()
            ->where('garage_id', $garageId)
            ->where('label', 'inspection_photo')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($atts->isEmpty()) {
            // Still enforce Option A: no "best effort" mixed snapshot
            return;
        }

        // 2) Dedupe by media_item_id (take first by sort_order,id)
        $deduped = $atts
            ->filter(fn($a) => !empty($a->media_item_id))
            ->groupBy('media_item_id')
            ->map(fn($group) => $group->first())
            ->values();

        if ($deduped->isEmpty()) {
            return;
        }

        // 3) Load media items for storage fields
        $mediaIds = $deduped->pluck('media_item_id')->unique()->values();
        $mediaMap = MediaItem::query()
            ->where('garage_id', $garageId)
            ->whereIn('id', $mediaIds)
            ->get()
            ->keyBy('id');

        // 4) Idempotent rebuild: wipe INSPECTION rows for this pack
        DB::table('approval_pack_photos')
            ->where('garage_id', $garageId)
            ->where('approval_pack_id', (int) $pack->id)
            ->where(function ($q) {
                $q->whereNull('category')->orWhere('category', 'inspection');
            })
            ->delete();

        // 5) Insert snapshot rows
        $i = 0;
        foreach ($deduped as $att) {
            $mi = $att->media_item_id ? ($mediaMap[$att->media_item_id] ?? null) : null;

            DB::table('approval_pack_photos')->insert([
                'garage_id'            => $garageId,
                'approval_pack_id'     => (int) $pack->id,
                'media_item_id'        => (int) $att->media_item_id,

                // Option A hard-lock
                'category'             => 'inspection',
                'label'                => 'inspection_photo',

                'storage_disk'         => $mi->disk ?? $mi->storage_disk ?? null,
                'storage_path'         => $mi->path ?? $mi->storage_path ?? null,
                'mime_type'            => $mi->mime_type ?? null,
                'file_size'            => $mi->size ?? $mi->file_size ?? null,

                'sort_order'           => $i,
                'source_attachment_id' => $att->id ?? null,

                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            $i++;
        }
    }
}
