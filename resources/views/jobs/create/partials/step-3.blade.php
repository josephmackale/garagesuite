{{-- ===================================================================
 |  DROP-IN: Step 3 (Job Details Gate → Shared Partials)
 |  ✅ FIX: submitCreateJob() scope issue (form was outside x-data)
 |  ✅ Keeps your Insurance Inspection mount position (after gate, before shared partials)
 =================================================================== --}}

@php
    use App\Models\Job;

    // Prevent undefined-variable crashes when loaded via AJAX/modal
    $isModal  = $isModal ?? request()->boolean('modal');
    $ctx      = $ctx ?? [];
    $ctxModal = $ctxModal ?? array_merge($ctx, ['modal' => 1]);



    $ctx = $ctx ?? array_filter([
        'customer_id' => request('customer_id'),
        'vehicle_id'  => request('vehicle_id'),
        'modal'       => request('modal'),
    ], fn($v) => $v !== null && $v !== '');

    $payerType = $payer_type ?? null;
    $payerArr  = is_array($payer ?? null) ? $payer : [];

    $isInsuranceFullPage = (($payerType === 'insurance') && !request()->boolean('modal') && empty($modal));
    $isInsuranceModal    = (($payerType === 'insurance') && request()->boolean('modal'));

    $job = $job ?? new Job();

    $customerId        = request('customer_id');
    $selectedVehicleId = request('vehicle_id');

    $partItems = old('part_items', []);
    if ($partItems === null) $partItems = [];

    $labourTotal = (float) old('labour_cost', $job->labour_cost ?? 0);

    $partsTotal = 0.0;
    foreach ($partItems as $row) {
        $qty  = (float) ($row['quantity'] ?? 0);
        $unit = (float) ($row['unit_price'] ?? 0);
        $lt   = $row['line_total'] ?? null;
        $partsTotal += is_numeric($lt) ? (float)$lt : ($qty * $unit);
    }
    $finalTotal = $labourTotal + $partsTotal;

    $inventoryForParts = $inventoryForParts ?? [];

    $defaultJobDate = old('job_date', now()->format('Y-m-d'));
    $defaultMileage = old('mileage', '');
    $defaultService = old('service_type', '');

    $shouldUnlockOnLoad = (
        (string)$defaultJobDate !== '' &&
        (string)$defaultMileage !== '' &&
        (string)$defaultService !== ''
    );

    if ($isInsuranceFullPage) {
        $shouldUnlockOnLoad = false;
    }

    $inspection = $inspection ?? null;
    $draft      = $draft ?? null;

    // Prepare insurance fields safely (used in x-data)
    $insurerOrgId  = '';
    $policyNo      = '';
    $claimNo       = '';
    $adjusterName  = '';
    $excessAmount  = '';
    $adjusterPhone = '';
    $notes         = '';

    if ($payerType === 'insurance') {
        $insurance = $payerArr['insurance'] ?? [];

        $insurerOrgId  = old('organization_id',           $insurance['insurer_id']     ?? null);
        $policyNo      = old('insurance.policy_number',   $insurance['policy_number']  ?? null);
        $claimNo       = old('insurance.claim_number',    $insurance['claim_number']   ?? null);
        $adjusterName  = old('insurance.adjuster_name',   $insurance['adjuster_name']  ?? null);

        $excessAmount  = old('insurance.excess_amount',   $insurance['excess_amount']  ?? '');
        $adjusterPhone = old('insurance.adjuster_phone',  $insurance['adjuster_phone'] ?? '');
        $notes         = old('insurance.notes',           $insurance['notes']          ?? '');
    }
@endphp

