{{-- resources/views/jobs/insurance/settlement/card.blade.php --}}

@php
  $gates = $gates ?? [];

  $s = data_get($gates, 'settlement', []);
  $state = (string) data_get($s, 'state', 'awaiting_settlement'); // awaiting_settlement|partially_settled|settled

  $badgeText = match ($state) {
    'settled' => 'Financially Closed',
    'partially_settled' => 'Partially Paid',
    default => 'Awaiting Payment',
  };

  $badgeClass = match ($state) {
    'settled' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    'partially_settled' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
    default => 'bg-gray-50 text-gray-700 ring-gray-600/20',
  };

  $total   = (float) (data_get($s, 'total') ?? 0);
  $paid    = (float) (data_get($s, 'paid') ?? 0);
  $balance = (float) (data_get($s, 'balance') ?? max(0, $total - $paid));
  $balance = max(0, $balance);

  $pct = ($total > 0) ? min(100, max(0, round(($paid / $total) * 100))) : 0;

  $hasInvoice  = (bool) data_get($gates, 'has_invoice', false);
  $invoicePaid = (bool) data_get($gates, 'invoice_paid', false);

  $paidTone = $paid > 0 ? 'bg-emerald-50 border-emerald-100' : 'bg-gray-50 border-gray-100';
  $balTone  = $balance <= 0 ? 'bg-emerald-50 border-emerald-100' : ($paid > 0 ? 'bg-amber-50 border-amber-100' : 'bg-rose-50 border-rose-100');

  // passed from insuranceShow
  $invoice  = $invoice ?? null;
  $payments = $payments ?? collect();

  $invoiceNo = $invoice->invoice_number ?? ($invoice->id ?? null);
  $paidAt    = $invoice?->paid_at ? $invoice->paid_at->format('Y-m-d H:i') : null;
  $payStatus = (string) ($invoice->payment_status ?? 'unpaid');

  // progress bar color
  $barClass = match ($state) {
    'settled' => 'bg-emerald-600',
    'partially_settled' => 'bg-amber-500',
    default => 'bg-indigo-600',
  };
@endphp

