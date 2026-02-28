<div class="bg-white shadow-sm rounded-xl p-4">
    <form method="GET" class="flex flex-col sm:flex-row sm:items-end gap-4">

        <div class="w-full sm:w-72">
            <x-input-label value="Search" />
            <x-text-input
                name="q"
                value="{{ $q }}"
                placeholder="Job #, reg no, customer..."
                class="mt-1 block w-full"
            />
        </div>

        <div class="w-full sm:w-44">
            <x-input-label value="Status" />
            <select name="status"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                <option value="">All</option>
                @foreach ($statuses as $st)
                    <option value="{{ $st }}" @selected($activeStatus === $st)>
                        {{ ucfirst(str_replace('_',' ',$st)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex gap-2">
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white rounded-lg text-xs font-semibold">
                <x-lucide-filter class="w-4 h-4" /> Apply
            </button>

            @if($q || $activeStatus)
                <a href="{{ route('jobs.index') }}"
                   class="inline-flex items-center px-3 py-2 border rounded-lg text-xs">
                    Clear
                </a>
            @endif
        </div>

    </form>
</div>
