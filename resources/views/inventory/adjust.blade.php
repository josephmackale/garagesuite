{{-- resources/views/inventory/adjust.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Adjust Stock – {{ $item->name }}
        </h2>
    </x-slot>

    <div class="max-w-xl mx-auto py-6">
        <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">

            <div class="text-sm text-gray-600">
                <p>Current Stock:
                    <span class="font-semibold">{{ $item->current_stock }} {{ $item->unit }}</span>
                </p>
                <p>Reorder Level:
                    <span class="font-semibold">{{ $item->reorder_level }}</span>
                </p>
            </div>

            <form action="{{ route('inventory-items.adjust', $item) }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium mb-1">Adjustment Type</label>
                    <div class="flex gap-4">

                        <label class="inline-flex items-center">
                            <input type="radio" name="mode" value="increase"
                                   {{ old('mode', 'increase') === 'increase' ? 'checked' : '' }}>
                            <span class="ml-2">Increase</span>
                        </label>

                        <label class="inline-flex items-center">
                            <input type="radio" name="mode" value="decrease"
                                   {{ old('mode') === 'decrease' ? 'checked' : '' }}>
                            <span class="ml-2">Decrease</span>
                        </label>

                        <label class="inline-flex items-center">
                            <input type="radio" name="mode" value="set"
                                   {{ old('mode') === 'set' ? 'checked' : '' }}>
                            <span class="ml-2">Set to exact quantity</span>
                        </label>

                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Quantity</label>
                    <input type="number" name="quantity" min="1"
                           value="{{ old('quantity', 1) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Reason (optional)</label>
                    <input type="text" name="reason"
                           value="{{ old('reason') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('inventory-items.index') }}"
                       class="px-4 py-2 text-sm border rounded-md">Cancel</a>

                    <button class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md">
                        Update Stock
                    </button>
                </div>

            </form>

        </div>
    </div>
</x-app-layout>
