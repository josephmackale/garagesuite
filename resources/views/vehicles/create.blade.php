<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Vehicle') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST"
                          action="{{ isset($lockedCustomer)
                                    ? route('customers.vehicles.store', $lockedCustomer)
                                    : route('vehicles.store') }}">

                        @include('vehicles._form', [
                            'vehicle' => null,
                            'buttonText' => 'Save Vehicle',
                            'lockedCustomer' => $lockedCustomer ?? null,
                            'selectedCustomerId' => $selectedCustomerId ?? null,
                        ])
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