<div class="rounded-xl border border-gray-200 bg-white shadow-sm">
  <div class="px-4 py-3 border-b border-gray-100 flex items-start justify-between gap-3">
    <div class="min-w-0">
      <div class="text-sm font-semibold text-gray-900">Settlement</div>
      <div class="text-xs text-gray-500">Invoice + Payments (financial status for this insurance case)</div>
    </div>

    <span class="shrink-0 inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $badgeClass }}">
      {{ $badgeText }}
    </span>
  </div>

  <div class="p-4 space-y-4">

    @if(! $hasInvoice)
      <div class="rounded-lg border border-dashed border-gray-300 p-4 text-sm text-gray-600">
        Settlement starts once an invoice is generated for this job.
      </div>
    @else

      @if(empty($invoice))
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
          <div class="font-semibold">Settlement wiring issue</div>
          <div class="mt-1">
            InsuranceGate says an invoice exists, but this card did not receive <code>$invoice</code>.
            Pass <code>'invoice' =&gt; $invoice</code> and <code>'payments' =&gt; $payments</code>
            from <code>jobs/insurance/show.blade.php</code>.
          </div>
        </div>
      @else

        {{-- Invoice summary card --}}
        <div class="rounded-lg border border-gray-100 p-4">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="text-xs text-gray-500">Invoice</div>
              <div class="text-sm font-semibold text-gray-900 truncate">#{{ $invoiceNo }}</div>
              <div class="text-xs text-gray-500 mt-1">
                Status: <span class="font-medium text-gray-700">{{ $payStatus }}</span>
                @if($paidAt) • Paid at: <span class="font-medium text-gray-700">{{ $paidAt }}</span>@endif
              </div>
            </div>

            <div class="flex flex-wrap gap-2">
              {{-- make this secondary (settlement main action is Record Payment) --}}
              <a href="{{ route('invoices.show', $invoice->id) }}"
                 class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-200 text-sm hover:bg-gray-50">
                Open Invoice
              </a>

              @if($invoicePaid)
                <a href="{{ route('invoices.receipt-pdf', $invoice->id) }}"
                   class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-200 text-sm hover:bg-gray-50">
                  Download Receipt
                </a>
              @else
                <div class="inline-flex items-center px-3 py-2 rounded-lg border border-dashed border-gray-300 text-sm text-gray-400 cursor-not-allowed">
                  Receipt available after payment
                </div>
              @endif

              <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-dashed border-gray-300 text-sm text-gray-400 cursor-not-allowed">
                <i data-lucide="lock" class="w-4 h-4"></i>
                MPESA STK Push (Coming Soon)
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4">
            <div class="rounded-lg border bg-gray-50 border-gray-100 p-3">
              <div class="text-xs text-gray-500">Total</div>
              <div class="text-sm font-semibold text-gray-900">{{ number_format($total, 2) }}</div>
            </div>

            <div class="rounded-lg border p-3 {{ $paidTone }}">
              <div class="text-xs text-gray-600">Paid</div>
              <div class="text-sm font-semibold text-emerald-800">{{ number_format($paid, 2) }}</div>
            </div>

            <div class="rounded-lg border p-3 {{ $balTone }}">
              <div class="text-xs text-gray-600">Balance</div>
              <div class="text-sm font-semibold {{ $balance <= 0 ? 'text-emerald-800' : ($paid > 0 ? 'text-amber-800' : 'text-rose-800') }}">
                {{ number_format($balance, 2) }}
              </div>
            </div>
          </div>

          @if($invoicePaid)
            <div class="mt-3 text-xs text-emerald-700 font-medium">
              ✅ Invoice fully paid — this case is financially closed.
            </div>
          @endif
        </div>

        {{-- Payment progress (show only if NOT settled) --}}
        @if(! $invoicePaid)
          <div class="rounded-lg border border-gray-100 p-4">
            <div class="flex items-center justify-between">
              <div class="text-sm font-semibold text-gray-900">Payment progress</div>
              <div class="text-sm font-semibold text-gray-900">{{ $pct }}%</div>
            </div>

            <div class="mt-3 h-2 w-full bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full bg-amber-500" style="width: {{ $pct }}%;"></div>
            </div>
          </div>
        @endif


        {{-- Record Payment (show only if NOT settled) --}}
        @if(! $invoicePaid)
          <form method="POST"
                action="{{ url('/invoices/'.$invoice->id.'/payments/manual') }}"
                class="rounded-lg border border-gray-100 p-4 space-y-3">
            @csrf

            <div class="flex items-center justify-between gap-3">
              <div class="text-sm font-semibold text-gray-900">Record Payment</div>
              <div class="text-xs text-gray-500">
                This logs a payment entry and updates invoice totals automatically.
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">Amount (KES)</label>
                <input name="amount" type="number" step="0.01" min="0.01" required
                      class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
              </div>

              <div>
                <label class="block text-xs text-gray-500 mb-1">Method</label>
                <input name="method" type="text" placeholder="cash / bank"
                      class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
              </div>

              <div>
                <label class="block text-xs text-gray-500 mb-1">Reference</label>
                <input name="reference" type="text" placeholder="Txn / Receipt No"
                      class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
              </div>

              <div>
                <label class="block text-xs text-gray-500 mb-1">Paid Date</label>
                <input name="paid_at" type="datetime-local"
                      class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
              </div>
            </div>

            <button type="submit"
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-black">
              Save Payment
            </button>
          </form>
        @endif
        @if($invoicePaid)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
          <div class="text-sm font-semibold text-emerald-900">Case closed</div>
          <div class="text-sm text-emerald-800 mt-1">
            Invoice fully paid — this insurance case is financially closed. No further payments can be recorded.
          </div>

          <div class="mt-3 flex flex-wrap gap-2">
            <a href="{{ route('jobs.index') }}"
              class="inline-flex items-center px-3 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-black">
              Back to Jobs
            </a>

            <a href="{{ route('dashboard') }}"
              class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-200 text-sm hover:bg-gray-50">
              Go to Dashboard
            </a>

            <a href="{{ route('invoices.receipt-pdf', $invoice->id) }}"
              class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-200 text-sm hover:bg-gray-50">
              Download Receipt
            </a>
          </div>
        </div>

        {{-- hide/disable payment form --}}
      @else
        {{-- show Record Payment form --}}
      @endif
        {{-- Payment history --}}
        <div class="rounded-lg border border-gray-100">
          <div class="px-3 py-2 border-b border-gray-100 text-xs font-medium text-gray-700">
            Payment History
            <span class="float-right text-gray-400">{{ $payments->count() }} record(s)</span>
          </div>

          @if($payments->count() === 0)
            <div class="p-3 text-sm text-gray-600">No payments recorded yet.</div>
          @else
            <div class="divide-y divide-gray-100">
              @foreach($payments as $pay)
                <div class="p-3 flex items-center justify-between gap-3">
                  <div class="min-w-0">
                    <div class="text-sm font-medium text-gray-900">
                      KES {{ number_format((float) $pay->amount, 2) }}
                    </div>
                    <div class="text-xs text-gray-500">
                      {{ $pay->method ?? 'manual' }}
                      @if(!empty($pay->reference)) • Ref: {{ $pay->reference }} @endif
                    </div>
                  </div>

                  <div class="shrink-0 text-xs text-gray-500">
                    {{ $pay->paid_at ? \Carbon\Carbon::parse($pay->paid_at)->format('Y-m-d H:i') : '—' }}
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>

      @endif
    @endif

  </div>
</div>