@php
    // Create vs Edit
    $isEdit = isset($job) && $job?->exists;

    // ✅ On Edit, always show gated body (narratives, work details, etc.)
    $showGatedBody =
        $isEdit
        || (old('mileage') !== null && old('service_type') !== null)
        || (!empty($job?->mileage) && !empty($job?->service_type));

    // Determine selected vehicle on load
    $selectedVehicle = null;
    $selectedVehicleId = old('vehicle_id', $selectedVehicleId ?? ($job->vehicle_id ?? null));

    if ($selectedVehicleId && isset($vehicles)) {
        $selectedVehicle = $vehicles->firstWhere('id', $selectedVehicleId);
    }

    // Payer gate (no default on create; force explicit selection)
    $payerType = old('payer_type', $job->payer_type ?? '');

    // Parts rows (keep at least 1)
    $partItems = old('part_items');

    if ($partItems === null) {
        $partItems = $job->relationLoaded('partItems')
            ? $job->partItems->map(fn($i) => [
                'description'       => $i->description,
                'quantity'          => $i->quantity,
                'unit_price'        => $i->unit_price,
                'line_total'        => $i->line_total,
                'inventory_item_id' => $i->inventory_item_id,
            ])->toArray()
            : $job->partItems->map(fn($i) => [
                'description'       => $i->description,
                'quantity'          => $i->quantity,
                'unit_price'        => $i->unit_price,
                'line_total'        => $i->line_total,
                'inventory_item_id' => $i->inventory_item_id,
            ])->toArray();
    }

    $partItems = $partItems ?? [];
    while (count($partItems) < 1) {
        $partItems[] = [
            'description' => '',
            'quantity' => '',
            'unit_price' => '',
            'line_total' => '',
            'inventory_item_id' => null,
        ];
    }

    $labourTotal = $job->labour_cost ?? 0;
    $partsTotal  = $job->parts_cost ?? 0;
    $finalTotal  = $job->final_cost ?? 0;
@endphp

