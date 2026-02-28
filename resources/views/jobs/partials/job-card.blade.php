@php
    $st = $job->status ?? 'pending';

    $next = match ($st) {
        'pending' => 'in_progress',
        'in_progress' => 'completed',
        default => null,
    };
@endphp

<div class="rounded-xl border bg-white p-3 hover:border-indigo-200 hover:shadow-sm transition">
    <div class="flex justify-between gap-2">
        <div>
            <a href="{{ route('jobs.show', $job) }}"
               class="text-sm font-semibold text-gray-900 hover:text-indigo-700">
                {{ $job->job_number ?? ('Job #'.$job->id) }}
            </a>

            <div class="mt-1 text-xs text-gray-500">
                {{ strtoupper($job->vehicle?->registration_number ?? '—') }}
                · {{ $job->vehicle?->make }} {{ $job->vehicle?->model }}
            </div>

            <div class="mt-1 text-xs text-gray-500">
                {{ $job->customer?->name ?? '—' }}
            </div>
        </div>

        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-gray-100">
            {{ ucfirst(str_replace('_', ' ', $st)) }}
        </span>
    </div>

    <div class="mt-3 flex justify-between items-center">
        <div class="text-xs font-semibold">
            KES {{ number_format($job->final_cost ?? 0, 2) }}
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('jobs.show', $job) }}"
               class="inline-flex items-center gap-1 text-xs text-gray-700 hover:text-indigo-700">
                <x-lucide-eye class="w-4 h-4" />
                Open
            </a>

            @if ($next)
                <form method="POST" action="{{ route('jobs.status.update', $job) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $next }}">
                    <button class="inline-flex items-center gap-1 text-xs text-indigo-600">
                        <x-lucide-arrow-right class="w-4 h-4" />
                        {{ $next === 'in_progress' ? 'Start' : 'Complete' }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="mt-2 text-[11px] text-gray-500 flex items-center gap-1">
        <x-lucide-calendar class="w-4 h-4" />
        {{ optional($job->job_date ?? $job->created_at)->format('d M Y') }}
    </div>
</div>
