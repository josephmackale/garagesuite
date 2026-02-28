{{-- jobs/insurance/repair/card.blade.php --}}

{{-- Repair Card (Includes Start, Items, and Complete) --}}
@php
    $repairSession = $repairSession ?? null;
    $repairItems   = collect($repairItems ?? []);
    $pack          = $pack ?? null;

    // Approval gate (use $pack passed from controller, not $job->approvalPack)
    $isApproved = ($pack && ($pack->status ?? null) === 'approved');

    // Determine completion
    $terminalStatuses = ['done', 'skipped', 'not_done'];

    $allDone = $repairItems->isNotEmpty()
        && $repairItems->every(function ($it) use ($terminalStatuses) {
            return in_array(($it->execution_status ?? 'pending'), $terminalStatuses, true);
        });

    $sessionStatus = $repairSession->status ?? null;
    $isCompletedSession  = ($sessionStatus === 'completed');
    $isInProgressSession = in_array($sessionStatus, ['in_progress', 'active'], true);
@endphp

<div class="bg-white rounded-lg border border-gray-100 p-5">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h3 class="text-sm font-semibold text-gray-900">Repair</h3>
            <p class="mt-1 text-xs text-gray-500">
                Execute approved scope items and mark each one as done.
            </p>
        </div>

        @if($isInProgressSession)
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100">
                In Progress
            </span>
        @elseif($isCompletedSession)
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                Completed 🔐
            </span>
        @else
            @if(!$isApproved)
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-amber-50 text-amber-800 ring-1 ring-amber-100">
                    Locked (Awaiting Approval)
                </span>
            @else
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-gray-50 text-gray-600 ring-1 ring-gray-100">
                    Not Started
                </span>
            @endif
        @endif
    </div>

    {{-- Locked state: no approved pack --}}
    @if(!$isApproved)
        <div class="mt-4 rounded-lg border border-dashed border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Repair is locked until the approval pack is approved.
        </div>
        @php return; @endphp
    @endif

    {{-- Not started: show Start Repair if no session --}}
    @if(!$repairSession)
        <div class="mt-4 rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-600">
            <p>Approval is complete. Start the repair session to load the approved scope items.</p>
            <form action="{{ route('jobs.insurance.repair.start', $job->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary mt-3">Start Repair</button>
            </form>
        </div>
        @php return; @endphp
    @endif

    {{-- Session exists --}}
    @if($repairItems->isEmpty())
        <div class="mt-4 rounded-lg border border-dashed border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            No repair items found for this session. This usually means the approved pack items were not cloned.
        </div>
    @else
        <div class="overflow-x-auto mt-4">
            <table class="min-w-full text-sm">
                <thead class="text-xs text-gray-500">
                    <tr class="border-b border-gray-200">
                        <th class="py-3 text-left font-medium w-[140px]">Status</th>
                        <th class="py-3 text-left font-medium">Item</th>
                        <th class="py-3 text-left font-medium w-[140px]">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @foreach($repairItems as $item)
                        @php
                            $status = $item->execution_status ?? 'pending';

                            // Phase 5: terminal statuses that mean the work item is finalized
                            $terminalStatuses = ['done', 'skipped', 'not_done'];

                            $isTerminal = in_array($status, $terminalStatuses, true);
                            $isDone = ($status === 'done'); // ✅ FIX: define $isDone (was undefined)

                            // Badge colors by status
                            $badgeClass = match ($status) {
                                'done'     => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100',
                                'skipped'  => 'bg-amber-50 text-amber-800 ring-1 ring-amber-100',
                                'not_done' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-100',
                                'in_progress' => 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100',
                                default    => 'bg-gray-50 text-gray-700 ring-1 ring-gray-100', // pending
                            };

                            // Human label
                            $label = match ($status) {
                                'done'        => 'Done',
                                'skipped'     => 'Skipped',
                                'not_done'    => 'Not Done',
                                'in_progress' => 'In Progress',
                                default       => 'Pending',
                            };
                        @endphp

                        <tr class="hover:bg-gray-50/60">
                            <td class="py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeClass }}">
                                    {{ $label }}
                                </span>
                            </td>

                            <td class="py-3">
                                <div class="font-semibold text-gray-900">
                                    {{ $item->name }}
                                </div>
                            </td>

                            <td class="py-3">
                                @if($isCompletedSession)
                                    <span class="text-xs text-gray-400">Locked</span>
                                @else
                                    {{-- ✅ Phase 5: only allow action if NOT terminal --}}
                                    @if(!$isTerminal)
                                        <form action="{{ route('jobs.insurance.repair.updateStatus', ['job' => $job->id, 'item' => $item->id]) }}"
                                            method="POST"
                                            onsubmit="event.preventDefault();
                                                const f=this;
                                                const btn=f.querySelector('button[type=submit]');
                                                btn.disabled=true;

                                                const fd = new FormData();
                                                fd.append('_token', '{{ csrf_token() }}');
                                                fd.append('status', 'done'); // ✅ explicitly send status

                                                fetch(f.action, {
                                                method:'POST',
                                                headers:{
                                                    'X-Requested-With':'XMLHttpRequest',
                                                    'Accept':'application/json'
                                                },
                                                body: fd
                                                })
                                                .then(async r => ({ ok:r.ok, j: await r.json().catch(()=>({})) }))
                                                .then(({ ok, j }) => {
                                                if (!ok || j.ok === false) throw new Error(j.message || 'Failed');

                                                if (window.rehydrateInsuranceCard) {
                                                    window.rehydrateInsuranceCard(`/jobs/{{ $job->id }}/insurance/repair-card`, 'insurance-repair-card');
                                                } else {
                                                    location.reload();
                                                }
                                                })
                                                .catch(e => alert(e.message || 'Failed'))
                                                .finally(()=>{ btn.disabled=false; });
                                            ">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700">
                                            Mark Done
                                        </button>
                                        </form>
                                    @else
                                        @if($isDone)
                                            <span class="text-xs font-semibold text-emerald-700">✓ Completed</span>
                                        @else
                                            <span class="text-xs text-gray-500">Finalized</span>
                                        @endif
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Complete Repair: only when session is in progress and all items terminal --}}
        @if($isInProgressSession && $allDone && !$isCompletedSession)
            <form action="{{ route('jobs.insurance.repair.complete', $job->id) }}" method="POST" class="mt-4">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                    Complete Repair
                </button>
            </form>
        @elseif($isInProgressSession && !$allDone)
            <div class="mt-4 text-xs text-gray-500">
                Complete Repair will unlock once all items are marked done (or otherwise finalized).
            </div>
        @endif

    @endif
</div>
