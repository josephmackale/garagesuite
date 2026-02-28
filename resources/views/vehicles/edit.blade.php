<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <x-back-link href="{{ route('vehicles.show', $vehicle) }}" label="Back" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Vehicle') }}
            </h2>
        </div>
    </x-slot>


    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('vehicles.update', $vehicle) }}">
                        @method('PUT')
                        @include('vehicles._form', [
                            'vehicle' => $vehicle,
                            'buttonText' => 'Update Vehicle',
                        ])
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
