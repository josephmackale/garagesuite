<x-app-layout>

    <div class="py-6">

        {{-- Search / toolbar (already width-aware) --}}
        @include('vehicles.partials.search')

        {{-- Page content --}}
        <x-ui.page-container class="mt-5 space-y-4">

            {{-- List --}}
            <div class="bg-white shadow-sm sm:rounded-lg divide-y overflow-hidden">
                @forelse($vehicles as $vehicle)
                    @include('vehicles.partials.row', ['vehicle' => $vehicle])
                @empty
                    <div class="px-6 py-12 text-center text-sm text-gray-500">
                        No vehicles found.
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div>
                {{ $vehicles->links() }}
            </div>

        </x-ui.page-container>

    </div>

</x-app-layout>