<div class="{{ $isModal ? 'p-0' : 'max-w-6xl mx-auto p-6' }}">
    @include('jobs.create._progress', ['current' => 'step3'])

    @php
        $isModal  = request()->boolean('modal');
        $ctxModal = array_merge($ctx, $isModal ? ['modal' => 1] : []);
    @endphp

    {{-- Top right actions --}}
    <div class="{{ $isModal ? 'mt-3' : 'mt-6' }} flex items-center justify-end gap-3">
        @if(in_array($payerType, ['company','insurance'], true))
            @if($isModal)
                <button type="button"
                        onclick="loadCreateJobStep('{{ route('jobs.create.step2', $ctxModal) }}')"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Back
                </button>
            @else
                <a href="{{ route('jobs.create.step2', $ctx) }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Back
                </a>
            @endif
        @else
            @if($isModal)
                <button type="button"
                        onclick="loadCreateJobStep('{{ route('jobs.create.step1', $ctxModal) }}')"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Change type
                </button>
            @else
                <a href="{{ route('jobs.create.step1', $ctx) }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Change type
                </a>
            @endif
        @endif

        @if($isModal)
            <button type="button"
                    onclick="closeCreateJobModal()"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Close
            </button>
        @endif
    </div>

    {{-- ============================================================
        Step 3 FORM
        ✅ FIX: x-data is now on the FORM so submitCreateJob() is in scope
    ============================================================ --}}

    <form method="POST"
          action="{{ route('jobs.create.step3.post', $ctx) }}"
          class="mt-6 space-y-8"
          id="createJobForm"
          x-ref="createJobForm"
            @inspection:completed.window="
                lockedByInspection = true;
                !isModal && document.getElementById('insurance-quotation')
                    ?.scrollIntoView({ behavior: 'smooth', block: 'start' })
            "


          x-data="{
                lockedByInspection: false,
                unlocked: @js($shouldUnlockOnLoad),

                jobDate: @js($defaultJobDate),
                mileage: @js($defaultMileage),
                serviceType: @js($defaultService),

                isModal: @js($isModal),
                isInsuranceModal: @js($isInsuranceModal),

                insurerOrgId: @js($insurerOrgId ?? ''),
                policyNo: @js($policyNo ?? ''),
                claimNo: @js($claimNo ?? ''),
                adjusterName: @js($adjusterName ?? ''),
                excessAmount: @js($excessAmount ?? ''),
                adjusterPhone: @js($adjusterPhone ?? ''),
                notes: @js($notes ?? ''),

                errors: {},
                submitting: false,

                init() {
                    // Force unlock on full-page (non-modal, non-insurance)
                    if (!this.isModal && !this.isInsuranceModal) {
                        this.unlocked = true;
                    }
                },


                syncInsuranceHidden() {
                    if (!this.isInsuranceModal) return;

                    const setVal = (id, val) => {
                        const el = document.getElementById(id);
                        if (el) el.value = val ?? '';
                    };

                    setVal('insurer_org_id_hidden', this.insurerOrgId);
                    setVal('policy_no_hidden', this.policyNo);
                    setVal('claim_no_hidden', this.claimNo);
                    setVal('adjuster_name_hidden', this.adjusterName);
                    setVal('excess_amount_hidden', this.excessAmount);
                    setVal('adjuster_phone_hidden', this.adjusterPhone);
                    setVal('notes_hidden', this.notes);
                },

                validateGate() {
                    this.errors = {};

                    if (this.isInsuranceModal) {
                        if (!this.insurerOrgId || String(this.insurerOrgId).trim() === '') {
                            this.errors.insurer = 'Insurer is required.';
                        }
                        if (!this.policyNo || String(this.policyNo).trim() === '') {
                            this.errors.policy_number = 'Policy number is required.';
                        }
                        if (!this.claimNo || String(this.claimNo).trim() === '') {
                            this.errors.claim_number = 'Claim number is required.';
                        }
                    }

                    if (!this.jobDate || String(this.jobDate).trim() === '') {
                        this.errors.job_date = 'Job date is required.';
                    }
                    if (!this.mileage || String(this.mileage).trim() === '') {
                        this.errors.mileage = 'Mileage is required.';
                    }
                    if (!this.serviceType || String(this.serviceType).trim() === '') {
                        this.errors.service_type = 'Service type is required.';
                    }

                    return Object.keys(this.errors).length === 0;
                },

                unlockRest() {
                    this.syncInsuranceHidden();
                    if (!this.validateGate()) return;

                    this.unlocked = true;

                    const flag = document.getElementById('unlockFlag');
                    if (flag) flag.value = '1';

                    this.$nextTick(() => {
                        const el = document.getElementById('job-rest');
                        if (el) setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'start' }), 0);
                    });
                },

                async submitCreateJob() {
                    // Full-page? allow normal submit
                    if (!this.isModal) {
                        this.syncInsuranceHidden();
                        this.$refs.createJobForm.submit();
                        return;
                    }

                    // Modal: AJAX submit (expects JSON next_url)
                    this.syncInsuranceHidden();
                    if (!this.validateGate()) return;

                    if (this.submitting) return;
                    this.submitting = true;

                    try {
                        const form = this.$refs.createJobForm;
                        const fd = new FormData(form);

                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: fd,
                            credentials: 'same-origin',
                        });

                        // If server returns validation HTML, fallback to replace modal with HTML
                        const ct = (res.headers.get('content-type') || '').toLowerCase();

                        if (ct.includes('application/json')) {
                            const data = await res.json().catch(() => ({}));

                            if (!res.ok) {
                                // best-effort: map laravel validation errors into this.errors
                                const ve = data?.errors || {};
                                if (ve && typeof ve === 'object') {
                                    Object.keys(ve).forEach(k => {
                                        this.errors[k] = Array.isArray(ve[k]) ? ve[k][0] : ve[k];
                                    });
                                }
                                return;
                            }

                            const nextUrl = data?.next_url || data?.nextUrl || data?.url;
                            if (nextUrl) {
                                // Use your existing modal loader
                                if (typeof loadCreateJobStep === 'function') {
                                    loadCreateJobStep(nextUrl);
                                } else {
                                    window.location.href = nextUrl;
                                }
                                return;
                            }

                            // No next url => do nothing
                            return;
                        }

                        // Non-JSON: treat as HTML fragment, replace modal content if possible
                        const html = await res.text();

                        const root =
                            document.querySelector('#create-job-modal-content') ||
                            document.querySelector('[data-create-job-modal-body]') ||
                            document.querySelector('#createJobModalBody');

                        if (root) {
                            root.innerHTML = html;

                            // Re-init Alpine on newly injected content
                            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                                window.Alpine.initTree(root);
                            }
                        } else {
                            // As a last resort, replace the whole document (shouldn't happen)
                            document.open(); document.write(html); document.close();
                        }
                    } finally {
                        this.submitting = false;
                    }
                }
          }"
          @submit.prevent="submitCreateJob()"
    >
        @csrf

        {{-- keep wizard context --}}
        @if(!empty($customerId))
            <input type="hidden" name="customer_id" value="{{ $customerId }}">
        @endif

        @if(!empty($selectedVehicleId))
            <input type="hidden" name="vehicle_id" value="{{ $selectedVehicleId }}">
        @endif

        <input type="hidden" name="payer_type" value="{{ $payerType }}">

        <input type="hidden"
               name="unlocked_job_rest"
               value="{{ old('unlocked_job_rest', $shouldUnlockOnLoad ? 1 : 0) }}"
               id="unlockFlag" />

        @if($isInsuranceFullPage)
            <input type="hidden" name="modal" value="0">
        @endif

        @if($payerType === 'company')
            <input type="hidden" name="organization_id" value="{{ old('organization_id', $payerArr['organization_id'] ?? null) }}">
        @endif

        @if($payerType === 'insurance')
            {{-- legacy store expects insurer in organization_id --}}
            <input type="hidden" id="insurer_org_id_hidden" name="organization_id" value="{{ $insurerOrgId }}">

            {{-- legacy store expects insurance[...] --}}
            <input type="hidden" id="policy_no_hidden" name="insurance[policy_number]" value="{{ $policyNo }}">
            <input type="hidden" id="claim_no_hidden"  name="insurance[claim_number]"  value="{{ $claimNo }}">
            <input type="hidden" id="adjuster_name_hidden" name="insurance[adjuster_name]" value="{{ $adjusterName }}">

            <input type="hidden" id="excess_amount_hidden"  name="insurance[excess_amount]"  value="{{ $excessAmount }}">
            <input type="hidden" id="adjuster_phone_hidden" name="insurance[adjuster_phone]" value="{{ $adjusterPhone }}">
            <input type="hidden" id="notes_hidden"          name="insurance[notes]"          value="{{ $notes }}">
        @endif

        <div class="space-y-6">
            {{-- ✅ Insurance Modal Gate Card --}}
            @if($payerType === 'insurance')
                <template x-if="isInsuranceModal">
                    <div class="rounded-xl border border-slate-200 bg-white p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Insurance Details</div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    Complete these before job fields unlock.
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Insurer (Org ID)</label>
                                <input type="text"
                                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       x-model="insurerOrgId"
                                       @input="syncInsuranceHidden()"
                                       placeholder="e.g. 12">
                                <template x-if="errors.insurer">
                                    <div class="mt-1 text-xs text-red-600" x-text="errors.insurer"></div>
                                </template>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Policy Number</label>
                                <input type="text"
                                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       x-model="policyNo"
                                       @input="syncInsuranceHidden()"
                                       placeholder="Policy number">
                                <template x-if="errors.policy_number">
                                    <div class="mt-1 text-xs text-red-600" x-text="errors.policy_number"></div>
                                </template>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Claim Number</label>
                                <input type="text"
                                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       x-model="claimNo"
                                       @input="syncInsuranceHidden()"
                                       placeholder="Claim number">
                                <template x-if="errors.claim_number">
                                    <div class="mt-1 text-xs text-red-600" x-text="errors.claim_number"></div>
                                </template>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Adjuster Name (optional)</label>
                                <input type="text"
                                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       x-model="adjusterName"
                                       @input="syncInsuranceHidden()"
                                       placeholder="Adjuster name">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Adjuster Phone (optional)</label>
                                <input type="text"
                                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       x-model="adjusterPhone"
                                       @input="syncInsuranceHidden()"
                                       placeholder="Phone">
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Notes (optional)</label>
                            <textarea rows="3"
                                      class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                      x-model="notes"
                                      @input="syncInsuranceHidden()"
                                      placeholder="Notes"></textarea>
                        </div>
                    </div>
                </template>
            @endif

            {{-- Gate Card --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Job Basics</div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            @if($isInsuranceFullPage)
                                Enter job basics, then save to unlock the rest of the job card.
                            @else
                                Job Date + Mileage + Service Type unlock the rest of the job card.
                            @endif
                        </div>
                    </div>

                    {{-- Action Button --}}
                    @if(!$isInsuranceFullPage)
                        <button type="button"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800"
                                @click="unlockRest()">
                            Continue
                        </button>
                    @endif

                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Job Date</label>
                        <input type="date"
                            name="job_date"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            x-model="jobDate"
                            required>

                        <template x-if="errors.job_date">
                            <div class="mt-1 text-xs text-red-600" x-text="errors.job_date"></div>
                        </template>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Mileage</label>
                        <input type="number"
                            name="mileage"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="e.g. 120000"
                            x-model="mileage">

                        <template x-if="errors.mileage">
                            <div class="mt-1 text-xs text-red-600" x-text="errors.mileage"></div>
                        </template>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Service Type</label>
                        <input type="text"
                            name="service_type"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="e.g. Major Service, Brake Repair"
                            x-model="serviceType">

                        <template x-if="errors.service_type">
                            <div class="mt-1 text-xs text-red-600" x-text="errors.service_type"></div>
                        </template>
                    </div>
                </div>
            </div>


            {{-- ✅ NEW: Insurance Inspection mounts AFTER gate, BEFORE shared partials --}}
            @if($payerType === 'insurance')
                <div x-show="{{ $isInsuranceFullPage ? 'true' : 'unlocked' }}" x-transition class="space-y-6">
                    @include('jobs.create.partials._insurance_inspection', [
                        'draft'      => $draft,
                        'inspection' => $inspection,
                        'ctx'        => $ctx,
                    ])

                    {{-- ✅ ADD THIS: Quotation card right after inspection --}}
                    @include('jobs.insurance.quotation.card', [
                        'job'       => $job ?? null,
                        'inspection'=> $inspection ?? null,
                        'quote'     => $quote ?? null,   {{-- if not available yet, fine --}}
                    ])
                </div>
            @endif

            {{-- Unlocked content --}}
            @if(!$isInsuranceFullPage)
                <div x-show="unlocked" x-transition id="job-rest" class="space-y-8">
                    @include('jobs.partials.shared.work_details')
                    @include('jobs.partials.shared.work_done_and_labour')
                    @include('jobs.partials.shared.parts', [
                        'partItems' => $partItems,
                        'inventoryForParts' => $inventoryForParts
                    ])
                    @include('jobs.partials.shared.totals_and_actions', [
                        'labourTotal' => $labourTotal,
                        'partsTotal'  => $partsTotal,
                        'finalTotal'  => $finalTotal,
                    ])
                </div>
            @endif
        </div>
        {{-- Sticky footer actions (MODAL only) --}}
        @if($isModal)
            <div class="sticky bottom-0 -mx-6 mt-8 px-6 py-4 bg-white border-t border-slate-100">
                <div class="flex items-center justify-between gap-3">
                    <button type="button"
                            onclick="loadCreateJobStep('{{ route('jobs.create.step2', $ctxModal) }}')"
                            class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Back
                    </button>

                    <div class="flex items-center gap-3">
                        <button type="button"
                                onclick="closeCreateJobModal()"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Close
                        </button>

                        <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                                :disabled="submitting"
                                @click="submitting = true; syncInsuranceHidden()">
                            Save & Continue
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </form>
</div>
