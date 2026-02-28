<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Models\ApprovalPack;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InsuranceInvoiceController extends Controller
{
    /**
     * POST jobs/{job}/insurance/invoice/generate
     *
     * Generates (or returns existing) invoice for an insurance job, using the latest approved approval pack.
     * Idempotent: invoices.job_id is UNIQUE, so we never create duplicates.
     */
    public function generate(Job $job)
    {
        // Multi-tenant guard (mandatory)
        abort_unless((int) $job->garage_id === (int) auth()->user()->garage_id, 403);

        // Contract checks
        abort_unless($job->payer_type === 'insurance', 422, 'Not an insurance job.');
        abort_unless(!empty($job->completed_at), 403, 'Job not completed.');

        // Enforce LPO before invoice (your locked rule)
        // Requires Job::insuranceDetails() relation to exist (hasOne JobInsuranceDetail by job_id)
        $insurance = $job->insuranceDetails;
        $garageId = (int) auth()->user()->garage_id;

        $lpo = (string) \DB::table('job_insurance_details')
            ->where('garage_id', $garageId)
            ->where('job_id', $job->id)
            ->value('lpo_number');

        $lpo = trim($lpo);

        abort_unless($lpo !== '', 422, 'LPO required before invoicing.');

        return DB::transaction(function () use ($job, $lpo) {

            // ✅ Idempotency (scope by garage for safety)
            $existing = Invoice::where('garage_id', $job->garage_id)
                ->where('job_id', $job->id)
                ->first();

            if ($existing) {
                return response()->json([
                    'ok' => true,
                    'already_existed' => true,
                    'invoice_id' => $existing->id,
                    'redirect_url' => route('invoices.show', $existing),
                ]);
            }

            // Latest approved pack for this job
            $pack = ApprovalPack::where('garage_id', $job->garage_id)
                ->where('job_id', $job->id)
                ->where('status', 'approved')
                ->latest('id')
                ->firstOrFail();

            // Load pack items (scoped)
            $packItems = DB::table('approval_pack_items')
                ->where('garage_id', $job->garage_id)
                ->where('approval_pack_id', $pack->id)
                ->orderBy('id')
                ->get();

            abort_unless($packItems->count() > 0, 422, 'Approved pack has no items.');

            // Create invoice header (matches your invoices table)
            $invoice = Invoice::create([
                'garage_id'      => $job->garage_id,
                'job_id'         => $job->id,
                'customer_id'    => $job->customer_id,
                'vehicle_id'     => $job->vehicle_id,

                'invoice_number' => $this->nextNumber($job->garage_id),
                'issue_date'     => now()->toDateString(),
                'due_date'       => null,

                'status'         => 'draft',
                'payment_status' => 'unpaid',
                'paid_amount'    => 0.00,
                'paid_at'        => null,

                // We'll compute these below
                'subtotal'       => 0.00,
                'tax_rate'       => 16.00, // keep your default; change if insurance should be tax-free
                'tax_amount'     => 0.00,
                'total_amount'   => 0.00,

                'currency'       => 'KES',
                // ✅ snapshot
                'lpo' => $lpo ?: null,

            ]);

            // Copy approval pack items -> invoice items
            $subtotal = 0.0;

            foreach ($packItems as $pi) {

                // Normalize line_type -> enum('labour','part')
                $lt = Str::lower(trim((string) $pi->line_type));
                $itemType = in_array($lt, ['labour', 'labor'], true) ? 'labour' : 'part';

                // Description: use name, optionally append short description
                $desc = trim((string) $pi->name);
                if (!empty($pi->description)) {
                    $extra = trim(strip_tags((string) $pi->description));
                    if ($extra !== '') {
                        $desc .= ' — ' . Str::limit($extra, 120);
                    }
                }

                // qty is DECIMAL in approval_pack_items; invoice_items.quantity should be DECIMAL too (recommended)
                $qty = (float) $pi->qty;
                if ($qty <= 0) $qty = 1;

                $unitPrice = (float) $pi->unit_price;

                // line_total comes from pack (already computed)
                $lineTotal = (float) $pi->line_total;

                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'item_type'   => $itemType,
                    'description' => $desc,
                    'quantity'    => $qty,              // ensure invoice_items.quantity is DECIMAL(12,2)
                    'unit_price'  => round($unitPrice, 2),
                    'line_total'  => round($lineTotal, 2),
                ]);

                $subtotal += $lineTotal;
            }

            // Canonical totals (always correct, prevents drift)
            $invoice->recalcTotals();

            // Optional: auto mark as sent once generated
            $invoice->update(['status' => 'sent']);

            return response()->json([
                'ok' => true,
                'already_existed' => false,
                'invoice_id' => $invoice->id,
                'redirect_url' => route('invoices.show', $invoice),
            ]);
        });
    }

    /**
     * Simple invoice numbering: INV-YYYY-000001 (per garage)
     */
    private function nextNumber(int $garageId): string
    {
        $year = now()->format('Y');

        $last = Invoice::where('garage_id', $garageId)
            ->where('invoice_number', 'like', "INV-$year-%")
            ->orderByDesc('id')
            ->value('invoice_number');

        $next = 1;
        if ($last && preg_match('/INV-\d{4}-(\d+)/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return sprintf("INV-%s-%06d", $year, $next);
    }
}