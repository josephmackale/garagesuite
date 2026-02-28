{{-- resources/views/jobs/create/partials/step-2.blade.php --}}

@php
    $isModal = request()->boolean('modal');

    // Preserve wizard context
    $ctx = array_filter([
        'customer_id' => request('customer_id'),
        'vehicle_id'  => request('vehicle_id'),
        'draft'       => request('draft'),
        'modal'       => $isModal ? 1 : null,
    ], fn ($v) => $v !== null && $v !== '');


    $payerType = $payer_type ?? null;
    $payerData = $payer ?? [];

    // Auto-open optional section if user filled anything or errors exist
    $optionalPrefill = [
        old('excess_amount',  $payerData['excess_amount'] ?? ''),
        old('adjuster',       $payerData['adjuster'] ?? ''),
        old('adjuster_phone', $payerData['adjuster_phone'] ?? ''),
        old('notes',          $payerData['notes'] ?? ''),
        old('lpo_number',     $payerData['lpo_number'] ?? ''),
    ];

    $hasOptionalPrefill = collect($optionalPrefill)
        ->filter(fn($v) => trim((string)$v) !== '')
        ->isNotEmpty();

    $hasOptionalErrors =
        $errors->has('excess_amount') ||
        $errors->has('adjuster') ||
        $errors->has('adjuster_phone') ||
        $errors->has('notes') ||
        $errors->has('lpo_number');

    $openOptionalDefault = ($hasOptionalPrefill || $hasOptionalErrors);
@endphp


<div class="{{ $isModal ? 'p-0' : 'max-w-3xl mx-auto p-6' }}">
<div class="{{ $isModal ? 'px-6 pt-5' : '' }}">

@include('jobs.create._progress', ['current' => 'step2'])


<div class="mt-5 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

{{-- ================= HEADER ================= --}}
<div class="px-6 py-5 border-b border-slate-100">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">
                Payer Details
            </h1>

            <p class="text-sm text-slate-600 mt-1">
                @if($payerType === 'company')
                    Select the organization that will be billed.
                @elseif($payerType === 'insurance')
                    Capture insurer + policy + claim details before proceeding.
                @else
                    Individual jobs don’t need payer details.
                @endif
            </p>
        </div>

        <a href="{{ route('jobs.create.step1', $ctx) }}"
           class="text-sm text-indigo-600 font-semibold hover:text-indigo-700">
            Change type
        </a>
    </div>
</div>


{{-- ================= BODY ================= --}}
<div class="px-6 py-5">

@if($payerType === 'individual')

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
        Individual selected — payer details step is skipped.
    </div>

    <div class="mt-6 flex justify-end gap-3">

        @if($isModal)
            <button type="button"
                onclick="loadCreateJobStep('{{ route('jobs.create.step1', array_merge($ctx,['modal'=>1])) }}')"
                class="px-4 py-2 rounded-xl border border-slate-200">
                Back
            </button>

            <button type="button"
                onclick="closeCreateJobModal()"
                class="px-4 py-2 rounded-xl border border-slate-200">
                Close
            </button>

            <button type="button"
                onclick="loadCreateJobStep('{{ route('jobs.create.step3', array_merge($ctx,['modal'=>1])) }}')"
                class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold">
                Continue
            </button>
        @else
            <a href="{{ route('jobs.create.step3', $ctx) }}"
               class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold">
                Continue
            </a>
        @endif

    </div>

@else


<form method="POST"
      action="{{ route('jobs.create.step2.post', array_merge($ctx, ['draft' => ($ctx['draft'] ?? request('draft'))])) }}"
      class="space-y-6">

@csrf
<input type="hidden" name="draft" value="{{ $ctx['draft'] ?? request('draft') ?? '' }}">
<input type="hidden" name="payer_type" value="{{ $payerType }}">


{{-- ================= INSURANCE ================= --}}
@if($payerType === 'insurance')

<div class="space-y-5">

<div class="flex items-center justify-between">
    <div>
        <div class="text-sm font-semibold text-slate-900">
            Insurance Details
        </div>
        <div class="text-xs text-slate-500 mt-1">
            Capture claim identifiers.
        </div>
    </div>

    <span class="text-xs font-semibold text-slate-500 bg-slate-100 rounded-full px-3 py-1">
        Required
    </span>
