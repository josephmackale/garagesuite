@props([
    'label',
    'active' => false,
    'open' => false,

    // style: "bordered" (Jobs/Inventory) OR "soft" (Documents)
    'style' => 'bordered',
])

@php
    $buttonBase = 'w-full flex items-center justify-between px-4 py-2 font-medium rounded-xl cursor-pointer';

    if ($style === 'bordered') {
        $buttonBase .= ' border-l-4';
        $buttonClass = $active
            ? 'bg-indigo-50 text-indigo-600 border-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-300'
            : 'text-gray-700 hover:bg-gray-50 border-transparent dark:text-slate-200 dark:hover:bg-slate-800/70';
    } else {
        $buttonClass = $active
            ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-300'
            : 'text-gray-700 hover:bg-indigo-50 dark:text-slate-200 dark:hover:bg-slate-800/70';
    }
@endphp

<div x-data="{ open: {{ $open ? 'true' : 'false' }} }" class="space-y-0.5">
    <button type="button" @click="open = !open" class="{{ $buttonBase }} {{ $buttonClass }}">
        <span class="flex items-center">
            {{ $icon ?? '' }}
            <span>{{ $label }}</span>
        </span>

        <span class="text-gray-500 dark:text-slate-300">
            <x-lucide-chevron-down x-show="open" class="w-4 h-4" x-cloak />
            <x-lucide-chevron-right x-show="!open" class="w-4 h-4" x-cloak />
        </span>
    </button>

    <div x-show="open" x-transition class="ml-10 mt-1 space-y-0.5">
        {{ $slot }}
    </div>
</div>
