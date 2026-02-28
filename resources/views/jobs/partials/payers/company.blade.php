{{-- Company payer fields --}}
<div class="space-y-3">
    <div class="text-sm font-semibold text-slate-700">
        Company / Organization
    </div>

    <div>
        <x-input-label for="organization_id" value="Organization" />
        <select
            id="organization_id"
            name="organization_id"
            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
        >
            <option value="">Select organization...</option>

            @foreach(($organizations ?? []) as $org)
                <option value="{{ $org->id }}"
                    @selected(old('organization_id', $job->organization_id ?? '') == $org->id)
                >
                    {{ $org->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('organization_id')" class="mt-1" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <x-input-label for="company_ref" value="Company Ref (optional)" />
            <x-text-input
                id="company_ref"
                name="company_ref"
                type="text"
                class="mt-1 block w-full"
                value="{{ old('company_ref', $job->company_ref ?? '') }}"
                placeholder="e.g. PO / LPO / Ref No."
            />
            <x-input-error :messages="$errors->get('company_ref')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="billing_terms" value="Billing Terms (optional)" />
            <x-text-input
                id="billing_terms"
                name="billing_terms"
                type="text"
                class="mt-1 block w-full"
                value="{{ old('billing_terms', $job->billing_terms ?? '') }}"
                placeholder="e.g. Net 7, Net 30"
            />
            <x-input-error :messages="$errors->get('billing_terms')" class="mt-1" />
        </div>
    </div>
</div>
