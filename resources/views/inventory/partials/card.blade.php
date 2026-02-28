{{-- inventory/partials/card.blade.php --}}
<div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">

    {{-- Top --}}
    <div class="flex items-start justify-between gap-3">
        <div>
            <div class="font-semibold text-gray-900">
                {{ $item->name }}
            </div>

            <div class="mt-1 text-xs text-gray-500">
                {{ $item->brand }} · {{ $item->part_number }}
            </div>

            @if($item->category)
                <div class="mt-1 text-xs text-gray-400">
                    {{ $item->category }}
                </div>
            @endif
        </div>

        {{-- Stock pill --}}
        @php
            if ($item->current_stock <= 0 || $item->status === 'inactive') {
                $pill = 'bg-red-50 text-red-700';
                $label = 'Out';
            } elseif ($item->reorder_level > 0 && $item->current_stock <= $item->reorder_level) {
                $pill = 'bg-yellow-50 text-yellow-800';
                $label = 'Low';
            } else {
                $pill = 'bg-green-50 text-green-700';
                $label = 'In';
            }
        @endphp

        <span class="inline-flex items-center rounded-full px-2 py-0.5
                     text-[11px] font-semibold {{ $pill }}">
            {{ $label }}
        </span>
    </div>

    {{-- Stock --}}
    <div class="mt-3 text-sm">
        <span class="text-gray-500">Stock:</span>
        <span class="font-semibold text-gray-900">
            {{ $item->current_stock }} {{ $item->unit }}
        </span>
    </div>

    {{-- Prices --}}
    <div class="mt-2 flex items-center justify-between text-sm">
        <div class="text-gray-500">
            Cost
            <div class="font-semibold text-gray-900">
                KES {{ number_format($item->cost_price, 2) }}
            </div>
        </div>

        <div class="text-right text-gray-500">
            Selling
            <div class="font-semibold text-gray-900">
                KES {{ number_format($item->selling_price, 2) }}
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="mt-4 grid grid-cols-2 gap-2">
        <a href="{{ route('inventory-items.edit', $item) }}"
           class="inline-flex items-center justify-center rounded-lg
                  border px-3 py-2 text-sm font-semibold text-indigo-600">
            Edit
        </a>

        <form method="POST"
              action="{{ route('inventory-items.destroy', $item) }}"
              onsubmit="return confirm('Delete this item?');">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="w-full inline-flex items-center justify-center
                           rounded-lg bg-red-50 px-3 py-2
                           text-sm font-semibold text-red-700">
                Delete
            </button>
        </form>
    </div>
</div>
