@props([
    'action' => null,          // form action URL or route() result
    'queryName' => 'q',         // input name
    'queryValue' => null,       // current query value
    'placeholder' => 'Search',
    'showFilters' => true,      // show the filters button
    'createHref' => null,       // button link
    'createLabel' => 'ADD',     // button text
    'createColor' => 'indigo',  // indigo | emerald | rose | slate etc (Tailwind)
])

@php
    // Safe defaults
    $action     = $action ?? url()->current();
    $queryValue = $queryValue ?? request($queryName);

    // Tailwind button colors (limited, safe mapping)
    $btn = match ($createColor) {
        'emerald' => 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500',
        'rose'    => 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500',
        'slate'   => 'bg-slate-800 hover:bg-slate-900 focus:ring-slate-500',
        default   => 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500',
    };
@endphp

<div class="max-w-6xl mx-auto px-3 sm:px-4 lg:px-6">
    <div class="flex items-center gap-3">

        {{-- Search --}}
        <form method="GET" action="{{ $action }}" class="flex-1">
            <div class="flex items-center h-11 rounded-full border border-gray-200 bg-white shadow-sm px-4">
                <x-lucide-search class="w-4 h-4 text-gray-400 shrink-0" />

                <input
                    type="text"
                    name="{{ $queryName }}"
                    value="{{ $queryValue }}"
                    placeholder="{{ $placeholder }}"
                    class="ml-3 w-full h-full bg-transparent border-0 p-0 text-sm text-gray-900
                           placeholder:text-gray-400 focus:ring-0 focus:outline-none"
                >
            </div>
        </form>

        {{-- Filters --}}
        @if($showFilters)
            <button type="button"
                    class="h-11 w-11 inline-flex items-center justify-center rounded-2xl
                           border border-gray-200 bg-white shadow-sm
                           text-gray-700 hover:bg-gray-50 active:bg-gray-100"
                    aria-label="Filters">
                <x-lucide-sliders-horizontal class="w-5 h-5" />
            </button>
        @endif

        {{-- Create button --}}
        @if($createHref)
            <a href="{{ $createHref }}"
               class="h-11 inline-flex items-center justify-center gap-2
                      {{ $btn }} px-6 text-sm font-semibold text-white shadow-sm
                      focus:outline-none focus:ring-2 focus:ring-offset-2">
                <x-lucide-plus class="w-4 h-4" />
                <span>{{ $createLabel }}</span>
            </a>
        @endif

    </div>
</div>
