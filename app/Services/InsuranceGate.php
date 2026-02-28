<?php
// app/Services/InsuranceGate.php

namespace App\Services;

use App\Models\Job;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
class InsuranceGate
{
    public function forJob(\App\Models\Job $job): array
    {
        // Canonical approval status — ONLY approval_packs table
        $packStatus = \App\Models\ApprovalPack::query()
            ->where('job_id', $job->id)
            ->orderByDesc('id')
            ->value('status') ?: 'draft';

        // Back-compat normalization if legacy ever existed
        if ($packStatus === 'pending') $packStatus = 'submitted';

        

        // ✅ Phase 4 — Quotation Editability (single source of truth)
        $latestQuotation = \App\Models\JobQuotation::query()
            ->where('garage_id', $job->garage_id)
            ->where('job_id', $job->id)
            ->latest('id')
            ->first();

        $inspectionCompleted = !is_null($job->inspection_completed_at);

        $quotationEditable = false;
        $quotationStatus   = null;

        if ($latestQuotation) {
            $quotationStatus = $latestQuotation->status;

            // Editable ONLY while draft after inspection
            $quotationEditable =
                $inspectionCompleted
                && $quotationStatus === 'draft';
        }

        // ---------------------------------
        // ✅ Settlement (binds to Invoice + Payments truth)
        // ---------------------------------
        $invoice = $job->invoice()
            ->select(['id','garage_id','job_id','total_amount','paid_amount','payment_status','paid_at'])
            ->first();

        $hasInvoice = (bool) $invoice;

        // normalize status
        $paymentStatus = (string) ($invoice->payment_status ?? 'unpaid');
        if (!in_array($paymentStatus, ['unpaid','partial','paid'], true)) {
            $paymentStatus = 'unpaid';
        }

        $invoicePaid    = $hasInvoice && $paymentStatus === 'paid';
        $invoicePartial = $hasInvoice && $paymentStatus === 'partial';

        $total   = (float) ($invoice->total_amount ?? 0);
        $paid    = (float) ($invoice->paid_amount ?? 0);
        $balance = $hasInvoice ? max(0, $total - $paid) : null;

        $settlementState = $invoicePaid
            ? 'settled'
            : ($invoicePartial ? 'partially_settled' : 'awaiting_settlement');

        return [
            // Phase 4 gates
            'quotation_status'   => $quotationStatus,
            'is_insurance'       => $this->isInsurance($job),

            'inspection_started'  => $this->inspectionStarted($job),
            'inspection_complete' => $this->inspectionComplete($job),

            'quote_submitted' => $this->quoteSubmitted($job),

            // ✅ Canonical approval gate for UI
            'approval' => [
                'status' => $packStatus, // draft|submitted|approved|rejected
            ],

            'repair_started'   => $this->repairStarted($job),
            'repair_completed' => $this->repairCompleted($job),

            // derived unlocks (UI reads only these)
            'completion_unlocked' => $this->repairCompleted($job),
            'invoice_unlocked'     => data_get($this->canInvoice($job), 'ok', false),
            'has_lpo'              => $this->hasLpo($job),

            // ✅ Phase 2 Claim (binds to job_insurance_details + invoices ONLY)
            'claim' => [
                'claim_number' => $this->claimNumber($job),
                'insurer_name' => $this->insurerName($job),
            ],
            'claim_unlocked' => data_get($this->canViewClaim($job), 'ok', false),

            'has_invoice'         => $hasInvoice,
            'settlement_unlocked' => $hasInvoice,
            'settlement_complete' => $invoicePaid,
            'invoice_paid'        => $invoicePaid,
            'invoice_partial'     => $invoicePartial,

            'settlement' => [
                'state'          => $settlementState,     // awaiting_settlement|partially_settled|settled
                'payment_status' => $paymentStatus,       // unpaid|partial|paid
                'invoice_id'     => $invoice?->id,
                'total'          => $hasInvoice ? $total : null,
                'paid'           => $hasInvoice ? $paid : null,
                'balance'        => $balance,
                'paid_at'        => $invoice?->paid_at,
            ],
            
            'can_view_claim'   => $this->canViewClaim($job),
            'can_edit_claim'   => $this->canEditClaim($job),
            'can_submit_claim' => $this->canSubmitClaim($job),

            // permissions (keep your existing methods)
            'can_edit_inspection'     => $this->canEditInspection($job),
            'can_complete_inspection' => $this->canCompleteInspection($job),
            'can_edit_quote'          => $this->canEditQuote($job),
            'can_submit_quote'        => $this->canSubmitQuote($job),
            'can_approve_quote'       => $this->canApproveQuote($job),
            'can_start_repair'        => $this->canStartRepair($job),
            'can_invoice'             => $this->canInvoice($job),
        ];
    }

    /**
     * Quick helpers
     */
    public function isInsurance(Job $job): bool
    {
        return ($job->payer_type ?? null) === 'insurance';
    }

    public function inspectionStarted(Job $job): bool
    {
        // Deterministic: if an inspection row exists, inspection has started
        return \App\Models\JobInspection::query()
            ->where('job_id', $job->id)
            ->exists();
    }

