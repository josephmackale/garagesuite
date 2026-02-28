<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <div>
                <h2 class="font-semibold text-lg sm:text-xl text-gray-800 leading-tight">
                    Insurance Claim
                </h2>
                <div class="text-xs text-gray-500">
                    Job #{{ $job->id }} • Plate: {{ $job->vehicle->plate_number ?? '—' }}
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('jobs.insurance.show', $job) }}"
                   class="inline-flex items-center justify-center px-3 py-2 rounded-md text-gray-700 bg-white border border-gray-200 text-xs font-semibold"
                   title="Back to Insurance Workflow">
                    <x-lucide-shield class="w-4 h-4 mr-1" />
                    Insurance
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

        {{-- ✅ Claim Pack notices --}}
        @if(session('claim_pack_success'))
            <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('claim_pack_success') }}
            </div>
        @endif

        @if(session('claim_pack_error'))
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('claim_pack_error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <div class="font-semibold mb-1">Please fix:</div>
                <ul class="list-disc ml-5">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
            {{-- Summary --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="text-sm font-semibold text-gray-900">Claim Workspace (v1)</div>
                <div class="text-xs text-gray-500 mt-1">
                    This page will compile the Claim Pack (Approved scope + Invoice + Photos) for insurer submission.
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="rounded border border-gray-200 p-4">
                        <div class="text-xs text-gray-500">Customer</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $job->customer->name ?? '—' }}</div>
                        <div class="text-xs text-gray-600">{{ $job->customer->phone ?? '—' }}</div>
                    </div>

                    <div class="rounded border border-gray-200 p-4">
                        <div class="text-xs text-gray-500">Vehicle</div>
                        <div class="text-sm font-semibold text-gray-900">
                            {{ trim(($job->vehicle->make ?? '') . ' ' . ($job->vehicle->model ?? '')) ?: '—' }}
                        </div>
                        <div class="text-xs text-gray-600">Plate: {{ $job->vehicle->plate_number ?? '—' }}</div>
                    </div>

                    <div class="rounded border border-gray-200 p-4">
                        <div class="text-xs text-gray-500">Insurer</div>
                        <div class="text-sm font-semibold text-gray-900">
                            {{ $job->insuranceDetail?->insurer_name ?? $job->insuranceDetail?->insurer?->name ?? '—' }}
                        </div>
                        <div class="text-xs text-gray-600">Policy/Claim refs come next</div>
                    </div>
                </div>
            </div>

            {{-- ✅ Claim Pack Contents (Preview) --}}
            <div class="bg-white rounded-lg border p-4 mb-4">
                @php
                    // ✅ Canonical claim row (truth)
                    $claim = \App\Models\InsuranceClaim::query()
                        ->where('garage_id', (int) $job->garage_id)
                        ->where('job_id', (int) $job->id)
                        ->first();

                    // ✅ Keep $detail if you still use it elsewhere on this page
                    $detail = $job->insuranceDetail;

                    // ✅ Pack state now comes from insurance_claims.*
                    $packPath       = $claim?->pack_path;
                    $packVersion    = (int) ($claim?->pack_version ?? 0);
                    $packGeneratedAt = $claim?->pack_generated_at;

                    $hasPack = !empty($packPath);

                    // (Optional safety) verify file exists
                    /*
                    if ($hasPack) {
                        $hasPack =
                            \Illuminate\Support\Facades\Storage::disk('local')->exists($packPath)
                            || \Illuminate\Support\Facades\Storage::disk('public')->exists($packPath)
                            || \Illuminate\Support\Facades\Storage::exists($packPath);
                    }
                    */

                    // ✅ Submission state (use claim table when you add it; falls back safely)
                    $claimSubmittedAt = data_get($claim, 'submitted_at'); // or pack_submitted_at if you add it
                    $isSubmitted      = !empty($claimSubmittedAt);

                    // ✅ Derived UI permissions (no undefined vars)
                    $canGenerate = !$hasPack && !$isSubmitted;          // draft → can generate
                    $canSubmit   = $hasPack && !$isSubmitted;           // generated → can submit
                    $isLocked    = $isSubmitted;                        // submitted/locked (settled can be added later)
                @endphp
                <div class="flex items-start justify-between gap-4 mb-3">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">Claim Pack</div>

                        @if($hasPack)
                            <div class="text-xs text-green-700 bg-green-50 border border-green-200 rounded px-2 py-1 inline-flex items-center gap-2 mt-1">
                                <span class="font-semibold">{{ $isSubmitted ? 'Submitted' : 'Ready' }}</span>
                                <span>v{{ $packVersion }}</span>
                                <span class="text-green-800/70">•</span>
                                <span>{{ optional($packGeneratedAt)->format('d M Y H:i') ?? $packGeneratedAt }}</span>
                            </div>
                        @else
                            <div class="text-xs text-gray-600 bg-gray-50 border border-gray-200 rounded px-2 py-1 inline-flex items-center gap-2 mt-1">
                                <span class="font-semibold">Not generated yet</span>
                                <span>Generate to freeze the submission bundle.</span>
                            </div>
                        @endif

                        <div class="text-xs text-gray-500 mt-1">Approved scope + Invoice + Photos for insurer submission.</div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if(!$isSubmitted)
                            <form method="POST" action="{{ route('jobs.insurance.claim.generate', $job->id) }}">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold bg-gray-900 text-white">
                                    <x-lucide-file-down class="w-4 h-4 mr-1" />
                                    {{ $hasPack ? 'Regenerate (New Version)' : 'Generate Claim Pack' }}
                                </button>
                            </form>
                        @else
                            {{-- 🔒 Locked after submission --}}
                            <button type="button"
                                class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold bg-gray-200 text-gray-500 cursor-not-allowed"
                                disabled>
                                <x-lucide-lock class="w-4 h-4 mr-1" />
                                Locked After Submission
                            </button>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

                    {{-- Approval Pack --}}
                    <div class="border rounded-md p-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-900">Approval Pack (PDF)</div>
                                <div class="text-xs text-gray-500">
                                    @if($approvalPack)
                                        Latest pack ID: {{ $approvalPack->id }}
                                    @else
                                        Not available yet
                                    @endif
                                </div>
                            </div>

                            @if(!empty($approvalPdfUrl))
                                <a href="{{ $approvalPdfUrl }}"
                                class="inline-flex items-center px-3 py-1.5 text-xs rounded bg-gray-900 text-white">
                                    Approval PDF
                                </a>
                            @else
                                <span class="text-xs text-gray-400">Locked</span>
                            @endif
                        </div>
                    </div>

                    {{-- Invoice --}}
                    <div class="border rounded-md p-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-900">Invoice</div>
                                <div class="text-xs text-gray-500">
                                    @if($invoice)
                                        Invoice #{{ $invoice->invoice_number ?? $invoice->id }}
                                    @else
                                        Not created yet
                                    @endif
                                </div>
                            </div>

                            @if($invoice)
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('invoices.pdf', $invoice->id) }}"
                                    class="inline-flex items-center px-3 py-1.5 text-xs rounded border">
                                        PDF
                                    </a>
                                    <a href="{{ route('invoices.show', $invoice->id) }}"
                                    class="inline-flex items-center px-3 py-1.5 text-xs rounded border">
                                        Open
                                    </a>
                                </div>
                            @else
                                <span class="text-xs text-gray-400">Locked</span>
                            @endif
                        </div>
                    </div>

                    {{-- Inspection Photos --}}
                    <div class="border rounded-md p-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-900">Damage Photos (Before Repair)</div>
                                <div class="text-xs text-gray-500">
                                    {{ (int) $inspectionPhotoCount }} photo(s)
                                </div>
                            </div>

                            @if((int) $inspectionPhotoCount > 0)
                                <a href="{{ route('jobs.insurance.vault', $job->id) }}"
                                class="inline-flex items-center px-3 py-1.5 text-xs rounded border">
                                    View
                                </a>
                            @else
                                <span class="text-xs text-gray-400">None</span>
                            @endif
                        </div>
                    </div>

                    {{-- Completion Photos (optional) --}}
                    <div class="border rounded-md p-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-900">Completion Photos (After Repair)</div>
                                <div class="text-xs text-gray-500">
                                    {{ (int) $completionPhotoCount }} photo(s)
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                {{-- View (always allowed) --}}
                                <a href="{{ route('jobs.insurance.vault', $job->id) }}"
                                class="inline-flex items-center px-3 py-1.5 text-xs rounded border">
                                    View
                                </a>

                                {{-- Attach (LOCK after submit) --}}
                                @if(!$isSubmitted)
                                    <a href="{{ action([\App\Http\Controllers\JobController::class, 'insuranceVaultPicker'], $job->id) }}?mode=completion&return={{ urlencode(route('jobs.insurance.claim.show', $job->id)) }}"                                    class="inline-flex items-center px-3 py-1.5 text-xs rounded border bg-blue-600 text-white border-blue-600">
                                        Attach Photos
                                    </a>
                                @else
                                    <button type="button" disabled
                                        class="inline-flex items-center px-3 py-1.5 text-xs rounded border bg-gray-200 text-gray-500 cursor-not-allowed">
                                        <x-lucide-lock class="w-3.5 h-3.5 mr-1" />
                                        Attach Locked
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>

                @if($hasPack)
                    <div class="mt-4 flex flex-wrap items-center justify-end gap-2">
                        <a href="{{ route('jobs.insurance.claim.pack', $job->id) }}"
                        class="inline-flex items-center px-4 py-2 text-sm rounded bg-blue-600 text-white">
                            Download Claim Pack (ZIP)
                        </a>

                        @if(!empty($claimShareUrl))
                            <a href="{{ $claimShareUrl }}"
                            class="inline-flex items-center px-4 py-2 text-sm rounded border">
                                Share Link
                            </a>
                        @endif

                        {{-- ✅ Submit appears AFTER generate --}}
                        @if(!$isSubmitted)
                            @if($canSubmit)
                                <form method="POST" action="{{ route('jobs.insurance.claim.submit', $job) }}">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center px-4 py-2 text-sm rounded bg-gray-900 text-white">
                                        Submit Claim
                                    </button>
                                </form>
                            @else
                                <button type="button" disabled
                                        class="inline-flex items-center px-4 py-2 text-sm rounded bg-gray-200 text-gray-500 cursor-not-allowed">
                                    Submit Claim (Coming soon)
                                </button>
                            @endif
                        @endif
                    </div>

                    @if(!$isSubmitted)
                        <div class="mt-2 text-xs text-gray-500 text-right">
                            Download/Share is ready. Submit Claim to finalize insurer submission.
                        </div>
                    @else
                        <div class="mt-2 text-xs text-green-700 text-right">
                            Claim submitted. Download/Share remains available.
                        </div>
                    @endif
                @else
                    <div class="mt-4 text-xs text-gray-500">
                        Generate the Claim Pack to enable Download, Share, and Submit.
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>