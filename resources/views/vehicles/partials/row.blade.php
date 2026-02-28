<div class="px-5 sm:px-6 py-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

        {{-- LEFT: DETAILS --}}
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-3 flex-wrap">
                <span class="font-semibold text-gray-900">
                    {{ $vehicle->registration_number ?? '—' }}
                </span>

                @if($vehicle->color)
                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">
                        {{ $vehicle->color }}
                    </span>
                @endif
            </div>

            <div class="mt-1 text-sm text-gray-500">
                {{ $vehicle->make ?? '—' }} · {{ $vehicle->model ?? '—' }}
                @if($vehicle->year) · {{ $vehicle->year }} @endif
            </div>

            <div class="mt-1 text-sm">
                <span class="text-gray-500">Customer:</span>

                @if($vehicle->customer)
                    <a href="{{ route('customers.show', $vehicle->customer) }}"
                       class="font-medium text-indigo-600 hover:underline">
                        {{ $vehicle->customer->name }}
                    </a>

                    @if($vehicle->customer->phone)
                        <span class="text-gray-500 ml-2">{{ $vehicle->customer->phone }}</span>
                    @endif
                @else
                    <span class="text-gray-400">No customer</span>
                @endif
            </div>
        </div>

        {{-- RIGHT: ACTIONS --}}
        <div class="shrink-0">

            {{-- md+ ICON ACTIONS --}}
            <div class="hidden md:flex items-center gap-1">
                <a href="{{ route('jobs.index', ['vehicle_id' => $vehicle->id]) }}"
                   class="p-2 rounded-lg text-indigo-600 hover:bg-indigo-50" title="Jobs">
                    <x-lucide-briefcase class="w-4 h-4" />
                </a>

                <a href="{{ route('vehicles.show', $vehicle) }}"
                   class="p-2 rounded-lg text-gray-600 hover:bg-gray-100" title="View">
                    <x-lucide-eye class="w-4 h-4" />
                </a>

                <a href="{{ route('vehicles.edit', $vehicle) }}"
                   class="p-2 rounded-lg text-indigo-600 hover:bg-indigo-50" title="Edit">
                    <x-lucide-pencil class="w-4 h-4" />
                </a>

                <form method="POST" action="{{ route('vehicles.destroy', $vehicle) }}"
                      onsubmit="return confirm('Delete this vehicle?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-2 rounded-lg text-red-600 hover:bg-red-50" title="Delete">
                        <x-lucide-trash-2 class="w-4 h-4" />
                    </button>
                </form>
            </div>

            {{-- MOBILE TEXT ACTIONS --}}
                <div class="md:hidden mt-4 grid grid-cols-2 gap-2">
                <a href="{{ route('jobs.index', ['vehicle_id' => $vehicle->id]) }}"
                class="inline-flex items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold text-indigo-600 hover:bg-indigo-50">
                    Jobs
                </a>

                <a href="{{ route('vehicles.show', $vehicle) }}"
                class="inline-flex items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    View
                </a>

                <a href="{{ route('vehicles.edit', $vehicle) }}"
                class="col-span-2 inline-flex items-center justify-center rounded-lg bg-indigo-600 px-3 py-2
                        text-sm font-semibold text-white hover:bg-indigo-700">
                    Edit Vehicle
                </a>

                <form method="POST"
                    action="{{ route('vehicles.destroy', $vehicle) }}"
                    onsubmit="return confirm('Delete this vehicle?')"
                    class="col-span-2">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center rounded-lg bg-red-50 px-3 py-2
                                text-sm font-semibold text-red-700 hover:bg-red-100">
                        Delete Vehicle
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
