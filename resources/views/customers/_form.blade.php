@csrf

{{-- Single-column customer form (drop-in replacement) --}}
<div class="space-y-4">

    {{-- Name --}}
    <div>
        <label class="block text-sm font-medium text-gray-800 mb-1">
            Name <span class="text-red-500">*</span>
        </label>
        <input
            type="text"
            name="name"
            required
            autocomplete="name"
            value="{{ old('name', $customer->name ?? '') }}"
            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                   focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-200"
            placeholder="e.g. John Mwangi"
        >
    </div>

    {{-- Phone --}}
    <div>
        <label class="block text-sm font-medium text-gray-800 mb-1">
            Phone <span class="text-red-500">*</span>
        </label>

        <div class="flex rounded-xl overflow-hidden border border-slate-200 bg-white
                    focus-within:ring-2 focus-within:ring-indigo-500/20 focus-within:border-indigo-300">

            {{-- Prefix (Flag + Code + Chevron) --}}
            <div class="flex items-center gap-2 px-3 py-3 bg-slate-50 border-r border-slate-200 shrink-0">
                <span class="text-base leading-none">🇰🇪</span>

                {{-- Keep ONLY one "KE" display (no duplicates) --}}
                <span class="text-xs font-semibold text-slate-600 tracking-wide">KE</span>

                <div class="relative">
                    <select
                        id="phone_country"
                        class="bg-transparent text-sm font-semibold text-slate-900 border-0 p-0
                            focus:ring-0 focus:outline-none appearance-none pr-6"
                    >
                        {{-- IMPORTANT: label must be ONLY +254 (so KE doesn't repeat) --}}
                        <option value="+254" selected>+254</option>
                    </select>

                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="w-4 h-4 text-slate-500 absolute right-0 top-1/2 -translate-y-1/2 pointer-events-none"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            {{-- Local number --}}
            <input
                type="text"
                id="phone_local"
                inputmode="tel"
                autocomplete="tel"
                required
                class="w-full px-4 py-3 text-sm text-slate-900 border-0
                    focus:ring-0 focus:outline-none placeholder:text-slate-400"
                placeholder="7XXXXXXXX"
                value="{{ preg_replace('/^\+254/', '', old('phone', $customer->phone ?? '')) }}"
            >
        </div>

        <p class="mt-1 text-xs text-gray-500">Enter number without country code (e.g. 7XXXXXXXX).</p>

        {{-- Hidden combined phone field that your backend expects --}}
        <input type="hidden" name="phone" id="phone_full" value="{{ old('phone', $customer->phone ?? '') }}">
    </div>




    {{-- Email (optional) --}}
    <div>
        <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-gray-800 mb-1">Email</label>
            <span class="text-xs text-gray-500">Optional</span>
        </div>
        <input
            type="email"
            name="email"
            autocomplete="email"
            value="{{ old('email', $customer->email ?? '') }}"
            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                   focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400"
            placeholder="e.g. name@email.com"
        >
    </div>

    {{-- Address (optional) --}}
    <div>
        <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-gray-800 mb-1">Address</label>
            <span class="text-xs text-gray-500">Optional</span>
        </div>
        <input
            type="text"
            name="address"
            value="{{ old('address', $customer->address ?? '') }}"
            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                   focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400"
            placeholder="e.g. Nairobi, Westlands"
        >
    </div>

    {{-- Notes (optional) --}}
    <div>
        <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-gray-800 mb-1">Notes</label>
            <span class="text-xs text-gray-500">Optional</span>
        </div>
        <textarea
            name="notes"
            rows="2"
            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                   focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-400"
            placeholder="Any quick notes about this customer..."
        >{{ old('notes', $customer->notes ?? '') }}</textarea>
    </div>

</div>

{{-- Actions --}}
<div class="mt-7 flex flex-col items-center gap-3">
    <button
        type="submit"
        class="w-full max-w-sm inline-flex items-center justify-center gap-2
            px-6 py-3 rounded-lg
            bg-indigo-600 text-white text-sm font-semibold
            shadow-sm hover:shadow-md hover:-translate-y-[1px]
            hover:bg-indigo-700 active:bg-indigo-800 active:translate-y-0
            transition
            focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ $buttonLabel }}
    </button>


    <button type="button" id="closeCustomerCreateBottom" class="text-sm text-gray-600 hover:underline">
        Cancel
    </button>
</div>

