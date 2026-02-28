<div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
    <div class="p-4 border-b">
        <div class="flex justify-between">
            <div>
                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $meta['pill'] }}">
                    {{ $meta['label'] }}
                </span>
                <div class="mt-1 text-xs text-gray-500">{{ $meta['hint'] }}</div>
            </div>

            <div class="text-right">
                <div class="text-[11px] text-gray-500">Total</div>
                <div class="text-xs font-semibold">
                    KES {{ number_format($total, 2) }}
                </div>
            </div>
        </div>

        <a href="{{ route('jobs.index', array_filter(['q' => $q, 'status' => $key])) }}"
           class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-indigo-600">
            <x-lucide-search class="w-4 h-4" />
            View only {{ strtolower($meta['label']) }}
        </a>
    </div>

    <div class="p-3 space-y-2 max-h-[520px] overflow-y-auto">
        @forelse ($jobs as $job)
            @include('jobs.partials.job-card', compact('job'))
        @empty
            <div class="text-sm text-gray-500 text-center py-6">
                No jobs in {{ strtolower($meta['label']) }}.
            </div>
        @endforelse
    </div>
</div>
