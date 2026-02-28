{{-- resources/views/jobs/insurance/quotation/card.blade.php --}}

@php
    /**
     * Quotation Card bootstrap (SAFE).
     * Goal: prevent undefined variables and ensure $job + $quote are always available.
     */

    // ✅ Normalize: some callers pass $quotation, some pass $quote, some pass nothing
    $quote = $quote ?? ($quotation ?? null);
    $quotation = $quotation ?? $quote;

    // ✅ Ensure we have the real Job model (route > fallback ids)
    if (empty($job) || !data_get($job, 'id')) {
        $routeJob = request()->route('job');

        if ($routeJob instanceof \App\Models\Job) {
            $job = $routeJob;
        } else {
            $jid = (int) (
                $routeJob
                ?: data_get($inspection ?? null, 'job_id')
                ?: data_get($ctx ?? null, 'job_id')
                ?: 0
            );

            $job = $jid ? \App\Models\Job::query()->whereKey($jid)->first() : null;
        }
    }

    // ✅ If caller forgot gates OR passed null, compute them here (prevents random locked)
    if ((empty($gates) || !is_array($gates)) && $job && data_get($job, 'id')) {
        $gates = app(\App\Services\InsuranceGate::class)->forJob($job);
    }

    // ✅ Safe defaults (prevents "Undefined variable" crashes)
    $quoteStatus = (string) data_get($quote, 'status', 'draft');
    $quoteVersion = (int) data_get($quote, 'version', 1);
    $canEditQuote = null;
    $isEditable = false;
    $isReadOnlyByStatus = false;

    // ✅ Canonical editability (Phase 4/5): trust gates ONLY
    $canEdit    = (bool) data_get($gates ?? [], 'can_edit_quote.ok', false);
    $editReason = (string) data_get($gates ?? [], 'can_edit_quote.reason', '');

    $isEditable          = $canEdit;
    $isLockedByInspection = ! $canEdit;

    // Read-only banner if denied due to submitted/approved/etc
    $isReadOnlyByStatus = ! $canEdit && in_array($quoteStatus, ['submitted', 'approved'], true);

    $statusLabel = match ($quoteStatus) {
        'approved'  => 'Approved',
        'submitted' => 'Submitted',
        'rejected'  => 'Rejected',
        default     => 'Draft',
    };

    $statusClass = match ($quoteStatus) {
        'approved'  => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        'submitted' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
        'rejected'  => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
        default     => 'bg-slate-50 text-slate-700 ring-1 ring-slate-200',
    };

    $gateLabel = $isLockedByInspection ? 'Locked' : 'Unlocked';
    $gateClass = $isLockedByInspection
        ? 'bg-amber-50 text-amber-800 ring-1 ring-amber-200'
        : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';

    // ✅ Suggestions count (Damaged + Missing) - safe even if $inspection missing
    $damagedCount = (int) data_get($inspection ?? null, 'counts.damaged', data_get($inspection ?? null, 'damaged_count', 0));
    $missingCount = (int) data_get($inspection ?? null, 'counts.missing', data_get($inspection ?? null, 'missing_count', 0));
    $suggestionsCount = max(0, $damagedCount + $missingCount);
    $canImportSuggestions = $isEditable && $suggestionsCount > 0;

    // ✅ Save URL (route exists now)
    $saveUrl = \Illuminate\Support\Facades\Route::has('jobs.create.step3.quotation.save')
        ? route('jobs.create.step3.quotation.save')
        : '';

    // ✅ Normalize DB quote lines into the JS structure the UI expects
    $initialLines = collect($quoteLines ?? $lines ?? [])->map(function ($l) {
        return [
            'type'        => data_get($l, 'line_type', data_get($l, 'type', 'labour')),
            'description' => (string) data_get($l, 'description', ''),
            'qty'         => (float) data_get($l, 'qty', data_get($l, 'approved_qty', 1)),
            'amount'      => (float) data_get($l, 'amount', data_get($l, 'total', 0)),
            // optional if your JS uses it:
            'unit_price'  => (float) data_get($l, 'unit_price', 0),
        ];
    })->values()->toArray();

@endphp