    public function inspectionComplete(\App\Models\Job $job): bool
    {
        return \App\Models\JobInspection::query()
            ->where('job_id', $job->id)
            ->where('status', 'completed')
            ->exists();
    }



    public function repairStarted(Job $job): bool
    {
        return \App\Models\JobRepair::query()
            ->where('job_id', $job->id)
            ->where(function ($q) {
                $q->whereNotNull('started_at')
                ->orWhereIn('status', ['started', 'in_progress', 'completed']);
            })
            ->exists();
    }


    public function quoteExists(Job $job): bool
    {
        return \App\Models\JobQuotation::query()
            ->where('job_id', $job->id)
            ->exists();
    }

    public function quoteSubmitted(Job $job): bool
    {
        return \App\Models\JobQuotation::query()
            ->where('job_id', $job->id)
            ->whereNotNull('submitted_at')
            ->exists();
    }


    public function quoteApproved(Job $job): bool
    {
        return \App\Models\ApprovalPack::query()
            ->where('job_id', $job->id)
            ->where('status', 'approved')
            ->exists();
    }


    public function hasLpo(Job $job): bool
    {
        $lpo = (string) \App\Models\JobInsuranceDetail::query()
            ->where('job_id', $job->id)
            ->value('lpo_number');

        return Str::of($lpo)->trim()->isNotEmpty();
    }

    public function repairCompleted(Job $job): bool
    {
        $garageId = (int) $job->garage_id;

        $repair = \DB::table('job_repairs')
            ->where('garage_id', $garageId)
            ->where('job_id', $job->id)
            ->orderByDesc('id')
            ->first();

        if (! $repair) return false;

        // session must be closed
        if (empty($repair->completed_at) && (($repair->status ?? null) !== 'completed')) {
            return false;
        }

        // ✅ only block if there are still active/pending items
        $blocking = \DB::table('job_repair_items')
            ->where('garage_id', $garageId)
            ->where('job_repair_id', $repair->id)
            ->whereIn('execution_status', ['pending', 'in_progress'])
            ->count();

        return $blocking === 0;
    }

    /**
     * === Core gates ===
     * Return: ['ok'=>bool, 'reason'=>string, 'code'=>string]
     */
    public function canEditInspection(Job $job): array
    {
        if (!$this->isInsurance($job)) return $this->ok();
        if ($this->repairStarted($job)) return $this->deny('Repair already started. Inspection is locked.', 'inspection.locked.repair_started');
        if ($this->inspectionComplete($job)) return $this->deny('Inspection is completed and locked.', 'inspection.locked.complete');
        return $this->ok();
    }

    public function canCompleteInspection(Job $job): array
    {
        if (!$this->isInsurance($job)) return $this->ok();

        // You already enforce: min 4 photos + checklist completion in the inspection module itself.
        if ($this->inspectionComplete($job)) return $this->deny('Inspection is already completed.', 'inspection.already_complete');
        if ($this->repairStarted($job)) return $this->deny('Repair already started. Cannot complete inspection now.', 'inspection.blocked.repair_started');

        return $this->ok();
    }

    public function canSubmitQuote(Job $job): array
    {
        if (!$this->isInsurance($job)) return $this->ok();

        if (!$this->inspectionComplete($job)) return $this->deny('Complete inspection before submitting a quotation.', 'quote.blocked.no_inspection');
        if ($this->repairStarted($job)) return $this->deny('Repair already started. Quotation cannot be submitted/changed.', 'quote.locked.repair_started');

        // Optional: require quote exists + has lines at controller level once you implement quotes table
        return $this->ok();
    }

    public function canEditQuote(Job $job): array
    {
        if (!$this->isInsurance($job)) return $this->ok();

        // 🔒 Hard lock boundary: once approved, quotation is immutable
        if ($this->quoteApproved($job)) {
            return $this->deny('Quotation is locked because approval is already completed.', 'quote.locked.approved');
        }

        // If repair already started, quotation changes are not allowed
        if ($this->repairStarted($job)) {
            return $this->deny('Repair already started. Quotation cannot be edited.', 'quote.locked.repair_started');
        }

        // Optional: keep your existing flow discipline (edit allowed only after inspection complete)
        if (!$this->inspectionComplete($job)) {
            return $this->deny('Complete inspection before editing quotation.', 'quote.blocked.no_inspection');
        }

        return $this->ok();
    }

    public function canApproveQuote(Job $job): array
    {
        if (!$this->isInsurance($job)) return $this->ok();

        if (!$this->inspectionComplete($job)) return $this->deny('Complete inspection before approval.', 'approval.blocked.no_inspection');
        if (!$this->quoteSubmitted($job)) return $this->deny('Quotation must be submitted before approval.', 'approval.blocked.not_submitted');
        if ($this->repairStarted($job)) return $this->deny('Repair already started. Approval is locked.', 'approval.locked.repair_started');

        return $this->ok();
    }

