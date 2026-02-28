{{-- resources/views/customers/show.blade.php --}}
<x-app-layout>
    @php
        /* ============================================================
         * SECTION 0: Helpers / Shared vars
         * ============================================================ */
        $initials = function ($name) {
            $name = trim((string) $name);
            if ($name === '') return '—';
            $parts = preg_split('/\s+/', $name);
            $first = mb_substr($parts[0] ?? '', 0, 1);
            $last  = mb_substr(end($parts) ?: '', 0, 1);
            return strtoupper(($first ?: '') . ($last ?: '')) ?: '—';
        };

        $activeReminders    = $activeReminders    ?? 0;
        $activeJobs         = $activeJobs         ?? ($customer->jobs?->whereIn('status', ['pending','in progress'])->count() ?? 0);
        $activeAppointments = $activeAppointments ?? 0; // until appointments module exists

        $waPhone = preg_replace('/\D+/', '', (string)($customer->phone ?? ''));
        $waLink  = $waPhone ? "https://wa.me/{$waPhone}" : null;

        // Legacy urls (you can later remove if you drop these)
        $remindersUrl    = request()->fullUrlWithQuery(['hub' => 'reminders']);
        $jobsUrl         = request()->fullUrlWithQuery(['tab' => 'jobs']);      // if you later add main tabs
        $appointmentsUrl = request()->fullUrlWithQuery(['hub' => 'appointments']); // placeholder

        $vehicles = $customer->vehicles ?? collect();
    @endphp


    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- ============================================================
             * SECTION 1: Customer Header Strip (GaragePlug-like)
             * ============================================================ --}}
            <div class="bg-white shadow-sm">
                <div class="px-6 py-4">
                    <div class="flex items-start justify-between gap-6">

                        {{-- LEFT --}}
                        <div class="flex items-start gap-4 min-w-0">
                            <div class="h-11 w-11 rounded-full bg-gray-100 text-gray-700 flex items-center justify-center font-semibold text-sm">
                                {{ $initials($customer->name) }}
                            </div>

                            <div class="min-w-0">
                                {{-- Name row + WhatsApp --}}
                                <div class="flex items-center gap-2">
                                    <div class="text-[15px] leading-5 font-semibold text-gray-900 truncate min-w-0 flex-1">
                                        {{ $customer->name }}
                                    </div>

                                    @if($waLink)
                                        <a href="{{ $waLink }}" target="_blank"
                                           class="inline-flex shrink-0 items-center justify-center text-emerald-600 hover:text-emerald-700 -mt-[1px]"
                                           title="WhatsApp">
                                            <x-lucide-message-circle class="w-[18px] h-[18px]" />
                                        </a>
                                    @endif
                                </div>

                                {{-- Phone + Email --}}
                                <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm leading-5 text-gray-700">
                                    @if($customer->phone)
                                        <span class="inline-flex items-center gap-2">
                                            <x-lucide-phone class="w-4 h-4 text-gray-400" />
                                            <span>{{ $customer->phone }}</span>
                                        </span>
                                    @endif

                                    @if($customer->email)
                                        <span class="inline-flex items-center gap-2 min-w-0">
                                            <x-lucide-mail class="w-4 h-4 text-gray-400" />
                                            <span class="truncate">{{ $customer->email }}</span>
                                        </span>
                                    @endif
                                </div>

                                {{-- Counters (underline ONLY up to ":") --}}
                                <div class="mt-1 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm leading-5 text-gray-700">
                                    <a href="{{ $remindersUrl }}" class="hover:text-gray-900">
                                        <span class="text-gray-600 underline decoration-gray-300 underline-offset-2">
                                            Active Reminders :
                                        </span>
                                        <span class="font-semibold text-gray-900">{{ $activeReminders }}</span>
                                    </a>

                                    <a href="{{ $jobsUrl }}" class="hover:text-gray-900">
                                        <span class="text-gray-600 underline decoration-gray-300 underline-offset-2">
                                            Active Jobs :
                                        </span>
                                        <span class="font-semibold text-gray-900">{{ $activeJobs }}</span>
                                    </a>

                                    <a href="{{ $appointmentsUrl }}" class="hover:text-gray-900">
                                        <span class="text-gray-600 underline decoration-gray-300 underline-offset-2">
                                            Active Appointments :
                                        </span>
                                        <span class="font-semibold text-gray-900">{{ $activeAppointments }}</span>
                                    </a>
                                </div>

                            </div>
                        </div>

                        {{-- RIGHT --}}
                        <div class="shrink-0 text-right">
                            <a href="{{ route('customers.edit', $customer) }}"
                               class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 hover:text-emerald-800">
                                <x-lucide-plus class="w-4 h-4" />
                                <span>Add Tag</span>
                            </a>

                            <div class="mt-1">
                                <button type="button"
                                        class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 hover:text-emerald-800 underline decoration-emerald-200 underline-offset-2">
                                    <span>View Total Business</span>
                                    <x-lucide-chevron-down class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ============================================================
            * SECTION 2: Vehicles Cards
            * ============================================================ --}}
            <div id="vehicles" class="bg-white shadow-sm">



                <div class="px-6 py-5">
                    @if($vehicles->count() === 0)
                        <div class="py-14 flex flex-col items-center justify-center text-center">
                            <div class="h-20 w-20 rounded-full bg-gray-50 flex items-center justify-center">
                                <x-lucide-car class="w-8 h-8 text-gray-400" />
                            </div>
                            <div class="mt-4 text-sm font-semibold text-gray-900">No vehicles recorded</div>
                            <div class="mt-1 text-sm text-gray-500">
                                Add the customer’s first vehicle to start creating jobs and documents.
                            </div>

                            <div class="mt-5">
                                @if(\Route::has('vehicles.create'))
                                    <a href="{{ route('vehicles.create', ['customer_id' => $customer->id]) }}"
                                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                        <x-lucide-plus class="w-4 h-4" />
                                        <span>Add Vehicle</span>
                                    </a>
                                @else
                                    <span class="text-sm text-red-600">Missing route: vehicles.create</span>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($vehicles as $vehicle)
                                @php
                                    $plate = $vehicle->registration_number ?? 'Vehicle';
                                    $makeModel = trim(($vehicle->make ?? '').' '.($vehicle->model ?? ''));
                                    $year = $vehicle->year ?? null;
                                    $subtitle = trim($makeModel . ($year ? " • {$year}" : ''));
                                    if ($subtitle === '') $subtitle = '—';

                                    $jobCount = method_exists($vehicle, 'jobs') ? ($vehicle->jobs?->count() ?? null) : null;
                                    $docCount = method_exists($vehicle, 'documents') ? ($vehicle->documents?->count() ?? null) : null;
                                @endphp

                                <div class="rounded-xl border border-gray-100 bg-white shadow-sm hover:shadow transition">
                                    <div class="p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-gray-900 truncate">
                                                    {{ $plate }}
                                                </div>
                                                <div class="mt-1 text-sm text-gray-600 truncate">
                                                    {{ $subtitle }}
                                                </div>
                                            </div>

                                            <div class="shrink-0 h-9 w-9 rounded-lg bg-gray-50 flex items-center justify-center">
                                                <x-lucide-car class="w-5 h-5 text-gray-400" />
                                            </div>
                                        </div>

                                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                                            @if(!empty($vehicle->vin))
                                                <span class="truncate">VIN: {{ $vehicle->vin }}</span>
                                            @endif
                                        </div>

                                        <div class="mt-3 flex items-center gap-3 text-xs text-gray-600">
                                            @if(!is_null($jobCount))
                                                <span class="inline-flex items-center gap-1">
                                                    <x-lucide-wrench class="w-3.5 h-3.5 text-gray-400" />
                                                    <span><span class="font-semibold text-gray-900">{{ $jobCount }}</span> Jobs</span>
                                                </span>
                                            @endif

                                            @if(!is_null($docCount))
                                                <span class="inline-flex items-center gap-1">
                                                    <x-lucide-file-text class="w-3.5 h-3.5 text-gray-400" />
                                                    <span><span class="font-semibold text-gray-900">{{ $docCount }}</span> Docs</span>
                                                </span>
                                            @endif
                                        </div>

                                        <div class="mt-4 flex items-center justify-between gap-2">
                                            <button type="button"
                                                    onclick="openCreateJobModal('{{ route('jobs.create.step1', [
                                                        'modal' => 1,
                                                        'customer_id' => $customer->id,
                                                        'vehicle_id' => $vehicle->id,
                                                    ]) }}')"
                                                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                                <x-lucide-plus class="w-3.5 h-3.5" />
                                                Create Job
                                            </button>

                                            <a href="{{ route('vehicles.show', $vehicle) }}"
                                            class="text-xs text-gray-500 hover:text-gray-700 hover:underline">
                                                View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- ✅ still allow adding more vehicles (but not in the header) --}}
                        <div class="mt-6">
                            @if(\Route::has('vehicles.create'))
                                <a href="{{ route('vehicles.create', ['customer_id' => $customer->id]) }}"
                                class="inline-flex items-center gap-2 rounded-xl bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-100">
                                    <x-lucide-plus class="w-4 h-4" />
                                    <span>Add Vehicle</span>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>


            {{-- ============================================================
             * SECTION 3: Documents Hub (Invoices / Receipts / Job Cards / All / Reminders)
             * ============================================================ --}}
            @php
                $hub = request('hub', 'invoices');

                $hubTabs = [
                    'invoices'  => 'Invoices',
                    'receipts'  => 'Receipts',
                    'job_cards' => 'Job Cards',
                    'all_docs'  => 'All Documents',
                    'reminders' => 'Reminders',
                ];

                $q = trim((string) request('q', ''));

                // ✅ Documents now come ONLY from controller
                $documents = $documents ?? collect();

                $typeMap = [
                    // ✅ current system (DocumentController/document_type)
                    'invoice_pdf'   => 'invoices',
                    'receipt_pdf'   => 'receipts',
                    'job_card_pdf'  => 'job_cards',

                    // ✅ legacy/alternate values (keep support)
                    'invoice'       => 'invoices',
                    'receipt'       => 'receipts',
                    'job_card'      => 'job_cards',
                    'jobcard'       => 'job_cards',
                ];

                $filteredDocs = $documents;

                if ($hub !== 'all_docs' && $hub !== 'reminders') {
                    $filteredDocs = $filteredDocs->filter(function ($d) use ($hub, $typeMap) {
                        $t = strtolower((string)($d->document_type ?? $d->type ?? $d->doc_type ?? ''));
                        $mapped = $typeMap[$t] ?? null;
                        return $mapped === $hub;
                    });
                }

                if ($q !== '' && $hub !== 'reminders') {
                    $filteredDocs = $filteredDocs->filter(function ($d) use ($q) {
                        $hay = strtolower(
                            (string)($d->title ?? $d->name ?? '')
                            .' '.(string)($d->reference ?? '')
                            .' '.(string)($d->number ?? '')
                        );
                        return str_contains($hay, strtolower($q));
                    });
                }

                $filteredDocs = $filteredDocs->sortByDesc(function ($d) {
                    return $d->issued_at ?? $d->created_at ?? now();
                });
            @endphp


            <div class="bg-white shadow-sm">
                {{-- Tabs row --}}
                <div class="px-6">
                    <div class="flex items-center gap-10 overflow-x-auto border-b border-gray-100">
                        @foreach($hubTabs as $key => $label)
                            @php $active = $hub === $key; @endphp
                            <a href="{{ request()->fullUrlWithQuery(['hub' => $key]) }}"
                               class="whitespace-nowrap py-4 text-xs font-bold tracking-widest
                                      {{ $active ? 'text-emerald-700' : 'text-gray-500 hover:text-gray-800' }}">
                                {{ strtoupper($label) }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Search row --}}
                <div class="px-6 pt-3">
                    @if($hub !== 'reminders')
                        <form method="GET" action="{{ url()->current() }}">
                            @foreach(request()->except(['q']) as $k => $v)
                                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                            @endforeach

                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-gray-400">
                                    <x-lucide-search class="w-4 h-4" />
                                </div>
                                <input type="text" name="q" value="{{ $q }}"
                                       placeholder="Search documents..."
                                       class="w-full rounded-full border border-transparent bg-gray-50 px-11 py-3 text-sm text-gray-800
                                              focus:bg-white focus:border-gray-200 focus:ring-0" />
                            </div>
                        </form>
                    @else
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-gray-400">
                                <x-lucide-search class="w-4 h-4" />
                            </div>
                            <input type="text" disabled
                                   placeholder="Search reminders (coming soon)..."
                                   class="w-full rounded-full border border-transparent bg-gray-50 px-11 py-3 text-sm text-gray-400" />
                        </div>
                    @endif
                </div>

                {{-- Content --}}
                <div class="px-6 py-6">
                    @if($hub === 'reminders')
                        <div class="py-16 flex flex-col items-center justify-center text-center">
                            <div class="h-20 w-20 rounded-full bg-gray-50 flex items-center justify-center">
                                <x-lucide-bell class="w-8 h-8 text-gray-400" />
                            </div>
                            <div class="mt-4 text-sm font-semibold text-gray-900">Reminders coming soon</div>
                            <div class="mt-1 text-sm text-gray-500">
                                SMS reminders, WhatsApp reminders, and service reminders will appear here.
                            </div>
                        </div>
                    @else
                        @if($filteredDocs->count() === 0)
                            <div class="py-16 flex flex-col items-center justify-center text-center">
                                <div class="h-20 w-20 rounded-full bg-gray-50 flex items-center justify-center">
                                    <x-lucide-file-text class="w-8 h-8 text-gray-400" />
                                </div>
                                <div class="mt-4 text-sm font-semibold text-gray-900">No documents found</div>
                                <div class="mt-1 text-sm text-gray-500">
                                    Upload invoices, receipts, or job cards for this customer’s vehicles.
                                </div>

                                @if(\Route::has('documents.create'))
                                    <div class="mt-5">
                                        <a href="{{ route('documents.create', ['customer_id' => $customer->id]) }}"
                                           class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                            <x-lucide-plus class="w-4 h-4" />
                                            <span>Add Document</span>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold text-gray-900">
                                    {{ $hubTabs[$hub] ?? 'Documents' }}
                                    <span class="ml-2 text-xs font-semibold text-gray-500">{{ $filteredDocs->count() }}</span>
                                </div>

                                @if(\Route::has('documents.create'))
                                    <a href="{{ route('documents.create', ['customer_id' => $customer->id]) }}"
                                       class="inline-flex items-center gap-2 rounded-xl bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-100">
                                        <x-lucide-plus class="w-4 h-4" />
                                        <span>Add Document</span>
                                    </a>
                                @endif
                            </div>

                            <div class="mt-4 divide-y divide-gray-100">
                                @foreach($filteredDocs as $doc)
                                    @php
                                        $title = $doc->name
                                            ?? $doc->title
                                            ?? $doc->file_name
                                            ?? 'Document';

                                        $rawType = (string)($doc->document_type ?? $doc->type ?? $doc->doc_type ?? 'DOC');
                                        $type    = strtoupper(str_replace('_', ' ', preg_replace('/_pdf$/', '', $rawType)));

                                        $date = $doc->issued_at
                                            ?? $doc->created_at
                                            ?? $doc->updated_at
                                            ?? null;
                                    @endphp

                                    <div class="py-4 flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold text-gray-900 truncate">{{ $title }}</div>
                                            <div class="mt-1 text-sm text-gray-600">
                                                <span class="font-semibold text-gray-700">{{ $type }}</span>
                                                @if($date)
                                                    <span class="mx-2 text-gray-300">•</span>
                                                    <span>{{ \Illuminate\Support\Carbon::parse($date)->format('d M Y') }}</span>
                                                @endif
                                                @if(!is_null($amount))
                                                    <span class="mx-2 text-gray-300">•</span>
                                                    <span>KSh {{ number_format((float)$amount, 2) }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="shrink-0 flex items-center gap-2">
                                            @if(\Route::has('documents.show'))
                                                <a href="{{ route('documents.show', $doc) }}"
                                                   class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">
                                                    View
                                                </a>
                                            @endif

                                            @if(!empty($doc->file_path) && \Route::has('documents.download'))
                                                <a href="{{ route('documents.download', $doc) }}"
                                                   class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                                                    Download
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            </div>


            {{-- ============================================================
             * SECTION 4: Recent Jobs (keep your existing list)
             * ============================================================ --}}
            <div class="bg-white shadow-sm sm:rounded-xl p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h3 class="text-base font-semibold text-gray-900">Recent Jobs</h3>

                    <button
                        type="button"
                        data-create-job-url="{{ route('jobs.create.step1', ['customer_id' => $customer->id]) }}"
                        onclick="openCreateJobModal(this.dataset.createJobUrl)"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        <x-lucide-plus class="w-4 h-4" />
                        <span>Create Job</span>
                    </button>

                </div>

                @if($customer->jobs->isEmpty())
                    <div class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-500">
                        No jobs recorded yet.
                    </div>
                @else
                    @php
                        $badge = function (?string $status) {
                            $s = strtolower(trim((string) $status));
                            return match ($s) {
                                'pending' => 'bg-gray-100 text-gray-700',
                                'awaiting parts' => 'bg-amber-100 text-amber-800',
                                'in progress' => 'bg-blue-100 text-blue-800',
                                'completed' => 'bg-green-100 text-green-800',
                                'delivered' => 'bg-emerald-100 text-emerald-800',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        };
                        $label = function (?string $status) {
                            $s = strtolower(trim((string) $status));
                            return $s !== '' ? ucfirst($s) : 'Unknown';
                        };
                    @endphp

                    {{-- Mobile cards --}}
                    <div class="space-y-3 sm:hidden">
                        @foreach($customer->jobs as $job)
                            <a href="{{ route('jobs.edit', $job) }}"
                               class="block rounded-xl border border-gray-200 p-4 hover:bg-gray-50">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ $job->job_no ?? ('Job #'.$job->id) }}
                                        </div>

                                        <div class="mt-1 text-sm text-gray-700 line-clamp-2">
                                            {{ $job->service_type ?? $job->title ?? 'Job' }}
                                        </div>

                                        <div class="mt-2 flex items-center justify-between gap-3 text-xs text-gray-500">
                                            <span>{{ optional($job->date)->format('Y-m-d') ?? '—' }}</span>
                                            <span class="font-medium text-gray-700">
                                                Ksh {{ number_format($job->final_cost ?? 0, 2) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="shrink-0 inline-flex items-center gap-1 text-sm text-indigo-600">
                                        <span>View</span>
                                        <x-lucide-chevron-right class="w-4 h-4" />
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- Tablet/Desktop table --}}
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-700">Date</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-700">Vehicle</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-700">Service</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-700">Status</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-700">Final Cost</th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($customer->jobs as $job)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-3 py-3 text-gray-700">{{ optional($job->date)->format('Y-m-d') }}</td>
                                    <td class="px-3 py-3 text-gray-700">{{ $job->vehicle->registration_number ?? $job->vehicle->registration_no ?? '' }}</td>
                                    <td class="px-3 py-3 text-gray-700">{{ $job->service_type ?? $job->title }}</td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $badge($job->status) }}">
                                            {{ $label($job->status) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-right text-gray-700">
                                        Ksh {{ number_format($job->final_cost ?? 0, 2) }}
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <a href="{{ route('jobs.edit', $job) }}"
                                           class="inline-flex items-center gap-1 text-sm text-indigo-600 hover:underline">
                                            <span>View</span>
                                            <x-lucide-arrow-up-right class="w-4 h-4" />
                                        </a>
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

{{-- Create Job Modal --}}
<div id="createJobModal"
     class="fixed inset-0 z-50 hidden"
     aria-hidden="true">
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/40" onclick="closeCreateJobModal()"></div>

    {{-- Panel --}}
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-3xl rounded-2xl bg-white shadow-xl overflow-hidden
           max-h-[90vh] flex flex-col">

            <div class="flex items-center justify-between border-b px-5 py-4">
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-gray-900">Create Job</h3>
                    <p class="mt-0.5 text-sm text-gray-500">Fill the form below to create a new job.</p>
                </div>

                <button type="button"
                        class="inline-flex items-center justify-center rounded-lg p-2 text-gray-500 hover:bg-gray-100"
                        onclick="closeCreateJobModal()"
                        aria-label="Close">
                    <x-lucide-x class="w-5 h-5" />
                </button>
            </div>

            <div id="createJobModalBody" class="p-5 overflow-y-auto flex-1">
                {{-- Loaded via fetch --}}
                <div class="text-sm text-gray-600">Loading…</div>
            </div>
        </div>
    </div>
</div>

<script>
/*
============================================================
GarageSuite: Create Job Modal (Step 1 Only Launcher)
- Modal is ONLY for Step 1 (payer type)
- After Step 1 → redirect to full page
- Prevents Step 2/3 from loading in modal
============================================================
*/

let __createJobModalOpen = false;

function initModalAlpine(body) {
    try {
        if (!body) return;
        if (!window.Alpine) return;

        if (typeof window.Alpine.initTree === 'function') {

            if (typeof window.Alpine.destroyTree === 'function') {
                try { window.Alpine.destroyTree(body); } catch (e) {}
            }

            requestAnimationFrame(() => {
                try { window.Alpine.initTree(body); } catch (e) {
                    console.warn('Alpine init skipped:', e);
                }
            });

            return;
        }

    } catch (e) {
        console.warn('Alpine init skipped:', e);
    }
}


function showModalAlert(body, message, type = 'error') {
    const styles = {
        error:  'border-red-200 bg-red-50 text-red-700',
        warn:   'border-amber-200 bg-amber-50 text-amber-800',
        info:   'border-blue-200 bg-blue-50 text-blue-800',
        success:'border-emerald-200 bg-emerald-50 text-emerald-800',
    };

    const cls = styles[type] || styles.error;

    body.insertAdjacentHTML('afterbegin',
        `<div class="mb-4 rounded-lg border ${cls} p-3 text-sm">
            ${message}
        </div>`
    );
}


async function readResponseSmart(resp) {
    const ct = (resp.headers.get('content-type') || '').toLowerCase();

    if (ct.includes('application/json')) {
        return { kind: 'json', data: await resp.json() };
    }

    return { kind: 'text', data: await resp.text() };
}


/*
------------------------------------------------------------
HOOK MODAL FORM
- Step 1 submits via AJAX
- If server returns next_url → EXIT MODAL → FULL PAGE
------------------------------------------------------------
*/
function hookModalForm(body) {

    const form = body.querySelector('form');
    if (!form) return;

    if (form.dataset.__hooked === '1') return;
    form.dataset.__hooked = '1';

    form.addEventListener('submit', async (e) => {

        e.preventDefault();

        const submitBtn = form.querySelector('[type="submit"]');

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.dataset.__oldText = submitBtn.innerHTML;
            submitBtn.innerHTML = 'Saving…';
        }

        try {

            const csrf = document.querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '';

            const resp = await fetch(form.action, {
                method: (form.method || 'POST').toUpperCase(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json, text/html;q=0.9, */*;q=0.8',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                },
                body: new FormData(form),
                credentials: 'same-origin',
                redirect: 'follow',
            });


            // Hard redirect (Laravel redirect response)
            if (resp.redirected) {
                closeCreateJobModal();
                window.location.href = resp.url;
                return;
            }


            const parsed = await readResponseSmart(resp);


            // Validation
            if (resp.status === 422) {

                if (parsed.kind === 'json' && parsed.data?.html) {
                    body.innerHTML = parsed.data.html;
                    initModalAlpine(body);
                    hookModalForm(body);
                    return;
                }

                showModalAlert(body, 'Please fix the highlighted errors and try again.', 'error');
                return;
            }


            if (!resp.ok) {
                showModalAlert(body, 'Save failed. Please try again.', 'error');
                return;
            }


            // ============================
            // SUCCESS (JSON)
            // ============================
            if (parsed.kind === 'json') {

                const data = parsed.data;


                // 🚨 MAIN CHANGE: EXIT MODAL AFTER STEP 1
                if (data?.next_url) {

                    closeCreateJobModal();

                    // Small delay for clean UI
                    setTimeout(() => {
                        window.location.href = data.next_url;
                    }, 50);

                    return;
                }


                if (data?.success) {
                    closeCreateJobModal();
                    window.location.reload();
                    return;
                }


                if (data?.html) {
                    body.innerHTML = data.html;
                    initModalAlpine(body);
                    hookModalForm(body);
                    return;
                }


                showModalAlert(body, 'Unexpected response from server. Please try again.', 'warn');
                return;
            }


            // ============================
            // SUCCESS (HTML)
            // ============================
            const html = parsed.data;

            if (html && String(html).trim().length) {
                body.innerHTML = html;
                initModalAlpine(body);
                hookModalForm(body);
                return;
            }


            // Fallback
            closeCreateJobModal();
            window.location.reload();


        } catch (err) {

            showModalAlert(
                body,
                (err && err.message) ? err.message : 'Something went wrong.',
                'error'
            );

        } finally {

            const btn = form.querySelector('[type="submit"]');

            if (btn) {
                btn.disabled = false;

                if (btn.dataset.__oldText) {
                    btn.innerHTML = btn.dataset.__oldText;
                }
            }
        }

    });
}



/*
------------------------------------------------------------
OPEN MODAL (STEP 1 ONLY)
------------------------------------------------------------
*/
function openCreateJobModal(url) {

    const modal = document.getElementById('createJobModal');
    const body  = document.getElementById('createJobModalBody');

    if (!modal || !body) return;

    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');

    __createJobModalOpen = true;

    body.innerHTML = `<div class="text-sm text-gray-600">Loading…</div>`;


    // Force modal=1 for Step 1 only
    try {
        const u = new URL(url, window.location.origin);

        if (!u.searchParams.has('modal')) {
            u.searchParams.set('modal', '1');
        }

        url = u.toString();

    } catch (e) {}


    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html',
        },
        credentials: 'same-origin',
        redirect: 'follow',
    })

    .then(async (r) => {

        const text = await r.text();

        if (!r.ok) {

            body.innerHTML = `
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 space-y-2">
                    <div class="font-semibold">Failed to load the create job form</div>
                    <div><b>Status:</b> ${r.status} ${r.statusText}</div>
                    <pre class="text-xs bg-white/70 border p-2 rounded">
${escapeHtml(text.slice(0, 800))}
                    </pre>
                </div>
            `;

            throw new Error('load_failed');
        }

        return text;
    })

    .then((html) => {

        body.innerHTML = html;

        initModalAlpine(body);

        hookModalForm(body);
    })

    .catch((e) => {

        body.innerHTML = `
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                Failed to load the create job form. ${e?.message || ''}
            </div>
        `;
    });
}



/*
------------------------------------------------------------
UTILS
------------------------------------------------------------
*/
function escapeHtml(str) {

    return String(str || '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}


function closeCreateJobModal() {

    const modal = document.getElementById('createJobModal');
    const body  = document.getElementById('createJobModalBody');

    if (!modal || !body) return;

    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');

    document.body.classList.remove('overflow-hidden');

    __createJobModalOpen = false;

    body.innerHTML = `<div class="text-sm text-gray-600">Loading…</div>`;
}


// ESC key closes modal
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && __createJobModalOpen) {
        closeCreateJobModal();
    }
});
</script>


 
</x-app-layout>