<section class="mt-4">
  <div class="rounded-xl border bg-white shadow-sm overflow-hidden"
       id="insurance-quotation"
       x-data="insuranceQuotationCard({
            editable: @js($isEditable),
            lockedByInspection: @js($isLockedByInspection),
            status: @js($quoteStatus),
            version: @js($quoteVersion ?? 1),
            jobId: @js((int) (
                data_get($job, 'id')
                ?: data_get($inspection, 'job_id')
                ?: data_get($ctx, 'job_id')
                ?: 0
            )),
            initialLines: @js($initialLines ?? []),
            tax: @js($tax ?? 0),
            vatRate: @js(0.16),
            discount: @js($discount ?? 0),
            csrf: @js(csrf_token()),
            saveUrl: @js($saveUrl),
       })">

    {{-- HEADER (FULL DROP-IN) --}}
    <div class="px-4 sm:px-6 py-4 border-b">
      <div class="flex items-start justify-between gap-4">
        {{-- Left --}}
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <h3 class="text-base font-semibold text-slate-900">Quotation</h3>

            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">
              {{ $statusLabel }}
            </span>
          </div>

          <div class="mt-1 text-sm text-slate-600">
            @if($isEditable)
              <span class="font-medium text-slate-800">Completed</span>
              <span class="text-emerald-600">✅</span>
              @if(data_get($inspection, 'completed_at') || data_get($inspection, 'updated_at'))
                <span class="text-slate-500">
                  ({{ \Illuminate\Support\Carbon::parse(data_get($inspection,'completed_at') ?? data_get($inspection,'updated_at'))->format('d M Y, H:i') }})
                </span>
              @endif
            @else
              <span class="text-amber-700">Inspection not complete — quotation is locked.</span>
            @endif
          </div>
        </div>
      </div>
    </div>

    {{-- Toast (shows Draft saved / errors) --}}
    <div class="px-4 sm:px-6 pt-3" x-cloak x-show="toast.show">
      <div
        class="rounded-lg px-3 py-2 text-sm font-medium"
        :class="toast.type === 'error'
          ? 'bg-rose-50 text-rose-800 ring-1 ring-rose-200'
          : 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200'"
      >
        <span x-text="toast.message"></span>
      </div>
    </div>


    {{-- Body --}}
    <div class="p-4 sm:p-6">

      {{-- Locked banner --}}
      <template x-if="lockedByInspection">
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
          <div class="font-medium">Quotation is locked</div>
          <div class="text-amber-800">Complete and lock the inspection to unlock quotation editing.</div>
        </div>
      </template>

      {{-- Submitted/Approved banner --}}
      @if($isReadOnlyByStatus)
        <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
          <div class="font-medium">Quotation is {{ strtolower($statusLabel) }}</div>
          <div class="text-blue-800">
            Editing is disabled to preserve the audit trail.
            @if(data_get($quote,'submitted_at'))
              Submitted: {{ \Illuminate\Support\Carbon::parse(data_get($quote,'submitted_at'))->format('d M Y, H:i') }}
            @endif
          </div>
        </div>
      @endif

    {{-- Lines --}}
    <div class="rounded-lg border overflow-hidden">

      {{-- Header --}}
      <div class="px-4 py-3 border-b flex items-center justify-between bg-white">
        <div>
          <div class="text-sm font-semibold text-slate-900">Quotation Lines</div>
          <div class="text-xs text-slate-500">Build the estimate (labour, parts, materials, sublet).</div>
        </div>

        <div class="flex items-center gap-2">
          <button type="button"
                  class="inline-flex items-center rounded-lg border px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  :disabled="!editable"
                  @click="addBlankLine()">
            + Add Line
          </button>

          <button type="button"
                  class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed"
                  :disabled="!editable"
                  @click="focusQuickDesc()">
            Quick Add
          </button>
        </div>
      </div>

      {{-- Quick Add Row --}}
      <div class="px-4 py-3 border-b bg-slate-50">
        <div class="grid grid-cols-12 gap-2 items-end">
          <div class="col-span-12 sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Type</label>
            <select class="w-full rounded-lg border px-3 py-2 text-sm"
                    :disabled="!editable"
                    x-model="quick.type">
              <option value="labour">Labour</option>
              <option value="parts">Parts</option>
              <option value="materials">Materials</option>
              <option value="sublet">Sublet</option>
            </select>
          </div>

          <div class="col-span-12 sm:col-span-6">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Description</label>
            <input id="quoteQuickDesc"
                  type="text"
                  class="w-full rounded-lg border px-3 py-2 text-sm"
                  :disabled="!editable"
                  placeholder="e.g. Panel beating — front bumper / Replace headlamp / Paint materials"
                  x-model="quick.description"
                  @keydown.enter.prevent="addQuickLine()">
          </div>

          <div class="col-span-6 sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Qty</label>
            <input type="text" inputmode="decimal"
                  min="1"
                  step="1"
                  class="w-full rounded-lg border px-3 py-2 text-sm text-right"
                  :disabled="!editable"
                  x-model.number="quick.qty">
          </div>

          <div class="col-span-6 sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-600 mb-1">Amount</label>
            <input type="text" inputmode="decimal"
                  min="0"
                  step="0.01"
                  class="w-full rounded-lg border px-3 py-2 text-sm text-right"
                  :disabled="!editable"
                  x-model.number="quick.amount">
          </div>

          <div class="col-span-12 flex items-center justify-end">
            <button type="button"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!editable || !(quick.description && quick.description.trim().length)"
                    @click="addQuickLine()">
              Add
            </button>
          </div>
        </div>
      </div>

      {{-- Empty state --}}
      <template x-if="lines.length === 0">
        <div class="px-4 py-10 text-center bg-white">
          <div class="text-sm font-medium text-slate-800">No quotation lines yet.</div>
          <div class="mt-1 text-sm text-slate-600">Use Quick Add above, or add common items below.</div>

          <div class="mt-4 flex flex-wrap gap-2 justify-center">
            <button type="button"
                    class="rounded-lg border px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!editable"
                    @click="addPresetLine({ type:'labour', description:'Panel beating', qty:1, unit_price:0 })">
              + Panel beating
            </button>
            <button type="button"
                    class="rounded-lg border px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!editable"
                    @click="addPresetLine({ type:'materials', description:'Paint materials', qty:1, unit_price:0 })">
              + Paint materials
            </button>
            <button type="button"
                    class="rounded-lg border px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!editable"
                    @click="addPresetLine({ type:'parts', description:'Replace part', qty:1, unit_price:0 })">
              + Replace part
            </button>
          </div>
        </div>
      </template>

      {{-- Table --}}
      <template x-if="lines.length > 0">
        <div class="overflow-x-auto bg-white">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-700">
              <tr>
                <th class="px-4 py-2 text-left font-semibold">Type</th>
                <th class="px-4 py-2 text-left font-semibold">Description</th>
                <th class="px-4 py-2 text-right font-semibold w-[90px]">Qty</th>
                <th class="px-4 py-2 text-right font-semibold w-[180px]">Amount</th>
                <th class="px-4 py-2 text-right font-semibold w-[160px]">Action</th>
              </tr>
            </thead>

            <tbody class="divide-y">
              <template x-for="(line, i) in lines" :key="i">
                <tr class="text-slate-800 align-top">

                  <td class="px-4 py-2 whitespace-nowrap">
                    {{-- Read mode --}}
                    <div x-show="editingIndex !== i">
                      <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                            :class="badgeClass(line.type)">
                        <span x-text="({labour:'Labour', parts:'Parts', materials:'Materials', sublet:'Sublet'}[line.type] || line.type || '—')"></span>
                      </span>
                    </div>

                    {{-- Edit mode --}}
                    <div x-show="editingIndex === i" x-cloak>
                      <select class="w-full min-w-[110px] rounded-lg border px-2 py-2 text-sm"
                              :disabled="!editable"
                              :value="line.type"
                              @change="patchLine(i,'type',$event.target.value)">
                        <option value="labour">Labour</option>
                        <option value="parts">Parts</option>
                        <option value="materials">Materials</option>
                        <option value="sublet">Sublet</option>
                      </select>
                    </div>
                  </td>


                  <td class="px-4 py-2 min-w-[280px]">
                    <div x-show="editingIndex !== i" class="whitespace-pre-wrap" x-text="line.description || '—'"></div>
                    <div x-show="editingIndex === i" x-cloak>
                      <input type="text"
                            class="w-full rounded-lg border px-3 py-2 text-sm"
                            :disabled="!editable"
                            :value="line.description"
                            @input="patchLine(i,'description',$event.target.value)">
                    </div>
                  </td>

                  <td class="px-4 py-2 text-right">
                    <div x-show="editingIndex !== i" x-text="Number(line.qty || 0)"></div>
                    <div x-show="editingIndex === i" x-cloak>
                      <input type="text" inputmode="decimal"
                            min="0"
                            step="0.01"
                            class="w-full rounded-lg border px-3 py-2 text-sm text-right"
                            :disabled="!editable"
                            :value="Number(line.qty || 0)"
                            @input="patchLine(i,'qty',Number($event.target.value || 0))">
                    </div>
                  </td>



                  <td class="px-4 py-2 text-right">
                    <div x-show="editingIndex !== i" x-text="fmt(line.amount)"></div>
                    <div x-show="editingIndex === i" x-cloak>
                      <input type="text" inputmode="decimal"
                            min="0"
                            step="0.01"
                            class="w-full rounded-lg border px-3 py-2 text-sm text-right"
                            :disabled="!editable"
                            :value="Number(line.amount || 0)"
                            @input="patchLine(i,'amount',Number($event.target.value || 0))">
                    </div>
                  </td>

                  <td class="px-4 py-2 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <button type="button"
                              class="inline-flex items-center rounded-lg border px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                              :disabled="!editable"
                              x-show="editingIndex !== i"
                              @click="startEdit(i)">
                        Edit
                      </button>

                      <button type="button"
                              class="inline-flex items-center rounded-lg border px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                              :disabled="!editable"
                              x-show="editingIndex === i"
                              x-cloak
                              @click="stopEdit()">
                        Done
                      </button>

                      <button type="button"
                              class="inline-flex items-center rounded-lg border border-rose-200 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-50 disabled:cursor-not-allowed"
                              :disabled="!editable"
                              @click="deleteLine(i)">
                        Delete
                      </button>
                    </div>
                  </td>

                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </template>
    </div>

      {{-- Summary --}}
      <div class="mt-4 flex flex-col sm:flex-row sm:items-start sm:justify-end gap-3">
        <div class="w-full sm:w-[360px] rounded-lg border bg-slate-50 p-4">
          <div class="text-sm font-semibold text-slate-900 mb-2">Summary</div>

          <div class="space-y-1 text-sm">
            <div class="flex items-center justify-between">
              <span class="text-slate-600">Subtotal</span>
              <span class="font-semibold text-slate-900" x-text="fmt(subtotal)"></span>
            </div>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-slate-600">VAT (16%)</span>

                <button type="button"
                        class="relative inline-flex h-5 w-9 items-center rounded-full transition"
                        :class="vatEnabled ? 'bg-emerald-500' : 'bg-slate-300'"
                        :disabled="!editable"
                        @click="toggleVat()">
                  <span class="inline-block h-4 w-4 transform rounded-full bg-white transition"
                        :class="vatEnabled ? 'translate-x-4' : 'translate-x-1'"></span>
                </button>

                <span class="text-xs font-semibold"
                      :class="vatEnabled ? 'text-emerald-700' : 'text-slate-500'"
                      x-text="vatEnabled ? 'ON' : 'OFF'"></span>
              </div>

              <span class="font-semibold text-slate-900" x-text="fmt(vatAmount)"></span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-600">Discount</span>
              <span class="font-semibold text-slate-900" x-text="'-' + fmt(discount)"></span>
            </div>

            <div class="pt-2 mt-2 border-t flex items-center justify-between">
              <span class="text-slate-700 font-semibold">Grand Total</span>
              <span class="text-slate-900 font-bold" x-text="fmt(total)"></span>
            </div>
          </div>
        </div>
      </div>

      {{-- Actions --}}
      <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t pt-4">
        <div class="text-sm text-slate-600">
          @if($isLockedByInspection)
            Complete inspection to unlock quotation.
          @elseif($isReadOnlyByStatus)
            Quotation is locked after submission/approval.
          @else
            Save draft anytime. Submit only when ready to send to insurer.
          @endif
        </div>

        <div class="flex items-center gap-2 justify-end">
          <button type="button"
                  class="inline-flex items-center rounded-lg border px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  :disabled="!editable"
                  @click="saveDraft()">
            Save Draft
          </button>

          <button type="button"
                  class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                  :disabled="!editable || lines.length < 1"
                  title="Requires at least 1 quotation line"
                  @click="$dispatch('quotation:submit')">
            Submit for Approval
          </button>
        </div>
      </div>
    </div>

  </div>
</section>
