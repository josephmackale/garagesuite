{{-- inventory/partials/header.blade.php --}}
<div class="flex items-center justify-between">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Inventory
    </h2>

    <a href="{{ route('inventory-items.create') }}"
       class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2
              text-sm font-semibold text-white hover:bg-indigo-700">
        <x-lucide-plus class="w-4 h-4" />
        Add Item
    </a>
</div>