</div>


<div class="rounded-2xl border border-slate-200 p-5 space-y-4">


{{-- Insurer --}}
<div>
    <label class="block text-sm font-medium mb-1">
        Insurer <span class="text-red-500">*</span>
    </label>

    <select name="insurer_id"
        class="w-full rounded-xl border-slate-200 focus:ring-indigo-400">

        <option value="">Select insurer</option>

        @foreach(($insurers ?? []) as $ins)
            <option value="{{ $ins->id }}"
                {{ (string)old('insurer_id', $payerData['insurance']['insurer_id'] ?? '') === (string)$ins->id ? 'selected' : '' }}>
                {{ $ins->name }}
            </option>
        @endforeach

    </select>

    @error('insurer_id')
        <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
    @enderror
</div>


{{-- Policy + Claim --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-3">

<div>
    <label class="block text-sm font-medium mb-1">
        Policy No <span class="text-red-500">*</span>
    </label>

    <input name="policy_no"
        value="{{ old('policy_no', data_get($payerData,'insurance.policy_number','')) }}"
        class="w-full rounded-xl border-slate-200 focus:ring-indigo-400"
        placeholder="e.g. POL-77823">

    @error('policy_no')
        <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
    @enderror
</div>


<div>
    <label class="block text-sm font-medium mb-1">
        Claim No <span class="text-red-500">*</span>
    </label>

    <input name="claim_no"
        value="{{ old('claim_no',  data_get($payerData,'insurance.claim_number','')) }}"
        class="w-full rounded-xl border-slate-200 focus:ring-indigo-400"
        placeholder="e.g. CLM-99112">

    @error('claim_no')
        <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
    @enderror
</div>

</div>



{{-- ========== OPTIONAL (COLLAPSIBLE) ========== --}}
<div x-data="{ open: {{ $openOptionalDefault ? 'true' : 'false' }} }">

<button type="button"
    @click="open = !open"
    class="w-full flex justify-between items-center rounded-xl border bg-slate-50 px-3 py-2">

    <div>
        <div class="text-xs font-semibold uppercase text-slate-700">
            Optional Details
        </div>
        <div class="text-[11px] text-slate-500">
            Excess, adjuster, notes.
        </div>
    </div>

    <span class="text-xs text-indigo-600 font-semibold">
        <span x-show="!open">Show</span>
        <span x-show="open" x-cloak>Hide</span>
    </span>
</button>


<div x-show="open" x-cloak class="mt-3">

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">


<div>
<label class="block text-sm mb-1">Excess Amount</label>
<input name="excess_amount"
    value="{{ old('excess_amount', $payerData['excess_amount'] ?? '') }}"
    class="w-full rounded-xl border-slate-200"
    placeholder="e.g. 10,000">
</div>


<div>
<label class="block text-sm mb-1">Adjuster Name</label>
<input name="adjuster"
    value="{{ old('adjuster', $payerData['adjuster'] ?? '') }}"
    class="w-full rounded-xl border-slate-200">
</div>


<div class="md:col-span-2">
<label class="block text-sm mb-1">Adjuster Phone</label>
<input name="adjuster_phone"
    value="{{ old('adjuster_phone', $payerData['adjuster_phone'] ?? '') }}"
    class="w-full rounded-xl border-slate-200">
</div>


<div class="md:col-span-2">
<label class="block text-sm mb-1">Notes</label>

<textarea name="notes" rows="3"
    class="w-full rounded-xl border-slate-200">{{ old('notes', $payerData['notes'] ?? '') }}</textarea>
</div>

</div>
</div>
</div>


</div>
</div>

@endif


{{-- ================= FOOTER ================= --}}
<div class="{{ $isModal ? 'sticky bottom-0 px-6 py-3 bg-white border-t border-slate-100' : 'pt-3' }}">

    <div class="flex items-center justify-between gap-3">

        @if($isModal)
            <button type="button"
                onclick="closeCreateJobModal()"
                class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50">
                Close
            </button>
        @else
            <a href="{{ route('jobs.create.step1', $ctx) }}"
               class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50">
                Back
            </a>
        @endif


        <button
            class="px-5 py-2 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400">
            Save &amp; Continue
        </button>

    </div>

</div>


</form>

@endif


</div>
</div>

</div>
</div>
