<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Job #{{ $job->job_number }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Update job details, labour and parts.
                </p>
            </div>

            <a href="{{ route('jobs.show', $job) }}"
               class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50">
                Back to Job
            </a>
        </div>
    </x-slot>

    <div class="py-3">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg px-6 py-3">
                <form method="POST"
                    action="{{ route('jobs.update', $job) }}"
                    class="space-y-4">

                    @csrf
                    @method('PUT')

                    @include('jobs._form', [
                        'submitLabel' => 'Update Job',
                        'selectedVehicleId' => null,
                    ])
                </form>
            </div>
        </div>
    </div>

</x-app-layout>
