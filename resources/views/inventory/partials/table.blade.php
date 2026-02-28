{{-- resources/views/inventory/partials/table.blade.php --}}

<div class="hidden md:block">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <table class="min-w-full text-sm">
                <thead>
                <tr class="border-b text-left text-gray-600">
                    <th class="pb-2">Name</th>
                    <th class="pb-2">Brand</th>
                    <th class="pb-2">Part No.</th>
                    <th class="pb-2 text-right">Stock</th>
                    <th class="pb-2 text-right">Cost</th>
                    <th class="pb-2 text-right">Selling</th>
                    <th class="pb-2 text-right">Actions</th>
                </tr>
                </thead>

                <tbody>
                @forelse ($items as $item)
                    <tr class="border-b last:border-0">
                        <td class="py-2">
                            <div class="font-semibold">{{ $item->name }}</div>
                            @if($item->category)
                                <div class="text-xs text-gray-500">{{ $item->category }}</div>
                            @endif
                        </td>

                        <td class="py-2">{{ $item->brand }}</td>
                        <td class="py-2">{{ $item->part_number }}</td>

                        <td class="py-2 text-right">
                            {{ $item->current_stock }} {{ $item->unit }}
                        </td>

                        <td class="py-2 text-right">
                            KES {{ number_format($item->cost_price, 2) }}
                        </td>

                        <td class="py-2 text-right">
                            KES {{ number_format($item->selling_price, 2) }}
                        </td>

                        <td class="py-2 text-right space-x-2">
                            <a href="{{ route('inventory-items.edit', $item) }}"
                               class="text-indigo-600 text-xs hover:underline">
                                Edit
                            </a>

                            <form action="{{ route('inventory-items.destroy', $item) }}"
                                  method="POST"
                                  class="inline"
                                  onsubmit="return confirm('Delete this item?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 text-xs hover:underline">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-gray-500">
                            No inventory items yet.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