{{-- ✅ WRAP ENTIRE PARTIAL FOR SCOPED app.js GATING --}}
<div data-job-form-root>

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="mb-4">
            <div class="bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3 rounded-lg">
                <div class="font-semibold mb-1">There were some problems with your input:</div>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- SECTION 1: Job Overview (3-row layout) --}}
    <div class="space-y-2">

        {{-- Hidden vehicle id always submitted --}}
        @if($selectedVehicleId)
            <input type="hidden" name="vehicle_id" value="{{ $selectedVehicleId }}">
        @endif

        <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">

            @if($selectedVehicle)
                @php
                    $customer = $selectedVehicle->customer;

                    $customerName = trim($customer?->name ?? 'Unknown Customer');
                    $initials = collect(preg_split('/\s+/', $customerName))
                        ->filter()
                        ->take(2)
                        ->map(fn ($p) => strtoupper(mb_substr($p, 0, 1)))
                        ->implode('');

                    $vehicleImage =
                        data_get($selectedVehicle, 'image_url')
                        ?? data_get($selectedVehicle, 'photo_url')
                        ?? data_get($selectedVehicle, 'image')
                        ?? null;
                @endphp

                {{-- ROW 1: Vehicle Details --}}
                <div class="p-5 sm:p-6 border-b border-slate-100">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">

                        {{-- LEFT: Vehicle Photo (true fill frame) --}}
                        <div class="lg:col-span-4">
                            <div class="rounded-lg overflow-hidden border border-slate-200 bg-slate-100">
                                <div class="relative aspect-[16/10] w-full">
                                    @if($vehicleImage)
                                        <img
                                            src="{{ $vehicleImage }}"
                                            alt="Vehicle photo"
                                            class="absolute inset-0 w-full h-full object-cover"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="absolute inset-0 flex items-center justify-center bg-slate-100">
                                            <div class="text-center px-6">
                                                <div class="text-sm font-semibold text-slate-700">
                                                    No vehicle photo
                                                </div>
                                                <div class="mt-1 text-xs text-slate-500">
                                                    Add a photo in Vehicle profile
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- RIGHT: Vehicle Details (rows, no cards) --}}
                        <div class="lg:col-span-8">

                            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                Vehicle Details
                            </div>

                            <div class="mt-2 text-2xl font-extrabold text-slate-900 tracking-tight">
                                {{ strtoupper($selectedVehicle->registration_number) }}
                            </div>

                            <div class="mt-1 text-sm font-semibold text-slate-700">
                                {{ $selectedVehicle->make }} {{ $selectedVehicle->model }}
                                @if(!empty($selectedVehicle->year))
                                    <span class="text-slate-300 font-bold mx-2">|</span>
                                    <span class="text-slate-700">{{ $selectedVehicle->year }}</span>
                                @endif
                            </div>

                            @php
                                $vehMileage =
                                    data_get($selectedVehicle, 'mileage')
                                    ?? data_get($selectedVehicle, 'odometer')
                                    ?? null;

                                $vehVin =
                                    $selectedVehicle->vin
                                    ?? $selectedVehicle->chassis_number
                                    ?? null;
                            @endphp

                            <div class="mt-4 space-y-3 text-sm">
                                <div class="flex items-start gap-4">
                                    <div class="w-24 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                        Mileage
                                    </div>
                                    <div class="font-semibold text-slate-900">
                                        {{ $vehMileage !== null && $vehMileage !== '' ? number_format((int) $vehMileage) . ' km' : '—' }}
                                    </div>
                                </div>

                                <div class="flex items-start gap-4">
                                    <div class="w-24 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                        VIN / Chassis
                                    </div>
                                    <div class="font-semibold text-slate-900 break-all">
                                        {{ $vehVin ?: '—' }}
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

                {{-- ROW 2: Customer Details --}}
                <div class="p-5 sm:p-6 border-b border-slate-100">
                    @php
                        $customerName = trim($customer?->name ?? 'Unknown Customer');
                        $initials = collect(preg_split('/\s+/', $customerName))
                            ->filter()
                            ->take(2)
                            ->map(fn ($p) => strtoupper(mb_substr($p, 0, 1)))
                            ->implode('');
                    @endphp

                    <div class="text-center">
                        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Customer Details</div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">

                        {{-- LEFT: Avatar + Name --}}
                        <div class="lg:col-span-4 flex flex-col items-center justify-center text-center">
                            <div class="w-14 h-14 rounded-full bg-indigo-600 text-white flex items-center justify-center font-extrabold text-lg">
                                {{ $initials ?: 'C' }}
                            </div>

                            <div class="mt-3 text-sm font-extrabold text-slate-900">
                                {{ $customer?->name ?? '—' }}
                            </div>

                            <div class="mt-1 text-xs text-slate-500">
                                Linked customer record
                            </div>
                        </div>

                        {{-- MIDDLE: Separator --}}
                        <div class="hidden lg:flex lg:col-span-1 justify-center">
                            <div class="w-px bg-slate-200 my-4"></div>
                        </div>

                        {{-- RIGHT: Phone + Email (rows, no cards) --}}
                        <div class="lg:col-span-7 flex items-center">
                            <div class="w-full space-y-4">

                                <div class="flex items-start gap-4">
                                    <div class="w-20 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                        Phone
                                    </div>
                                    <div class="text-sm font-semibold text-slate-900">
                                        {{ $customer?->phone ?? '—' }}
                                    </div>
                                </div>

                                <div class="flex items-start gap-4">
                                    <div class="w-20 text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                        Email
                                    </div>
                                    <div class="text-sm font-semibold text-slate-900 break-all">
                                        {{ $customer?->email ?? '—' }}
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>

                {{-- ROW 3: Job Basics + Payer (payer is last) --}}
                <div class="p-5 sm:p-6 bg-white">
                    <div class="mt-4 grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">

                        {{-- Job Basics --}}
                        <div class="lg:col-span-8">
                            <div class="text-center">
                                <span class="inline-block text-xs font-semibold text-slate-500 uppercase tracking-wider px-3 py-1 rounded-full bg-slate-50 border border-slate-200">
                                    Job Basics
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <x-input-label for="job_date" value="Job Date" />

                                    <div class="relative mt-1">
                                        {{-- Icon --}}
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                            <x-lucide-calendar class="w-4.5 h-4.5" />
                                        </div>

                                        {{-- Input --}}
                                        <x-text-input
                                            id="job_date"
                                            name="job_date"
                                            type="date"
                                            class="block w-full pl-10 pr-3 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                                            value="{{ old('job_date', $job->job_date ? $job->job_date->format('Y-m-d') : now()->format('Y-m-d')) }}"
                                            required
                                        />
                                    </div>

                                    <x-input-error :messages="$errors->get('job_date')" class="mt-1" />
                                </div>


                                {{-- STATUS (LOCKED RULES) --}}
                                <div>
                                    <x-input-label for="status" value="Status" />

                                    @if(! $isEdit)
                                        <input type="hidden" name="status" value="pending">
                                        <div class="mt-1 inline-flex items-center gap-2 rounded-full bg-yellow-50 text-yellow-800 ring-1 ring-yellow-100 px-3 py-1 text-xs font-semibold">
                                            <x-lucide-circle-dot class="w-4 h-4" />
                                            Open
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">New jobs always start as Open.</p>
                                    @else
                                        @php
                                            $current = old('status', $job->status ?? 'pending');

                                            $allowed = match ($job->status ?? 'pending') {
                                                'pending'     => ['pending', 'in_progress', 'cancelled'],
                                                'in_progress' => ['in_progress', 'completed', 'cancelled'],
                                                default       => [$job->status ?? 'pending'],
                                            };

                                            $labels = [
                                                'pending' => 'Open',
                                                'in_progress' => 'In Progress',
                                                'completed' => 'Completed',
                                                'cancelled' => 'Cancelled',
                                            ];
                                        @endphp

                                        @if(in_array(($job->status ?? 'pending'), ['completed','cancelled'], true))
                                            <input type="hidden" name="status" value="{{ $job->status }}">
                                            <div class="mt-1 inline-flex items-center gap-2 rounded-full bg-slate-50 text-slate-700 ring-1 ring-slate-100 px-3 py-1 text-xs font-semibold">
                                                <x-lucide-lock class="w-4 h-4" />
                                                {{ $labels[$job->status] ?? ucfirst(str_replace('_',' ', $job->status)) }}
                                            </div>
                                            <p class="mt-1 text-xs text-slate-500">This job is locked and status cannot change.</p>
                                        @else
                                            <select
                                                id="status"
                                                name="status"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                                required
                                            >
                                                @foreach ($allowed as $st)
                                                    <option value="{{ $st }}" @selected($current === $st)>
                                                        {{ $labels[$st] ?? ucfirst(str_replace('_',' ', $st)) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <x-input-error :messages="$errors->get('status')" class="mt-1" />
                                        @endif
                                    @endif
                                </div>

                                @if(empty($hideBasics))
                                    <div>
                                        <x-input-label for="mileage" value="Mileage" />
                                        <x-text-input
                                            id="mileage"
                                            name="mileage"
                                            type="number"
                                            class="mt-1 block w-full"
                                            value="{{ old('mileage', $job->mileage) }}"
                                            placeholder="e.g. 205000"
                                        />
                                        <x-input-error :messages="$errors->get('mileage')" class="mt-1" />
                                    </div>

                                    <div>
                                        <x-input-label for="service_type" value="Service Type" />
                                        <x-text-input
                                            id="service_type"
                                            name="service_type"
                                            type="text"
                                            class="mt-1 block w-full"
                                            placeholder="e.g. Full service, diagnostics"
                                            value="{{ old('service_type', $job->service_type) }}"
                                        />
                                        <x-input-error :messages="$errors->get('service_type')" class="mt-1" />
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Payer fields appear here (gated) --}}
                        <div class="lg:col-span-4">
                            <div id="payerFields" style="display:none;">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs font-semibold text-slate-600 uppercase tracking-wide">
                                        Payer Details
                                    </div>

                                    {{-- INDIVIDUAL --}}
                                    <div id="payer_individual" style="display:none;">
                                        <div class="mt-2 text-sm text-slate-700 font-semibold">Individual (Customer)</div>
                                        <div class="mt-1 text-xs text-slate-600">
                                            Customer will pay directly. No extra payer fields needed.
                                        </div>
                                    </div>

                                    {{-- COMPANY --}}
                                    <div id="payer_company" style="display:none;" class="mt-3">
                                        @include('jobs.partials.payers.company')
                                    </div>

                                    {{-- INSURANCE --}}
                                    <div id="payer_insurance" style="display:none;" class="mt-3">
                                        @include('jobs.partials.payers.insurance')
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            @else
                <div class="p-5 sm:p-6">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        No vehicle selected. Please create/select a vehicle first.
                    </div>
                </div>
            @endif
        </div>

        {{-- ========================= --}}
        {{-- GATED BODY (SECTION 2+) --}}
        {{-- ========================= --}}
        <div id="jobFormBody" style="{{ $showGatedBody ? '' : 'display:none;' }}">

            <hr class="my-6">

            {{-- Complaint (shared) --}}
            <div class="mb-6">
                <x-input-label for="complaint" value="Customer Complaint" />
                <textarea
                    id="complaint"
                    name="complaint"
                    rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    placeholder="What does the customer report?"
                >{{ old('complaint', $job->complaint) }}</textarea>
                <x-input-error :messages="$errors->get('complaint')" class="mt-1" />
            </div>

            {{-- SECTION 2: Work Details --}}
            <div class="space-y-2">
                <h3 class="text-base font-semibold text-gray-900">Work Details</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="diagnosis" value="Diagnosis" />
                        <textarea
                            id="diagnosis"
                            name="diagnosis"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            placeholder="What did you find after inspection?"
                        >{{ old('diagnosis', $job->diagnosis) }}</textarea>
                        <x-input-error :messages="$errors->get('diagnosis')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="notes" value="Internal Notes" />
                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            placeholder="Internal notes for your team."
                        >{{ old('notes', $job->notes) }}</textarea>
                        <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                    </div>
                </div>
            </div>

            <hr class="my-6">

            {{-- Work done --}}
            <div class="mb-4" id="labour">
                <x-input-label for="work_done" value="Work Done (description)" />
                <textarea
                    id="work_done"
                    name="work_done"
                    rows="4"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    placeholder="Describe all work carried out..."
                >{{ old('work_done', $job->work_done ?? '') }}</textarea>
            </div>

            {{-- Labour: single cost field --}}
            <div class="mb-4">
                <x-input-label for="labour_cost" value="Labour Cost" />
                <div class="mt-1 flex items-center gap-2">
                    <span class="text-sm text-gray-500">KES</span>
                    <input
                        type="number"
                        name="labour_cost"
                        id="labour_cost"
                        step="0.01"
                        class="w-40 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        placeholder="0.00"
                        value="{{ old('labour_cost', $job->labour_cost ?? '') }}"
                    />
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Put the total labour charge here. Details stay in “Work Done”.
                </p>
            </div>

            <hr class="my-6">

            {{-- SECTION 3: Part Items --}}
            <div
                id="parts"
                x-data="jobParts({
                    rows: @js($partItems),
                    inventory: @js($inventoryForParts ?? []),
                })"
                class="space-y-3"
            >
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Parts</h3>
                        <p class="text-xs text-gray-500">Add one line per part used. Empty rows will be ignored.</p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                        @click="addRow()"
                    >
                        <x-lucide-plus class="w-4 h-4" />
                        Add Part Row
                    </button>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600">Part / Description</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600 w-20">Qty</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600 w-28">Unit Price</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600 w-28">Line Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <template x-for="(row, index) in rows" :key="index">
                            <tr class="border-t border-gray-100">
                                <td class="px-3 py-2">
                                    <div class="flex items-stretch gap-2">
                                        <div class="flex-1">
                                            <input
                                                type="text"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                                :name="`part_items[${index}][description]`"
                                                x-model="row.description"
                                                placeholder="e.g. Oil filter"
                                            >
                                            <input type="hidden"
                                                   :name="`part_items[${index}][inventory_item_id]`"
                                                   x-model="row.inventory_item_id">
                                        </div>

                                        <div class="relative">
                                            <button
                                                type="button"
                                                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-2 py-2 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                                                @click="row.showPicker = !row.showPicker"
                                                title="Pick from inventory"
                                            >
                                                <x-lucide-package-plus class="w-4 h-4" />
                                            </button>

                                            <div
                                                x-show="row.showPicker"
                                                @click.outside="row.showPicker = false"
                                                x-transition
                                                class="absolute z-20 mt-1 w-72 max-h-64 overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg text-xs"
                                            >
                                                <div class="px-2 py-1 border-b border-gray-100 font-semibold text-gray-700">
                                                    Pick from inventory
                                                </div>

                                                <button
                                                    type="button"
                                                    class="w-full text-left px-3 py-2 text-gray-500 hover:bg-gray-50"
                                                    @click="pickFromInventory(index, null)"
                                                >
                                                    — Clear selection —
                                                </button>

                                                <template x-for="item in inventory" :key="item.id">
                                                    <button
                                                        type="button"
                                                        class="w-full text-left px-3 py-2 hover:bg-indigo-50"
                                                        @click="pickFromInventory(index, item.id)"
                                                    >
                                                        <div class="font-medium text-gray-900" x-text="item.name"></div>
                                                        <div class="text-[11px] text-gray-500">
                                                            KES <span x-text="formatMoney(item.price)"></span>
                                                        </div>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-3 py-2">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :name="`part_items[${index}][quantity]`"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm text-right"
                                        x-model.number="row.quantity"
                                    >
                                </td>

                                <td class="px-3 py-2">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :name="`part_items[${index}][unit_price]`"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm text-right"
                                        x-model.number="row.unit_price"
                                    >
                                </td>

                                <td class="px-3 py-2">
                                    <input
                                        type="text"
                                        readonly
                                        class="block w-full rounded-md border-gray-200 bg-gray-50 text-right text-sm"
                                        :value="formatMoney((Number(row.quantity || 0) * Number(row.unit_price || 0)))"
                                        placeholder="auto"
                                    >
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
                function jobParts(initial) {
                    return {
                        rows: (initial.rows || []).map(r => ({
                            description: r.description ?? '',
                            quantity: r.quantity ?? null,
                            unit_price: r.unit_price ?? null,
                            line_total: r.line_total ?? null,
                            inventory_item_id: r.inventory_item_id ?? null,
                            showPicker: false,
                        })),
                        inventory: initial.inventory || [],

                        addRow() {
                            this.rows.push({
                                description: '',
                                quantity: null,
                                unit_price: null,
                                line_total: null,
                                inventory_item_id: null,
                                showPicker: false,
                            });
                        },

                        pickFromInventory(rowIndex, inventoryId) {
                            const row = this.rows[rowIndex];

                            if (!inventoryId) {
                                row.inventory_item_id = null;
                                row.showPicker = false;
                                return;
                            }

                            const item = this.inventory.find(i => i.id === inventoryId);
                            if (!item) return;

                            row.description       = item.name;
                            row.unit_price        = item.price;
                            row.inventory_item_id = item.id;
                            row.showPicker        = false;
                        },

                        formatMoney(value) {
                            const n = Number(value || 0);
                            return n.toLocaleString('en-KE', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2,
                            });
                        },
                    }
                }
            </script>

            <hr class="my-6">

            {{-- Totals & Actions --}}
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="text-sm text-gray-500">
                    Job totals will be recalculated when you save.
                </div>

                <div class="flex flex-col md:flex-row items-stretch md:items-center gap-4">
                    <div class="flex items-center gap-4 text-sm">
                        <div>
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Labour</div>
                            <div class="font-semibold text-gray-900">{{ number_format($labourTotal, 2) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Parts</div>
                            <div class="font-semibold text-gray-900">{{ number_format($partsTotal, 2) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Total</div>
                            <div class="font-semibold text-gray-900">{{ number_format($finalTotal, 2) }}</div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('jobs.index') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50">
                            <x-lucide-x class="w-4 h-4" />
                            Cancel
                        </a>

                        <x-primary-button class="inline-flex items-center gap-2">
                            <x-lucide-save class="w-4 h-4" />
                            {{ $submitLabel ?? 'Save' }}
                        </x-primary-button>
                    </div>
                </div>
            </div>

        </div>
        {{-- /GATED BODY --}}
    </div>

</div> {{-- /data-job-form-root --}}
