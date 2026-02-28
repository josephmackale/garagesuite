<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">
                    Insurance Case #{{ $job->job_number ?? $job->id }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $job->customer->name ?? 'N/A' }}
                    @if(!empty($job->vehicle?->registration))
                        <span class="text-gray-400">·</span> {{ $job->vehicle->registration }}
                    @endif
                </p>
            </div>

            <a href="{{ route('jobs.index', ['status' => 'pending']) }}"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                <x-lucide-arrow-left class="w-4 h-4" />
                Back to Jobs
            </a>
        </div>
    </x-slot>
    
    @php
        // ✅ Phase 4/5: Blade must NOT recompute workflow logic.
        // Only read truth already calculated by InsuranceGate + DB fields.

        $canEditQuotation = (bool) data_get($gates ?? [], 'quotation_editable', false);

        // ✅ DB truth (via InsuranceGate): inspection complete is from job_inspections.status='completed'
        // (avoid relying on jobs.inspection_completed_at if it's not guaranteed to be synced)
        $inspectionDone = (bool) data_get($gates ?? [], 'inspection_complete', false);

        $quoteSubmitted = (bool) data_get($gates ?? [], 'quote_submitted', false);
        $approvalStatus = (string) data_get($gates ?? [], 'approval.status', 'draft');
        $isApproved     = ($approvalStatus === 'approved');

        $repairStarted  = (bool) data_get($gates ?? [], 'repair_started', false);

        // ✅ Settlement truth (Invoice + Payments)
        $hasInvoice     = (bool) data_get($gates ?? [], 'has_invoice', false);
        $invoicePaid    = (bool) data_get($gates ?? [], 'invoice_paid', false);

        // ✅ Pack existence (already passed from controller)
        $hasPack = !empty(data_get($pack ?? null, 'id'));

        // ✅ Context used by cards/routes
        $ctx = ['job' => $job->id];

        // ✅ Current phase must follow DB truth only
        // For the 4-step stepper (Inspection → Quotation → Approval → Settlement),
        // Settlement must win as soon as an invoice exists.
        if ($hasInvoice) {
            $currentPhase = 'settlement';
        } elseif ($isApproved) {
            $currentPhase = 'approval';
        } elseif ($inspectionDone) {
            $currentPhase = 'quotation';
        } else {
            $currentPhase = 'inspection';
        }
    @endphp


    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6" data-job-id="{{ (int) $job->id }}">

            {{-- Stepper (simple + accurate for now) --}}
            <div class="bg-white rounded-lg border border-gray-100 p-4">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    {{-- Inspection --}}
                    <span class="inline-flex items-center rounded-full px-3 py-1 font-semibold
                        {{ $currentPhase === 'inspection'
                            ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100'
                            : ($inspectionDone
                                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'
                                : 'bg-gray-50 text-gray-500 ring-1 ring-gray-100') }}">
                        Inspection
                    </span>

                    <span class="text-gray-300">→</span>

                    {{-- Quotation --}}
                    <span class="inline-flex items-center rounded-full px-3 py-1 font-semibold
                        {{ $currentPhase === 'quotation'
                            ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100'
                            : ($inspectionDone
                                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'
                                : 'bg-gray-50 text-gray-500 ring-1 ring-gray-100') }}">
                        Quotation
                    </span>

                    <span class="text-gray-300">→</span>

                    {{-- Approval --}}
                    <span class="inline-flex items-center rounded-full px-3 py-1 font-semibold
                        {{ $currentPhase === 'approval'
                            ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100'
                            : ($isApproved
                                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'
                                : 'bg-gray-50 text-gray-500 ring-1 ring-gray-100') }}">
                        Approval
                    </span>

                    <span class="text-gray-300">→</span>

                    {{-- Settlement --}}
                    <span class="inline-flex items-center rounded-full px-3 py-1 font-semibold
                        {{ $currentPhase === 'settlement'
                            ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100'
                            : ($invoicePaid
                                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'
                                : ($hasInvoice
                                    ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-100'
                                    : 'bg-gray-50 text-gray-500 ring-1 ring-gray-100')) }}">
                        Settlement
                    </span>
                </div>

                <p class="mt-2 text-xs text-gray-500">
                    Rule: Inspection must be completed before quotation. Approval must be granted before repair.
                </p>
            </div>

            {{-- Inspection (always visible for insurance) --}}
            <div class="bg-white rounded-lg border border-gray-100 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Inspection</h3>
                        <p class="mt-1 text-xs text-gray-500">
                            Photos + checklist. Minimum photos required before completion.
                        </p>
                    </div>

                    @if($inspectionDone)
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                            Completed
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">
                            In progress
                        </span>
                    @endif
                </div>

                <div class="mt-4">
                    {{-- ✅ Plug your real inspection module here --}}
                    @include('jobs.insurance.inspection.card', [
                    'job' => $job,
                    'inspection' => $inspection ?? null,
                    'ctx' => $ctx,
                    ])
                </div>
            </div>

            {{-- Quotation (gated) --}}
            <div class="bg-white rounded-lg border border-gray-100 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Quotation</h3>
                        <p class="mt-1 text-xs text-gray-500">
                            Build the estimate after inspection is complete.
                        </p>
                    </div>

                    @if(!$inspectionDone)
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-gray-50 text-gray-600 ring-1 ring-gray-100">
                            Locked (finish inspection)
                        </span>
                    @endif
                </div>

                <div class="mt-4">
                    <div id="insurance-quotation-card">
                        @if(!$inspectionDone)
                            <div class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-600">
                                Complete inspection first to unlock quotation.
                            </div>
                        @else
                            @include('jobs.insurance.quotation.card', [
                                'job' => $job,
                                'quote' => $quote ?? null,
                                'ctx' => $ctx,
                                'gates' => $gates ?? [],
                                'inspection' => $inspection ?? null,
                                'inspectionDone' => $inspectionDone,
                            ])
                        @endif
                    </div>
                </div>
            </div>

            {{-- Approval (gated by quotation submission later, but visible as a panel) --}}
            <div id="insurance-approval-card">
                <div class="bg-white rounded-lg border border-gray-100 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Approval</h3>
                            <p class="mt-1 text-xs text-gray-500">
                                Submit quotation for approval, then approve/reject with reference.
                            </p>
                        </div>

                        <div class="flex flex-col items-end gap-2">
                            @php
                                // Canonical approval state — ONLY from approval_packs
                                $approvalStatus = data_get($gates ?? [], 'approval.status', 'draft');
                            @endphp

                            @if($approvalStatus === 'approved')
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                                    Approved
                                </span>
                            @elseif($approvalStatus === 'submitted')
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-blue-50 text-blue-700 ring-1 ring-blue-100">
                                    Submitted
                                </span>
                            @elseif($approvalStatus === 'rejected')
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-red-50 text-red-700 ring-1 ring-red-100">
                                    Rejected
                                </span>
                            @else {{-- draft --}}
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-gray-50 text-gray-600 ring-1 ring-gray-100">
                                    Not submitted
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4">
                        @include('jobs.insurance.approval.panel', [
                            'job'          => $job,
                            'pack'         => $pack ?? null,
                            'packShareUrl' => $packShareUrl ?? null,
                            'gates'        => $gates ?? [],
                        ])
                    </div>
                </div>
            </div>
            
            {{-- Repair Card (Includes Start, Items, and Complete) --}}
            <div id="insurance-repair-card" class="bg-white rounded-lg border border-gray-100 p-5">
                @include('jobs.insurance.repair.card', [
                    'job'           => $job,
                    'pack'          => $pack ?? null,
                    'gates'         => $gates ?? [],
                    'repairSession' => $repairSession ?? null,
                    'repairItems'   => $repairItems ?? collect(),
                    'technicians'   => $technicians ?? collect(),
                ])
            </div>

            {{-- Completion Card (Unlocked after Repair completed) --}}
            @if(($gates['completion_unlocked'] ?? false) === true)
                <div class="bg-white rounded-lg border border-gray-100 p-5 mt-4">
                    @include('jobs.insurance.completion.card', [
                        'job'   => $job,
                        'gates' => $gates ?? [],
                    ])
                </div>
            @endif

            {{-- Settlement Card (binds to Invoice + Payments truth) --}}
            @if(($gates['settlement_unlocked'] ?? false) === true)
                <div class="bg-white rounded-lg border border-gray-100 p-5 mt-4">
                    @include('jobs.insurance.settlement.card', [
                        'job'      => $job,
                        'gates'    => $gates ?? [],
                        'invoice'  => $invoice ?? null,
                        'payments' => $payments ?? collect(),
                    ])
                </div>
            @endif
        </div> {{-- max-w-7xl --}}
    </div> {{-- py-6 --}}
</x-app-layout>
