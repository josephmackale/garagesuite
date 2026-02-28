{{-- inventory/partials/filters.blade.php --}}
<div class="bg-white rounded-xl shadow-sm p-4">
    <form method="GET" class="flex gap-2">
        <input
            type="text"
            name="q"
            value="{{ $search ?? '' }}"
            placeholder="Search by name, brand, part number..."
            class="flex-1 rounded-lg border-gray-300 text-sm
                   focus:border-indigo-500 focus:ring-indigo-500"
        />

        <button type="submit"
                class="rounded-lg bg-gray-900 px-4 py-2
                       text-sm font-semibold text-white">
            Search
        </button>
    </form>
</div>
