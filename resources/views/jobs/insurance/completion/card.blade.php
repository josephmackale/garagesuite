{{-- Completion Card (Drop-in - redesigned, no duplicate View Invoice) --}}
@php
  $gates    = $gates ?? [];
  $invoice  = $invoice ?? null;

  $hasInvoice  = (bool) data_get($gates, 'has_invoice', false);
  $invoicePaid = (bool) data_get($gates, 'invoice_paid', false);

  $invoiceNo = $invoice->invoice_number ?? ($invoice->id ?? null);
@endphp

<div class="rounded-xl border border-gray-200 bg-white shadow-sm">
  <div class="px-4 py-3 border-b border-gray-100 flex items-start justify-between gap-3">
    <div class="min-w-0">
      <div class="text-sm font-semibold text-gray-900">Completion</div>

      @if(empty($job->completed_at))
        <div class="text-xs text-gray-500">Finish the repair work to unlock invoicing.</div>
      @else
        @if($hasInvoice)
          <div class="text-xs text-gray-500">Repair completed. Payments are tracked under Settlement.</div>
        @else
          <div class="text-xs text-gray-500">Repair completed. Generate the insurance invoice to begin Settlement.</div>
        @endif
      @endif
    </div>

    @if(!empty($job->completed_at))
      <span class="shrink-0 inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset bg-emerald-50 text-emerald-700 ring-emerald-600/20">
        Completed
      </span>
    @else
      <span class="shrink-0 inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset bg-amber-50 text-amber-700 ring-amber-600/20">
        Pending
      </span>
    @endif
  </div>

  <div class="p-4 space-y-4">
    {{-- STEP 1: Mark completion --}}
    @if(empty($job->completed_at))
      <form method="POST"
            action="{{ route('jobs.insurance.completion.complete', $job) }}"
            onsubmit="event.preventDefault();
              fetch(this.action, {
                method:'POST',
                headers:{
                  'X-CSRF-TOKEN':'{{ csrf_token() }}',
                  'Accept':'application/json'
                }
              })
              .then(r => r.ok ? r.json() : r.json().then(e => Promise.reject(e)))
              .then(() => window.location.reload())
              .catch(e => alert(e.message || 'Failed to mark completion.'));">

        @csrf
        <button type="submit"
                class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-black">
          Mark Completion Done
        </button>
      </form>

    @else
      <div class="text-sm text-gray-700">
        Completed at:
        <span class="font-medium">{{ $job->completed_at }}</span>
      </div>

      {{-- STEP 2: Invoicing (only show Generate when invoice does NOT exist) --}}
      @if(! $hasInvoice || empty($invoice))
        <div class="flex flex-wrap items-center gap-2">
          <button type="button"
                  class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700"
                  onclick="generateInsuranceInvoice({{ $job->id }})">
            Generate Invoice
          </button>

          <span class="text-xs text-gray-500">
            Invoice lines are generated from the latest <span class="font-medium">approved approval pack</span>.
          </span>
        </div>
      @else
        {{-- Invoice exists: show compact info only (no big "View Invoice" button) --}}
        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3 flex items-center justify-between gap-3">
          <div class="min-w-0">
            <div class="text-xs text-gray-500">Invoice created</div>
            <div class="text-sm font-semibold text-gray-900 truncate">
              #{{ $invoiceNo }}
            </div>
            <div class="text-xs text-gray-500 mt-1">
              @if($invoicePaid)
                <span class="font-medium text-emerald-700">Paid — Receipt available in Settlement</span>
              @else
                <span class="font-medium text-gray-700">Unpaid — proceed to Settlement</span>
              @endif
            </div>
          </div>

          {{-- Optional tiny link (not a primary action) --}}
          <span class="shrink-0 text-xs text-gray-400">
            Manage in Settlement ↓
          </span>
        </div>
      @endif
    @endif
  </div>
</div>

<script>
async function generateInsuranceInvoice(jobId) {
  try {
    const res = await fetch(`/jobs/${jobId}/insurance/invoice/generate`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      }
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      alert(data.message || 'Failed to generate invoice.');
      return;
    }

    if (data.redirect_url) {
      window.location.href = data.redirect_url;
    } else {
      window.location.reload();
    }
  } catch (e) {
    alert('Failed to generate invoice.');
  }
}
</script>