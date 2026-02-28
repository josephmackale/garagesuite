{{-- Insurance payer fields --}}
<div class="space-y-3">
    <div class="text-sm font-semibold text-slate-700">
        Insurance Details
    </div>

    <div>
        <x-input-label for="insurer_id" value="Insurer" />
        <select
            id="insurer_id"
            name="insurer_id"
            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
        >
            <option value="">Select insurer...</option>

            @foreach(($insurers ?? []) as $ins)
                <option value="{{ $ins->id }}"
                    @selected(old('insurer_id', $job->insuranceDetail?->insurer_id ?? '') == $ins->id)
                >
                    {{ $ins->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('insurer_id')" class="mt-1" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <x-input-label for="policy_number" value="Policy Number" />
            <x-text-input
                id="policy_number"
                name="policy_number"
                type="text"
                class="mt-1 block w-full"
                value="{{ old('policy_number', $job->insuranceDetail?->policy_number ?? '') }}"
                placeholder="e.g. POL-12345"
            />
            <x-input-error :messages="$errors->get('policy_number')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="claim_number" value="Claim Number" />
            <x-text-input
                id="claim_number"
                name="claim_number"
                type="text"
                class="mt-1 block w-full"
                value="{{ old('claim_number', $job->insuranceDetail?->claim_number ?? '') }}"
                placeholder="e.g. CLM-67890"
            />
            <x-input-error :messages="$errors->get('claim_number')" class="mt-1" />
        </div>
    </div>
</div>