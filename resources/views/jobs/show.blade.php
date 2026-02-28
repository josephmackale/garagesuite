{{-- resources/views/jobs/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">
                    Job Details
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Overview, costs, parts used, and invoice.
                </p>
            </div>

            @php
                $isLocked = in_array(($job->status ?? 'pending'), ['completed', 'cancelled'], true);
            @endphp

            <div class="flex items-center gap-2">
                <a href="{{ route('jobs.index') }}"
                   class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                    <x-lucide-arrow-left class="w-4 h-4" />
                    Back
                </a>

                @if(! $isLocked)
                    <a href="{{ route('jobs.edit', $job) }}"
                       class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-indigo-600 text-xs font-semibold text-white hover:bg-indigo-700">
                        <x-lucide-pencil class="w-4 h-4" />
                        Edit
                    </a>
                @else
                    <span class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-100 text-slate-600 text-xs font-semibold cursor-not-allowed">
                        <x-lucide-lock class="w-4 h-4" />
                        Locked
                    </span>
                @endif
            </div>

        </div>
    </x-slot>

    @php
        $status = $job->status ?? 'pending';

        $statusColor = match ($status) {
            'pending'     => 'bg-yellow-50 text-yellow-800 ring-1 ring-yellow-100',
            'in_progress' => 'bg-blue-50 text-blue-800 ring-1 ring-blue-100',
            'completed'   => 'bg-green-50 text-green-800 ring-1 ring-green-100',
            'cancelled'   => 'bg-slate-50 text-slate-700 ring-1 ring-slate-100',
            default       => 'bg-slate-50 text-slate-700 ring-1 ring-slate-100',
        };

        $labour = (float) ($job->labour_cost ?? 0);
        $parts  = (float) ($job->parts_cost ?? 0);
        $total  = (float) ($job->final_cost ?? ($labour + $parts));

        $jobDate = $job->job_date ?? $job->created_at;

        // Attachments
        $attachedPhotos = $job->mediaAttachments ?? collect();
    @endphp

    {{-- Invoice Panel (appears when completed) --}}
    @php
        $jobStatus = $job->status ?? 'pending';
        $invoice = $job->invoice ?? null;
    @endphp

    @if($jobStatus === 'completed')
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                        <x-lucide-receipt class="w-4 h-4" />
                        Invoice
                    </h3>
                    <p class="mt-1 text-xs text-gray-500">
                        Job is completed — you can now issue an invoice.
                    </p>
                </div>

                @if($invoice)
                    <a href="{{ route('invoices.show', $invoice) }}"
                       class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                        <x-lucide-eye class="w-4 h-4" />
                        Open Invoice
                    </a>
                @else
                    <form method="POST" action="{{ route('jobs.invoice.store', $job) }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                            <x-lucide-plus class="w-4 h-4" />
                            Create Invoice
                        </button>
                    </form>
                @endif
            </div>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div class="rounded-xl border border-gray-200 p-3">
                    <div class="text-gray-500">Invoice Status</div>
                    <div class="mt-1 font-semibold text-gray-900">
                        {{ $invoice?->status ? ucfirst($invoice->status) : '—' }}
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 p-3">
                    <div class="text-gray-500">Payment</div>
                    <div class="mt-1 font-semibold text-gray-900">
                        {{ $invoice?->payment_status ? ucfirst($invoice->payment_status) : '—' }}
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 p-3">
                    <div class="text-gray-500">Total</div>
                    <div class="mt-1 font-semibold text-gray-900">
                        KES {{ number_format((float)($invoice?->total_amount ?? $job->final_cost ?? 0), 2) }}
                    </div>
                </div>
            </div>

            @if($invoice && ($invoice->status ?? 'draft') === 'draft')
                <div class="mt-4 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('invoices.issue', $invoice) }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800">
                            <x-lucide-send class="w-4 h-4" />
                            Issue Invoice
                        </button>
                    </form>

                    <a href="{{ route('invoices.pdf', $invoice) }}"
                       class="inline-flex items-center gap-2 px-3 py-2 rounded-full border border-gray-200 text-gray-700 text-xs font-semibold hover:bg-gray-50">
                        <x-lucide-download class="w-4 h-4" />
                        PDF
                    </a>
                </div>

                <p class="mt-2 text-[11px] text-gray-500">
                    Draft invoices can still be updated from job edits until issued.
                </p>
            @endif
        </div>
    @endif

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash --}}
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('info'))
                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg text-sm">
                    {{ session('info') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    <div class="font-semibold mb-1">Please fix the following:</div>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Summary card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">

                    {{-- Left --}}
                    <div class="space-y-2">
                        <div class="text-sm font-medium text-gray-500">
                            {{ $job->job_number ?? 'Job #'.$job->id }}
                        </div>

                        <div class="text-lg font-semibold text-gray-900">
                            {{ strtoupper($job->vehicle?->registration_number ?? '—') }}
                            @if($job->vehicle)
                                <span class="text-gray-400">–</span>
                                {{ $job->vehicle->make }} {{ $job->vehicle->model }}
                            @endif
                        </div>

                        <div class="text-sm text-gray-600">
                            {{ $job->vehicle?->customer?->name ?? $job->customer?->name ?? '—' }}
                            @if($job->vehicle?->customer?->phone || $job->customer?->phone)
                                <span class="text-gray-400">·</span>
                                {{ $job->vehicle?->customer?->phone ?? $job->customer?->phone }}
                            @endif
                        </div>

                        <dl class="mt-3 flex flex-wrap gap-x-8 gap-y-2 text-xs text-gray-600">
                            <div>
                                <dt class="font-medium text-gray-500">Job Date</dt>
                                <dd>{{ optional($jobDate)->format('d M Y') ?? '—' }}</dd>
                            </div>

                            <div>
                                <dt class="font-medium text-gray-500">Service Type</dt>
                                <dd>{{ $job->service_type ?: '—' }}</dd>
                            </div>

                            <div>
                                <dt class="font-medium text-gray-500">Mileage</dt>
                                <dd>{{ $job->mileage ? number_format($job->mileage).' km' : '—' }}</dd>
                            </div>

                            <div>
                                <dt class="font-medium text-gray-500">Status</dt>
                                <dd>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusColor }}">
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Right actions --}}
                    <div class="flex flex-wrap gap-2 justify-end">
                        {{-- Quick Status Actions --}}
                        @php $status = $job->status ?? 'pending'; @endphp

                        <div class="flex flex-wrap gap-2">
                            @if($status === 'pending')
                                <form method="POST" action="{{ route('jobs.status.update', $job) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="in_progress">
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700">
                                        <x-lucide-play class="w-4 h-4" />
                                        Mark In Progress
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('jobs.status.update', $job) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-300">
                                        <x-lucide-x-circle class="w-4 h-4" />
                                        Cancel
                                    </button>
                                </form>
                            @elseif($status === 'in_progress')
                                <form method="POST" action="{{ route('jobs.status.update', $job) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 px-3 py-2 rounded-full
                                                   bg-emerald-600 text-white text-xs font-semibold
                                                   hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                        <x-lucide-check-circle class="w-4 h-4" />
                                        Mark Completed
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('jobs.status.update', $job) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-300">
                                        <x-lucide-x-circle class="w-4 h-4" />
                                        Cancel
                                    </button>
                                </form>
                            @elseif($status === 'completed')
                                <span class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-green-50 text-green-800 text-xs font-semibold ring-1 ring-green-100">
                                    <x-lucide-lock class="w-4 h-4" />
                                    Completed (Locked)
                                </span>
                            @elseif($status === 'cancelled')
                                <span class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-slate-50 text-slate-700 text-xs font-semibold ring-1 ring-slate-100">
                                    <x-lucide-lock class="w-4 h-4" />
                                    Cancelled (Locked)
                                </span>
                            @endif
                        </div>

                        {{-- Invoice --}}
                        @php $canInvoice = (($job->status ?? 'pending') === 'completed'); @endphp

                        @if ($job->invoice)
                            <a href="{{ route('invoices.show', $job->invoice) }}"
                               class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                                <x-lucide-receipt class="w-4 h-4" />
                                View Invoice
                            </a>
                        @else
                            <form method="POST" action="{{ route('jobs.invoice.store', $job) }}">
                                @csrf
                                <button type="submit"
                                        @disabled(! $canInvoice)
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-full text-xs font-semibold
                                            {{ $canInvoice ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-gray-200 text-gray-500 cursor-not-allowed' }}">
                                    <x-lucide-plus class="w-4 h-4" />
                                    Create Invoice
                                </button>

                                @unless($canInvoice)
                                    <p class="mt-2 text-xs text-gray-500">
                                        Invoice can be created only after the job is marked <span class="font-semibold">Completed</span>.
                                    </p>
                                @endunless
                            </form>
                        @endif

                        {{-- Job Card --}}
                        @if (Route::has('jobs.job-card'))
                            <a href="{{ route('jobs.job-card', $job) }}"
                               class="inline-flex items-center gap-2 px-3 py-2 rounded-full border border-gray-200 text-gray-700 bg-white text-xs font-semibold hover:bg-gray-50">
                                <x-lucide-eye class="w-4 h-4" />
                                Job Card
                            </a>
                        @endif

                        @if (Route::has('jobs.job-card.download'))
                            <a href="{{ route('jobs.job-card.download', $job) }}"
                               class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800">
                                <x-lucide-download class="w-4 h-4" />
                                Download PDF
                            </a>
                        @endif
                    </div>

                </div>
            </div>

            {{-- Middle --}}
            <div class="grid gap-6 lg:grid-cols-3">

                {{-- Main column --}}
                <div class="lg:col-span-2 space-y-6">

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900">Job Narrative</h3>

                        <div>
                            <div class="text-xs font-medium text-gray-500 mb-1">Customer Complaint</div>
                            <div class="min-h-[56px] text-sm text-gray-800 border border-gray-200 rounded-lg px-3 py-2 bg-gray-50">
                                {{ $job->complaint ?: '—' }}
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-medium text-gray-500 mb-1">Diagnosis</div>
                                <div class="min-h-[56px] text-sm text-gray-800 border border-gray-200 rounded-lg px-3 py-2 bg-gray-50">
                                    {{ $job->diagnosis ?: '—' }}
                                </div>
                            </div>
                            <div>
                                <div class="text-xs font-medium text-gray-500 mb-1">Internal Notes</div>
                                <div class="min-h-[56px] text-sm text-gray-800 border border-gray-200 rounded-lg px-3 py-2 bg-gray-50">
                                    {{ $job->notes ?: '—' }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-medium text-gray-500 mb-1">Work Done</div>
                            <div class="min-h-[72px] text-sm text-gray-800 border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 whitespace-pre-wrap">
                                {{ $job->work_done ?: '—' }}
                            </div>
                        </div>
                    </div>

                    {{-- ✅ Job Photos (Vault Attach + Gallery) --}}
                    <div
                        x-data="{
                            openVault:false,
                            selected:[],
                            toggle(id){
                                if (this.selected.includes(id)) this.selected = this.selected.filter(x => x !== id);
                                else this.selected.push(id);
                            },
                            clear(){ this.selected = []; }
                        }"
                        class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Job Photos</h3>
                                <p class="mt-1 text-xs text-gray-500">
                                    Upload once in Vault, then attach to jobs and vehicles.
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="openVault=true"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800"
                            >
                                <x-lucide-image class="w-4 h-4" />
                                Attach from Vault
                            </button>
                        </div>

                        <div class="mt-4">
                            @if($attachedPhotos->count() === 0)
                                <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500">
                                    No photos attached yet.
                                </div>
                            @else
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                    @foreach($attachedPhotos as $att)
                                        @php $item = $att->mediaItem; @endphp
                                        @if($item)
                                            <div class="group relative rounded-xl border overflow-hidden bg-white">
                                                <div class="aspect-square bg-slate-100 overflow-hidden">
                                                    <img
                                                        src="{{ \Illuminate\Support\Facades\Storage::disk($item->disk)->url($item->path) }}"
                                                        class="w-full h-full object-cover group-hover:scale-105 transition"
                                                        alt="job photo"
                                                    >
                                                </div>

                                                <form
                                                    method="POST"
                                                    action="{{ route('vault.detach.job', [$job, $item]) }}"
                                                    class="absolute top-2 right-2"
                                                    onsubmit="return confirm('Detach this photo from the job?')"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="h-8 w-8 rounded-full bg-white/95 border text-slate-700 hover:text-slate-900">
                                                        ✕
                                                    </button>
                                                </form>

                                                <div class="p-2 text-[11px] text-slate-600 truncate">
                                                    {{ $item->original_name ?? 'photo' }}
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Vault Attach Modal --}}
                        <div x-show="openVault" x-cloak class="fixed inset-0 z-50">
                            <div class="absolute inset-0 bg-black/40" @click="openVault=false"></div>

                            <div class="absolute inset-x-0 top-10 mx-auto max-w-5xl px-4">
                                <div class="bg-white rounded-2xl shadow-xl border overflow-hidden">
                                    <div class="flex items-center justify-between px-5 py-4 border-b">
                                        <div class="font-semibold text-slate-900">Attach photos to this job</div>
                                        <button type="button" class="text-slate-500 hover:text-slate-900" @click="openVault=false">✕</button>
                                    </div>

                                    <form method="POST" action="{{ route('vault.attach.job', $job) }}">
                                        @csrf

                                        <div class="p-5">
                                            <div class="flex items-center justify-between mb-4">
                                                <div class="text-sm text-slate-600">
                                                    Select photos (<span x-text="selected.length"></span> selected)
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    <button type="button" class="text-sm text-slate-600 hover:text-slate-900" @click="clear()">
                                                        Clear
                                                    </button>

                                                    <button
                                                        type="submit"
                                                        :disabled="selected.length === 0"
                                                        class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold disabled:opacity-40 disabled:cursor-not-allowed"
                                                    >
                                                        Attach Selected
                                                    </button>
                                                </div>
                                            </div>

                                            <template x-for="id in selected" :key="id">
                                                <input type="hidden" name="media_item_ids[]" :value="id">
                                            </template>

                                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 max-h-[60vh] overflow-y-auto pr-1">
                                                @foreach($vaultItems as $v)
                                                    <button
                                                        type="button"
                                                        class="relative rounded-xl border overflow-hidden group"
                                                        :class="selected.includes({{ $v->id }}) ? 'ring-2 ring-slate-900 border-slate-900' : 'border-slate-200'"
                                                        @click="toggle({{ $v->id }})"
                                                    >
                                                        <div class="aspect-square bg-slate-100 overflow-hidden">
                                                            <img
                                                                src="{{ \Illuminate\Support\Facades\Storage::disk($v->disk)->url($v->path) }}"
                                                                class="w-full h-full object-cover group-hover:scale-105 transition"
                                                                alt="vault image"
                                                            >
                                                        </div>

                                                        <div class="p-2 text-[11px] text-slate-600 truncate text-left">
                                                            {{ $v->original_name ?? 'photo' }}
                                                        </div>

                                                        <div
                                                            class="absolute top-2 right-2 h-6 w-6 rounded-full bg-white/95 border flex items-center justify-center text-xs font-bold"
                                                            :class="selected.includes({{ $v->id }}) ? 'text-slate-900' : 'text-slate-400'"
                                                        >
                                                            ✓
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>

                                            <div class="mt-4">
                                                {{ $vaultItems->links() }}
                                            </div>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Parts --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-900">Parts Used</h3>
                            <a href="{{ route('jobs.edit', $job) }}#parts"
                               class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                Edit parts
                            </a>
                        </div>

                        @if($job->partItems && $job->partItems->count())
                            <div class="overflow-x-auto -mx-2 sm:mx-0">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Description</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Qty</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Unit</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Line Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        @foreach($job->partItems as $item)
                                            <tr>
                                                <td class="px-4 py-2 text-gray-900">
                                                    {{ $item->description }}
                                                    @if($item->inventory_item_id)
                                                        <span class="ml-2 inline-flex items-center rounded-full bg-slate-100 text-slate-700 px-2 py-0.5 text-[11px] font-medium">
                                                            inventory
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2 text-right text-gray-700">{{ number_format((float)$item->quantity, 2) }}</td>
                                                <td class="px-4 py-2 text-right text-gray-700">{{ number_format((float)$item->unit_price, 2) }}</td>
                                                <td class="px-4 py-2 text-right font-semibold text-gray-900">{{ number_format((float)$item->line_total, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No parts captured yet.</p>
                        @endif
                    </div>

                </div>

                {{-- Side panel --}}
                <div class="space-y-6">

                    {{-- Cost breakdown --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                        <h3 class="text-sm font-semibold text-gray-900">Cost Breakdown</h3>

                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <dt class="text-gray-600">Labour</dt>
                                <dd class="font-semibold text-gray-900">KES {{ number_format($labour, 2) }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-gray-600">Parts</dt>
                                <dd class="font-semibold text-gray-900">KES {{ number_format($parts, 2) }}</dd>
                            </div>
                            <div class="border-t border-dashed border-gray-200 pt-2 mt-2 flex items-center justify-between">
                                <dt class="text-gray-900 font-semibold">Total</dt>
                                <dd class="text-lg font-bold text-gray-900">KES {{ number_format($total, 2) }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Meta --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-xs text-gray-600 space-y-2">
                        <h3 class="text-sm font-semibold text-gray-900">Job Meta</h3>
                        <div class="flex items-center gap-2">
                            <x-lucide-clock class="w-4 h-4 text-gray-400" />
                            <span>Created {{ $job->created_at?->diffForHumans() ?? '—' }}</span>
                        </div>
                        @if($job->updated_at && $job->updated_at->ne($job->created_at))
                            <div class="flex items-center gap-2">
                                <x-lucide-refresh-ccw class="w-4 h-4 text-gray-400" />
                                <span>Updated {{ $job->updated_at?->diffForHumans() ?? '—' }}</span>
                            </div>
                        @endif
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
