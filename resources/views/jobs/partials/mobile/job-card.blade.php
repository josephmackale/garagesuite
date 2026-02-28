{{-- Mobile Job Card --}}
<div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-3">
        <div>
            <a href="{{ route('jobs.show', $job) }}"
               class="text-sm font-semibold text-gray-900 hover:text-indigo-700">
                {{ $job->job_number ?? ('Job #'.$job->id) }}
            </a>

            <div class="mt-1 text-xs text-gray-500">
                {{ strtoupper($job->vehicle?->registration_number ?? '—') }}
                @if($job->vehicle)
                    · {{ $job->vehicle->make }} {{ $job->vehicle->model }}
                @endif
            </div>

            <div class="mt-1 text-xs text-gray-500">
                {{ $job->customer?->name ?? $job->vehicle?->customer?->name ?? '—' }}
            </div>
        </div>

        {{-- Status pill --}}
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold
            @switch($job->status)
                @case('pending') bg-yellow-50 text-yellow-800 ring-1 ring-yellow-100 @break
                @case('in_progress') bg-blue-50 text-blue-800 ring-1 ring-blue-100 @break
                @case('completed') bg-green-50 text-green-800 ring-1 ring-green-100 @break
                @case('cancelled') bg-slate-50 text-slate-700 ring-1 ring-slate-100 @break
                @default bg-slate-50 text-slate-700
            @endswitch">
            {{ ucfirst(str_replace('_',' ',$job->status)) }}
        </span>
    </div>

    {{-- Amount + Actions --}}
    <div class="mt-4 flex items-center justify-between">
        <div class="text-sm font-semibold text-gray-900">
            KES {{ number_format((float)($job->final_cost ?? 0), 2) }}
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('jobs.show', $job) }}"
               class="inline-flex items-center gap-1 text-xs font-semibold text-gray-700">
                <x-lucide-eye class="w-4 h-4" />
                Open
            </a>

            @if($job->status === 'pending')
                <form method="POST" action="{{ route('jobs.status.update', $job) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="in_progress">
                    <button type="submit"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600">
                        <x-lucide-play class="w-4 h-4" />
                        Start
                    </button>
                </form>
            @elseif($job->status === 'in_progress')
                <form method="POST" action="{{ route('jobs.status.update', $job) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="completed">
                    <button type="submit"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600">
                        <x-lucide-check class="w-4 h-4" />
                        Complete
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Date --}}
    <div class="mt-3 flex items-center gap-2 text-[11px] text-gray-500">
        <x-lucide-calendar class="w-4 h-4 text-gray-400" />
        <span>{{ optional($job->job_date ?? $job->created_at)->format('d M Y') }}</span>
    </div>
</div>
