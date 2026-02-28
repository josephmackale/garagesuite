@php
    $steps = [
        ['label' => 'Type',  'active' => in_array($current, ['step1','step2','step3','review'])],
        ['label' => 'Payer', 'active' => in_array($current, ['step2','step3','review'])],
        ['label' => 'Job',   'active' => in_array($current, ['step3','review'])],
        ['label' => 'Review','active' => in_array($current, ['review'])],
    ];
@endphp

<div class="flex items-center gap-3 text-sm">
    @foreach($steps as $i => $s)
        <div class="flex items-center gap-2">
            <div class="h-7 w-7 rounded-full flex items-center justify-center
                {{ $s['active'] ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-600' }}">
                {{ $i + 1 }}
            </div>
            <span class="{{ $s['active'] ? 'text-slate-900 font-semibold' : 'text-slate-600' }}">
                {{ $s['label'] }}
            </span>
        </div>

        @if(!$loop->last)
            <div class="h-px w-10 bg-slate-200"></div>
        @endif
    @endforeach
</div>
