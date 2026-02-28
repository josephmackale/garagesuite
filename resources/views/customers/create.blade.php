<x-app-layout>

    {{-- Page Header --}}
    <x-slot name="header">
        <div class="relative flex items-center justify-center">

            {{-- Back Link (Left) --}}
            <div class="absolute left-0 flex items-center gap-3">
                <x-back-link href="{{ route('customers.index') }}" label="Customers" />
            </div>

            {{-- Center Title --}}
            <div class="text-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Add Customer
                </h2>

                <p class="text-sm text-gray-500">
                    Create a new customer profile
                </p>
            </div>

        </div>
    </x-slot>


    {{-- Page Content --}}
    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                <form method="POST" action="{{ route('customers.store') }}">

                    @include('customers._form', [
                        'customer' => new \App\Models\Customer,
                        'buttonLabel' => 'Create Customer',
                    ])

                </form>

            </div>

        </div>
    </div>

</x-app-layout>
