<x-app-layout>
    {{-- Header with back link --}}
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <x-back-link href="{{ route('vehicles.index') }}" label="Vehicles" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $vehicle->registration_number }} – {{ $vehicle->make }} {{ $vehicle->model }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Vehicle details card --}}
            <div class="bg-white shadow-sm sm:rounded-xl p-6">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-900">
                        Vehicle Details
                    </h3>

                    <a href="{{ route('vehicles.edit', $vehicle) }}"
                       class="text-sm text-indigo-600 hover:underline">
                        Edit Vehicle
                    </a>
                </div>

                {{-- Two-column layout --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-800">
                    {{-- Column 1: high-level + owner --}}
                    <div class="space-y-2">
                        <div>
                            <div class="text-lg font-semibold">
                                {{ strtoupper($vehicle->make) }} {{ strtoupper($vehicle->model) }}
                                @if($vehicle->year)
                                    <span class="text-gray-500">({{ $vehicle->year }})</span>
                                @endif
                            </div>
                        </div>

                        @if($vehicle->customer)
                            <div>
                                <span class="font-medium">Customer:</span>
                                <a href="{{ route('customers.show', $vehicle->customer) }}"
                                   class="text-indigo-600 hover:underline">
                                    {{ $vehicle->customer->name }}
                                </a>
                                @if($vehicle->customer->phone)
                                    <span class="text-gray-500">({{ $vehicle->customer->phone }})</span>
                                @endif
                            </div>
                        @endif

                        <div>
                            <span class="font-medium">Reg Number:</span>
                            <span class="ml-1">{{ $vehicle->registration_number }}</span>
                        </div>

                        @if($vehicle->color)
                            <div>
                                <span class="font-medium">Color:</span>
                                <span class="ml-1 uppercase">{{ $vehicle->color }}</span>
                            </div>
                        @endif

                        @if($vehicle->mileage)
                            <div>
                                <span class="font-medium">Last Recorded Mileage:</span>
                                <span class="ml-1">{{ number_format($vehicle->mileage) }} km</span>
                            </div>
                        @endif
                    </div>

                    {{-- Column 2: technical details --}}
                    <div class="space-y-2">
                        @if($vehicle->vin)
                            <div>
                                <span class="font-medium">VIN / Chassis:</span>
                                <span class="ml-1">{{ $vehicle->vin }}</span>
                            </div>
                        @endif

                        <div>
                            <span class="font-medium">Make / Model / Year:</span>
                            <span class="ml-1">
                                {{ strtoupper($vehicle->make) }} {{ strtoupper($vehicle->model) }}
                                @if($vehicle->year)
                                    ({{ $vehicle->year }})
                                @endif
                            </span>
                        </div>

                        @if($vehicle->engine)
                            <div>
                                <span class="font-medium">Engine:</span>
                                <span class="ml-1">{{ $vehicle->engine }}</span>
                            </div>
                        @endif

                        @if($vehicle->notes)
                            <div class="mt-2">
                                <span class="font-medium">Notes:</span>
                                <p class="mt-1 text-gray-700 whitespace-pre-line">
                                    {{ $vehicle->notes }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Add Job button - full width --}}
                <div class="mt-6">
                    <a href="{{ route('jobs.create.step1', [
                            'modal' => 1,
                            'vehicle_id' => $vehicle->id,
                            'customer_id' => $vehicle->customer_id,
                        ]) }}"
                       onclick="event.preventDefault(); if (window.openCreateJobModal) openCreateJobModal(this.href); else window.location.href = this.href;"
                       class="inline-flex items-center justify-center w-full px-4 py-2
                              rounded-lg bg-indigo-600 text-white text-sm font-medium">
                        Add Job for this Vehicle
                    </a>
                </div>
            </div>

            {{-- Service history --}}
            <div class="bg-white shadow-sm sm:rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-900">
                        Service History
                    </h3>

                    <a href="{{ route('jobs.create.step1', [
                            'modal' => 1,
                            'vehicle_id' => $vehicle->id,
                            'customer_id' => $vehicle->customer_id,
                        ]) }}"
                       onclick="event.preventDefault(); if (window.openCreateJobModal) openCreateJobModal(this.href); else window.location.href = this.href;"
                       class="text-xs text-indigo-600 hover:underline">
                        Create Job
                    </a>
                </div>

                @if($vehicle->jobs->isEmpty())
                    <p class="text-sm text-gray-500">
                        No jobs recorded yet for this vehicle.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Date</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Service</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-700">Status</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Cost</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-700">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($vehicle->jobs as $job)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-3 py-2">
                                        {{ optional($job->job_date ?? $job->date)->format('Y-m-d') }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ $job->service_type ?? $job->title ?? 'Service' }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ ucfirst($job->status) }}
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        Ksh {{ number_format($job->final_cost ?? 0, 2) }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-xs space-x-3">
                                        <a href="{{ route('jobs.edit', $job) }}"
                                           class="text-indigo-600 hover:underline">View</a>
                                        {{-- Later: Job Card PDF link here --}}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