    public function canStartRepair(Job $job): array
    {
        if (!$this->isInsurance($job)) return $this->ok();

        if (!$this->inspectionComplete($job)) return $this->deny('Complete inspection before starting repair.', 'repair.blocked.no_inspection');
        if (!$this->quoteApproved($job)) return $this->deny('Quotation approval required before repair.', 'repair.blocked.not_approved');

        // If repair already started, we treat "start" as no-op (but you can allow continuing work elsewhere)
        if ($this->repairStarted($job)) return $this->deny('Repair already started.', 'repair.already_started');

        return $this->ok();
    }

    public function canAddLabourOrParts(Job $job): array
    {
        if (!$this->isInsurance($job)) return $this->ok();

        // Before repair has begun, enforce the "start repair" gate
        if (!$this->repairStarted($job)) {
            return $this->canStartRepair($job);
        }

        // After it has begun, allow continuing (unless you introduce later locks like "completed")
        return $this->ok();
    }

    public function canInvoice(Job $job): array
    {
        if (!$this->isInsurance($job)) return $this->ok();

        if (!$this->quoteApproved($job)) return $this->deny('Quotation must be approved before invoicing.', 'invoice.blocked.not_approved');
        if (!$this->hasLpo($job)) return $this->deny('LPO/Authority number is required before invoicing.', 'invoice.blocked.no_lpo');

        // Must be completed before invoicing
        if (is_null($job->completed_at)) {
            return $this->deny('Complete the job before invoicing.', 'invoice.blocked.not_completed');
        }
        // Option B (if you keep jobs.completed_at for now):
        // if (is_null($job->completed_at)) return $this->deny('Complete the job before invoicing.', 'invoice.blocked.not_completed');

        return $this->ok();
    }

    public function canSwitchPayer(Job $job): array
    {
        if (!$this->isInsurance($job)) return $this->ok();

        // Your lock-in: payer type locks after inspection starts
        if ($this->inspectionStarted($job) || $this->inspectionComplete($job)) {
            return $this->deny('Payer type is locked after inspection begins.', 'payer.locked.inspection_started');
        }

        return $this->ok();
    }

    /**
     * === Internals ===
     */


    public function claimRequiredReady(Job $job): bool
    {
        $claimNo = (string) DB::table('job_insurance_details')
            ->where('job_id', $job->id)
            ->value('claim_number');

        return trim($claimNo) !== '';
    }

    public function invoiceExists(Job $job): bool
    {
        return \DB::table('invoices')
            ->where('garage_id', (int) $job->garage_id)
            ->where('job_id', $job->id)
            ->exists();
    }

    public function canViewClaim(Job $job): array
    {
        if (! $this->isInsurance($job)) return $this->ok();

        $inv = $this->canInvoice($job);
        if (! data_get($inv, 'ok', false)) {
            return $this->deny(
                'Claim is locked until invoicing is unlocked (Approved + LPO + Completed).',
                'claim.locked.invoice_not_unlocked'
            );
        }

        return $this->ok();
    }

    public function canEditClaim(Job $job): array
    {
        if (! $this->isInsurance($job)) return $this->ok();

        $view = $this->canViewClaim($job);
        if (! data_get($view, 'ok', false)) return $view;

        return $this->ok();
    }

    public function canSubmitClaim(Job $job): array
    {
        if (! $this->isInsurance($job)) return $this->ok();

        $view = $this->canViewClaim($job);
        if (! data_get($view, 'ok', false)) return $view;

        if (! $this->invoiceExists($job)) {
            return $this->deny('Create an invoice before submitting a claim.', 'claim.blocked.no_invoice');
        }

        if (! $this->claimRequiredReady($job)) {
            return $this->deny('Enter the Claim Number before submitting.', 'claim.blocked.missing_claim_number');
        }

        return $this->ok();
    }

    // InsuranceGate.php

    protected function claimNumber(Job $job): ?string
    {
        if (!$this->isInsurance($job)) return null;

        $row = DB::table('job_insurance_details as jid')
            ->where('jid.job_id', $job->id)
            ->select('jid.claim_number')
            ->first();

        $claim = $row?->claim_number ?? null;
        $claim = trim((string) $claim);

        return $claim !== '' ? $claim : null;
    }

    protected function insurerName(Job $job): ?string
    {
        if (!$this->isInsurance($job)) return null;

        // Prefer insurer table name via insurer_id, fallback to stored insurer_name
        $row = DB::table('job_insurance_details as jid')
            ->leftJoin('insurers as i', function ($join) use ($job) {
                $join->on('i.id', '=', 'jid.insurer_id')
                    ->where('i.garage_id', '=', $job->garage_id); // garage-safety
            })
            ->where('jid.job_id', $job->id)
            ->select([
                'i.name as insurer_name_from_table',
                'jid.insurer_name as insurer_name_fallback',
            ])
            ->first();

        if (!$row) return null;

        $name = $row->insurer_name_from_table ?: $row->insurer_name_fallback;
        $name = trim((string) $name);

        return $name !== '' ? $name : null;
    }
    protected function ok(): array
    {
        return ['ok' => true, 'reason' => '', 'code' => 'ok'];
    }

    protected function deny(string $reason, string $code): array
    {
        return ['ok' => false, 'reason' => $reason, 'code' => $code];
    }
}
