{{-- resources/views/jobs/show.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Job Details') }}
        </h2>
    </x-slot>

    @php
        $jobDate = $job->job_date ?? $job->created_at;

        $labourTotal = $job->workItems->sum('line_total');
        $partsTotal  = $job->partItems->sum('line_total');
        $grandTotal  = $labourTotal + $partsTotal;

        $status = strtolower($job->status ?? '');
        $statusColor = 'bg-gray-100 text-gray-700';
        if (in_array($status, ['pending', 'open', 'new'])) {
            $statusColor = 'bg-yellow-100 text-yellow-800';
        } elseif (in_array($status, ['in progress', 'ongoing'])) {
            $statusColor = 'bg-blue-100 text-blue-800';
        } elseif (in_array($status, ['completed', 'closed', 'done'])) {
            $statusColor = 'bg-green-100 text-green-800';
        }
    @endphp

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Success flash --}}
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('info'))
                <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-md text-sm">
                    {{ session('info') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md text-sm">
                    <div class="font-semibold mb-1">Please fix the following:</div>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Top summary + actions --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl p-6">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">

                    {{-- Left: vehicle + customer --}}
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">
                            {{ $job->vehicle?->registration_number }}
                            <span class="text-gray-400">–</span>
                            {{ $job->vehicle?->make }} {{ $job->vehicle?->model }}
                        </h1>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ $job->vehicle?->customer?->name }}
                            @if($job->vehicle?->customer?->phone)
                                <span class="text-gray-400">·</span>
                                {{ $job->vehicle->customer->phone }}
                            @endif
                        </p>

                        <dl class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs sm:text-sm text-gray-700">
                            <div>
                                <dt class="text-gray-500">Job Date</dt>
                                <dd class="font-medium">{{ optional($jobDate)->format('d M Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Service Type</dt>
                                <dd class="font-medium">{{ $job->service_type ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Status</dt>
                                <dd class="font-medium">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusColor }}">
                                        {{ ucfirst($job->status ?? 'pending') }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Mileage</dt>
                                <dd class="font-medium">{{ $job->mileage ?? $job->odometer ?? '—' }}</dd>
                            </div>
                            @if(!empty($job->reference))
                                <div>
                                    <dt class="text-gray-500">Job Ref</dt>
                                    <dd class="font-medium">{{ $job->reference }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Right: actions --}}
                    <div class="flex flex-wrap gap-2 justify-end">

                        @if($job->invoice)
                            <a href="{{ route('invoices.show', $job->invoice) }}"
                               class="inline-flex items-center px-4 py-2 rounded-full text-xs font-semibold tracking-wide bg-emerald-600 text-white hover:bg-emerald-700">
                                VIEW INVOICE
                            </a>
                        @else
                            <form method="POST" action="{{ route('jobs.invoice.store', $job) }}">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 rounded-full text-xs font-semibold tracking-wide bg-emerald-600 text-white hover:bg-emerald-700">
                                    CREATE INVOICE
                                </button>
                            </form>
                        @endif

                        {{-- Job Card (HTML) --}}
                        <a href="{{ route('jobs.job-card', $job) }}"
                           class="inline-flex items-center px-4 py-2 rounded-full text-xs font-semibold tracking-wide border border-gray-300 text-gray-700 hover:bg-gray-50">
                            View Job Card
                        </a>

                        {{-- Job Card (PDF) --}}
                        <a href="{{ route('jobs.job-card.download', $job) }}"
                           class="inline-flex items-center px-4 py-2 rounded-full text-xs font-semibold tracking-wide bg-gray-900 text-white hover:bg-gray-800">
                            Download Job Card
                        </a>

                        <a href="{{ route('jobs.edit', $job) }}"
                           class="inline-flex items-center px-4 py-2 rounded-full text-xs font-semibold tracking-wide bg-indigo-600 text-white hover:bg-indigo-700">
                            EDIT
                        </a>
                    </div>

                </div>
            </div>

            {{-- Main 2-column content --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- LEFT: job narrative --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Job description --}}
                    <div class="bg-white shadow-sm sm:rounded-2xl p-6 space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900 tracking-tight">
                            Job Description
                        </h3>

                        <div>
                            <label class="block text-xs font-medium text-gray-500">Customer Complaint</label>
                            <div class="mt-1 text-sm text-gray-900 border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 min-h-[42px]">
                                {{ $job->customer_complaint ?: '—' }}
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500">Diagnosis</label>
                                <div class="mt-1 text-sm text-gray-900 border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 min-h-[42px]">
                                    {{ $job->diagnosis ?: '—' }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500">Work Done</label>
                                <div class="mt-1 text-sm text-gray-900 border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 min-h-[42px]">
                                    {{ $job->work_done ?: '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500">Parts Used (notes)</label>
                                <div class="mt-1 text-sm text-gray-900 border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 min-h-[42px]">
                                    {{ $job->parts_used ?: '—' }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500">Internal Notes</label>
                                <div class="mt-1 text-sm text-gray-900 border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 min-h-[42px]">
                                    {{ $job->internal_notes ?: '—' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Labour table --}}
                    <div class="bg-white shadow-sm sm:rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-900 tracking-tight">
                                Labour Items
                            </h3>
                            <a href="{{ route('jobs.edit', $job) }}"
                               class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                Edit labour
                            </a>
                        </div>

                        @if($job->workItems->isEmpty())
                            <p class="text-sm text-gray-500">No labour items captured.</p>
                        @else
                            <div class="overflow-x-auto -mx-4 sm:mx-0">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Qty / Hours</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Rate</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Line Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        @foreach($job->workItems as $item)
                                            <tr>
                                                <td class="px-4 py-2 text-gray-900">{{ $item->description }}</td>
                                                <td class="px-4 py-2 text-right text-gray-700">{{ number_format($item->quantity, 2) }}</td>
                                                <td class="px-4 py-2 text-right text-gray-700">{{ number_format($item->unit_price, 2) }}</td>
                                                <td class="px-4 py-2 text-right font-medium text-gray-900">{{ number_format($item->line_total, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- Parts table --}}
                    <div class="bg-white shadow-sm sm:rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-900 tracking-tight">
                                Parts Items
                            </h3>
                            <a href="{{ route('jobs.edit', $job) }}"
                               class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                Edit parts
                            </a>
                        </div>

                        @if($job->partItems->isEmpty())
                            <p class="text-sm text-gray-500">No parts captured.</p>
                        @else
                            <div class="overflow-x-auto -mx-4 sm:mx-0">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Source</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Qty</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit Price</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Line Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        @foreach($job->partItems as $item)
                                            <tr>
                                                <td class="px-4 py-2 text-gray-900">{{ $item->description }}</td>
                                                <td class="px-4 py-2 text-gray-700">{{ $item->source ?? '—' }}</td>
                                                <td class="px-4 py-2 text-right text-gray-700">{{ number_format($item->quantity, 2) }}</td>
                                                <td class="px-4 py-2 text-right text-gray-700">{{ number_format($item->unit_price, 2) }}</td>
                                                <td class="px-4 py-2 text-right font-medium text-gray-900">{{ number_format($item->line_total, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                </div>

                {{-- RIGHT: side panel --}}
                <div class="space-y-6">

                    {{-- Cost breakdown --}}
                    <div class="bg-white shadow-sm sm:rounded-2xl p-6">
                        <h3 class="text-sm font-semibold text-gray-900 tracking-tight">
                            Cost Breakdown
                        </h3>

                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <dt class="text-gray-500">Labour</dt>
                                <dd class="font-medium text-gray-900">{{ number_format($labourTotal, 2) }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-gray-500">Parts</dt>
                                <dd class="font-medium text-gray-900">{{ number_format($partsTotal, 2) }}</dd>
                            </div>
                            <div class="border-t border-gray-200 my-2"></div>
                            <div class="flex items-center justify-between">
                                <dt class="text-gray-600 font-semibold">Total</dt>
                                <dd class="text-lg font-semibold text-gray-900">{{ number_format($grandTotal, 2) }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Status/meta --}}
                    <div class="bg-white shadow-sm sm:rounded-2xl p-6 space-y-3">
                        <h3 class="text-sm font-semibold text-gray-900 tracking-tight">
                            Job Meta
                        </h3>

                        <p class="text-xs text-gray-500">
                            Job created {{ optional($job->created_at)->diffForHumans() }}.
                            @if($job->updated_at && $job->updated_at != $job->created_at)
                                Last updated {{ $job->updated_at->diffForHumans() }}.
                            @endif
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
