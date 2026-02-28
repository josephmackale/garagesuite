{{-- resources/views/jobs/insurance/approval/panel.blade.php (FULL DROP-IN) --}}
@php
    // ✅ Canonical truth: latest approval pack (controller passes $pack)
    $status = (string) data_get($pack ?? null, 'status', 'draft');

    // Back-compat: legacy "pending" == "submitted"
    if ($status === 'pending') $status = 'submitted';

    // ✅ NEVER read legacy $job->approval here (causes inconsistencies)
    // $approval = $job->approval ?? null;

    // ✅ LPO DB truth
    $lpo = \DB::table('job_insurance_details')->where('job_id', $job->id)->value('lpo_number');

    // ✅ Gate truth (passed from controller)
    $quoteSubmitted = (bool) data_get($gates ?? [], 'quote_submitted', false);

    // ✅ Pack display helpers (all from approval_packs)
    $submittedAt = data_get($pack ?? null, 'submitted_at');
    $decisionAt  = data_get($pack ?? null, 'decision_at');

    // decision/approval metadata (all canonical fields)
    $approvedById   = data_get($pack ?? null, 'approved_by');               // user id
    $approvedAt     = data_get($pack ?? null, 'approval_approved_at');      // timestamp
    $decisionNotes  = data_get($pack ?? null, 'decision_notes');            // text
@endphp

<div class="rounded-xl border border-gray-200 bg-white shadow-sm">
    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
        <div>
            <div class="text-sm font-semibold text-gray-900">Insurance Approval</div>
            <div class="text-xs text-gray-500">Quotation scope must be approved before repair.</div>
        </div>

        <div class="text-xs font-semibold">
            @if($status === 'approved')
                <span class="px-2 py-1 rounded-full bg-green-50 text-green-700 border border-green-200">Approved</span>
            @elseif($status === 'rejected')
                <span class="px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-200">Rejected</span>
            @elseif($status === 'submitted')
                <span class="px-2 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200">Submitted</span>
            @else
                <span class="px-2 py-1 rounded-full bg-gray-50 text-gray-700 border border-gray-200">Not submitted</span>
            @endif
        </div>
    </div>

    {{-- Share section (outside the header row, but still inside the card) --}}
    @if(!empty($packShareUrl))
        <div class="px-4 py-3 border-b border-gray-100 bg-gray-50" x-data="{ copied:false, t:null }">
            <div class="text-xs font-semibold text-gray-800">Share Approval Pack</div>
            <div class="text-[11px] text-gray-500 mb-2">Secure link for insurer (expires in 14 days).</div>

            <div class="flex items-center gap-2">
                <input type="text"
                       readonly
                       value="{{ $packShareUrl }}"
                       class="w-full h-9 text-xs rounded-lg border-gray-300 bg-white px-3">

                <button type="button"
                        class="w-24 h-9 inline-flex items-center justify-center rounded-lg bg-gray-900 text-white text-xs font-semibold"
                        @click="
                            navigator.clipboard.writeText(@js($packShareUrl));
                            copied = true;
                            clearTimeout(t);
                            t = setTimeout(() => copied = false, 1500);
                        ">
                    <span x-show="!copied">Copy</span>
                    <span x-show="copied">Copied ✓</span>
                </button>

                <a href="{{ $packShareUrl }}"
                   target="_blank"
                   class="w-20 h-9 inline-flex items-center justify-center rounded-lg bg-indigo-600 text-white text-xs font-semibold">
                    Open
                </a>
            </div>

            <div x-show="copied"
                 x-transition
                 class="mt-1 text-[11px] font-semibold text-emerald-700">
                Link copied to clipboard.
            </div>

            @if(!empty($pack))
                <div class="mt-2 text-[11px] text-gray-600">
                    Pack #{{ $pack->id }} · v{{ $pack->version ?? 1 }} · {{ strtoupper($pack->status ?? 'draft') }}
                </div>
            @endif
        </div>
    @endif

    <div class="p-4 space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="rounded-lg bg-gray-50 border border-gray-100 p-3">
                <div class="text-[11px] text-gray-500">Submitted</div>
                <div class="text-sm font-semibold text-gray-900">
                    {{ $submittedAt ? \Carbon\Carbon::parse($submittedAt)->format('d M Y, H:i') : '—' }}
                </div>
            </div>

            <div class="rounded-lg bg-gray-50 border border-gray-100 p-3">
                <div class="text-[11px] text-gray-500">Decision</div>
                <div class="text-sm font-semibold text-gray-900">
                    {{ $decisionAt ? \Carbon\Carbon::parse($decisionAt)->format('d M Y, H:i') : '—' }}
                </div>
            </div>

            <div class="rounded-lg bg-gray-50 border border-gray-100 p-3">
                <div class="text-[11px] text-gray-500">Status</div>
                <div class="text-sm font-semibold text-gray-900">
                    {{ ucfirst($status ?: 'draft') }}
                </div>
            </div>
        </div>

        {{-- LPO status (always visible) --}}
        <div class="text-xs text-gray-600">
            <span class="font-semibold">LPO:</span>
            @if(!empty($lpo))
                <span class="text-emerald-700 font-semibold">{{ $lpo }}</span>
            @else
                <span class="text-red-600 font-semibold">Missing (invoice locked)</span>
            @endif
        </div>

        {{-- Not eligible / Draft --}}
        @if(!$quoteSubmitted || $status === 'draft')
            <form method="POST"
                  action="{{ route('jobs.insurance.approval.submit', $job) }}"
                  class="flex flex-col md:flex-row gap-2 md:items-center">
                @csrf

                <button type="submit"
                        class="inline-flex items-center justify-center px-3 py-2 rounded-lg bg-gray-900 text-white text-xs font-semibold hover:bg-black"
                        @if(!$quoteSubmitted) disabled @endif>
                    Submit for Approval
                </button>

                <div class="text-xs text-gray-500">
                    Requires inspection complete + quotation submitted.
                </div>
            </form>

        {{-- Submitted: show approve/reject actions --}}
        @elseif($status === 'submitted')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                @include('jobs.insurance.approval.approve-form', [
                    'job' => $job,
                    'pack' => $pack, // ✅ canonical
                    'lpo' => $lpo,
                ])

                @include('jobs.insurance.approval.reject-form', [
                    'job' => $job,
                    'pack' => $pack, // ✅ canonical
                ])
            </div>

        {{-- Approved --}}
        @elseif($status === 'approved')
            <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                <div class="font-semibold">Approved scope locked.</div>

                <div class="text-xs mt-1">
                    By (user id): <span class="font-semibold">{{ $approvedById ?: '—' }}</span>
                    · Approved at: <span class="font-semibold">{{ $approvedAt ? \Carbon\Carbon::parse($approvedAt)->format('d M Y, H:i') : '—' }}</span>
                </div>

                @if(!empty($decisionNotes))
                    <div class="text-xs mt-2">
                        Notes: <span class="font-semibold">{{ $decisionNotes }}</span>
                    </div>
                @endif

                <div class="text-xs mt-2">
                    LPO: <span class="font-semibold">{{ $lpo ?: '— (missing - invoice locked)' }}</span>
                </div>
            </div>

        {{-- Rejected --}}
        @elseif($status === 'rejected')
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                <div class="font-semibold">Rejected.</div>
                <div class="text-xs mt-1">{{ $decisionNotes ?: '—' }}</div>
            </div>
        @endif
    </div>
</div>