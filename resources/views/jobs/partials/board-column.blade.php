<div class="bg-white rounded-2xl border shadow-sm overflow-hidden">

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
                    KES {{ number_format($amount,2) }}
                </div>
            </div>
        </div>
    </div>

    {{-- DESKTOP --}}
    <div class="hidden lg:block p-3 space-y-2 max-h-[520px] overflow-y-auto">
        @forelse($jobs as $job)
            @include('jobs.partials.mobile.job-card', ['job' => $job])
        @empty
            <div class="text-center text-sm text-gray-500 py-6">
                No jobs here.
            </div>
        @endforelse
    </div>

    {{-- MOBILE --}}
    <div class="lg:hidden p-3 space-y-3">
        @forelse($jobs as $job)
            @include('jobs.partials.mobile.job-card', ['job' => $job])
        @empty
            <div class="text-center text-sm text-gray-500 py-6">
                No jobs here.
            </div>
        @endforelse
    </div>

</div>
