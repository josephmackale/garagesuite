<div class="max-w-3xl mx-auto p-6">
    @include('jobs.create._progress', ['current' => 'step4'])

    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-slate-200 p-8">

        {{-- ============================================================
            MODE A: SUCCESS (job exists)
        ============================================================ --}}
        @if(isset($job) && $job)
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100 text-green-700">
                    ✓
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        Job saved successfully
                    </h2>

                    <p class="text-sm text-slate-600">
                        What would you like to do next?
                    </p>
                </div>
            </div>

            {{-- Job Info --}}
            <div class="mt-6 mb-6 rounded-xl border border-slate-100 bg-slate-50 p-4">
                <div class="text-sm text-slate-600">Job Number</div>

                <div class="mt-1 text-base font-semibold text-slate-900">
                    {{ $job->job_number }}
                </div>
            </div>

            {{-- Actions --}}
            <div class="mt-8 flex flex-wrap items-center gap-3">
                <a href="{{ route('jobs.show', $job) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl
                          bg-indigo-600 text-white font-semibold
                          hover:bg-indigo-700 transition">
                    View Job
                </a>

                <a href="{{ route('jobs.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl
                          border border-slate-200 text-slate-700
                          hover:bg-slate-50 transition">
                    Go to Jobs
                </a>

                @if(!empty($modal))
                    <button type="button"
                            onclick="window.closeCreateJobModal ? closeCreateJobModal() : null"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl
                                   border border-slate-200 text-slate-700
                                   hover:bg-slate-50 transition">
                        Close
                    </button>
                @endif
            </div>

        {{-- ============================================================
            MODE B: REVIEW + CONFIRM (job not created yet)
        ============================================================ --}}
        @else
            @php
                // Provided by controller patch: $draft + $details
                $payerType = $draft['payer_type'] ?? null;
                $payer     = $draft['payer'] ?? [];
                $details   = $details ?? ($draft['details'] ?? []);

                $jobDate     = $details['job_date'] ?? null;
                $mileage     = $details['mileage'] ?? null;
                $serviceType = $details['service_type'] ?? null;

                // Payer display helpers
                $companyOrgId = $payer['organization_id'] ?? null;

                $insurance    = $payer['insurance'] ?? [];
                $insurerName  = $insurance['insurer_name'] ?? null;
                $policyNo     = $insurance['policy_number'] ?? null;
                $claimNo      = $insurance['claim_number'] ?? null;
            @endphp

            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        Review & Confirm
                    </h2>
                    <p class="text-sm text-slate-600 mt-1">
                        Confirm the details below, then create the job.
                    </p>
                </div>

                {{-- Back to step 3 --}}
                <a href="{{ route('jobs.create.step3', $ctx ?? []) }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl
                          border border-slate-200 text-slate-700
                          hover:bg-slate-50 transition">
                    Back
                </a>
            </div>

            {{-- Summary cards --}}
            <div class="mt-6 grid grid-cols-1 gap-4">

                {{-- Payer --}}
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Payer</div>

                    <div class="mt-2 text-sm text-slate-700">
                        <span class="font-semibold text-slate-900">
                            {{ strtoupper((string)$payerType) }}
                        </span>

                        @if($payerType === 'company')
                            <span class="ml-2 text-slate-600">
                                Org ID: {{ $companyOrgId ?: '—' }}
                            </span>
                        @elseif($payerType === 'insurance')
                            <div class="mt-2 space-y-1">
                                <div><span class="text-slate-500">Insurer:</span> <span class="font-semibold text-slate-900">{{ $insurerName ?: '—' }}</span></div>
                                <div><span class="text-slate-500">Policy:</span> <span class="text-slate-900">{{ $policyNo ?: '—' }}</span></div>
                                <div><span class="text-slate-500">Claim:</span> <span class="text-slate-900">{{ $claimNo ?: '—' }}</span></div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Job Basics --}}
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Job Basics</div>

                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                        <div>
                            <div class="text-slate-500">Job Date</div>
                            <div class="font-semibold text-slate-900">{{ $jobDate ?: '—' }}</div>
                        </div>
                        <div>
                            <div class="text-slate-500">Mileage</div>
                            <div class="font-semibold text-slate-900">{{ $mileage !== null && $mileage !== '' ? number_format((float)$mileage) : '—' }}</div>
                        </div>
                        <div>
                            <div class="text-slate-500">Service Type</div>
                            <div class="font-semibold text-slate-900">{{ $serviceType ?: '—' }}</div>
                        </div>
                    </div>
                </div>

                {{-- Optional warning if not ready --}}
                @if(empty($jobDate) || $mileage === null || $mileage === '' || empty($serviceType))
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        Some required job basics are missing. Please go back to Step 3 and complete Job Date, Mileage and Service Type.
                    </div>
                @endif
            </div>

            {{-- Confirm action --}}
            <div class="mt-8 flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('jobs.create.confirm', $ctx ?? []) }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl
                                   bg-slate-900 text-white font-semibold
                                   hover:bg-slate-800 transition
                                   disabled:opacity-50 disabled:cursor-not-allowed"
                            @disabled(empty($jobDate) || $mileage === null || $mileage === '' || empty($serviceType))>
                        Create Job
                    </button>
                </form>

                <a href="{{ route('jobs.create.step3', $ctx ?? []) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl
                          border border-slate-200 text-slate-700
                          hover:bg-slate-50 transition">
                    Edit Details
                </a>

                <a href="{{ route('jobs.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl
                          border border-slate-200 text-slate-700
                          hover:bg-slate-50 transition">
                    Go to Jobs
                </a>

                @if(!empty($modal))
                    <button type="button"
                            onclick="window.closeCreateJobModal ? closeCreateJobModal() : null"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl
                                   border border-slate-200 text-slate-700
                                   hover:bg-slate-50 transition">
                        Close
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>
