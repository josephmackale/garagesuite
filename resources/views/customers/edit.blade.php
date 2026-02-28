<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <x-back-link href="{{ route('customers.show', $customer) }}" label="Back" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Customer
            </h2>
        </div>
    </x-slot>


    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-xl p-6">
                <form method="POST" action="{{ route('customers.update', $customer) }}">
                    @method('PUT')
                    @include('customers._form', [
                        'customer' => $customer,
                        'buttonLabel' => 'Update Customer',
                    ])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
