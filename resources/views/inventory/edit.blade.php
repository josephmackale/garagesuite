{{-- resources/views/inventory/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Inventory Item
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('inventory-items.update', $item) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    @include('inventory.partials.form', ['item' => $item])

                    <div class="flex justify-end gap-2">
                        <a href="{{ route('inventory-items.index') }}"
                           class="px-4 py-2 text-sm border rounded-md">Cancel</a>

                        <button class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md">
                            Update Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
