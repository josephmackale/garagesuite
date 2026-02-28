<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    New Job
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Open a new job for a customer vehicle.
                </p>
            </div>

            <a href="{{ route('jobs.index') }}"
               class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50">
                Back to Jobs
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @include('jobs.partials.create-form', [
                    'customerId' => $customerId ?? null,
                    'selectedVehicleId' => $selectedVehicleId ?? null,
                    'hideBasics' => $hideBasics ?? false,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
