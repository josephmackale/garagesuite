{{-- Totals & Actions --}}
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div class="text-sm text-gray-500">
        Job totals will be recalculated when you save.
    </div>

    <div class="flex flex-col md:flex-row items-stretch md:items-center gap-4">
        <div class="flex items-center gap-4 text-sm">
            <div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">Labour</div>
                <div class="font-semibold text-gray-900">{{ number_format($labourTotal, 2) }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">Parts</div>
                <div class="font-semibold text-gray-900">{{ number_format($partsTotal, 2) }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">Total</div>
                <div class="font-semibold text-gray-900">{{ number_format($finalTotal, 2) }}</div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('jobs.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50">
                <x-lucide-x class="w-4 h-4" />
                Cancel
            </a>

            <x-primary-button class="inline-flex items-center gap-2">
                <x-lucide-save class="w-4 h-4" />
                {{ $submitLabel ?? 'Save' }}
            </x-primary-button>
        </div>
    </div>
</div>