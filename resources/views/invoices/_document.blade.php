{{-- resources/views/invoices/_document.blade.php --}}

@php
    $garage = $invoice->garage ?? auth()->user()?->garage;

    // Browser-friendly URL (invoice preview page)
    $logoUrl = $garage?->logo_path
        ? asset('storage/' . $garage->logo_path)
        : null;

    // PDF-safe absolute file path (DomPDF/Browsershot usually prefer this)
    $logoAbs = $garage?->logo_path
        ? public_path('storage/' . $garage->logo_path)
        : null;

    // Detect PDF context (covers common routes + any /pdf path)
    $isPdf = request()->routeIs('invoices.pdf', 'invoices.receipt-pdf')
        || str_contains((string) request()->path(), 'pdf');

    // If PDF, prefer local file path
    if ($isPdf && $logoAbs && file_exists($logoAbs)) {
        $logoUrl = 'file://' . $logoAbs;
    }

    $mode = $mode ?? 'invoice';
    $isReceipt = $mode === 'receipt';

    $paidAmount    = (float) ($paidAmount ?? ($invoice->paid_amount ?? 0));
    $totalAmount   = (float) ($totalAmount ?? ($invoice->total_amount ?? 0));
    $balanceAmount = (float) ($balanceAmount ?? max($totalAmount - $paidAmount, 0));

    $payments = $payments ?? collect();

@endphp

@php
    if (!$invoice->relationLoaded('items')) {
        try { $invoice->load('items'); } catch (\Throwable $e) {}
    }
    $dbgCount = $invoice->items?->count() ?? 0;
@endphp

@if(config('app.debug') && !$isPdf)
    <div class="text-xs text-red-600 mb-2">
        DEBUG items_count={{ $dbgCount }} • route={{ request()->path() }} • isPdf={{ $isPdf ? 'yes' : 'no' }}
    </div>
@endif

{{-- INVOICE HEADER --}}
<div class="flex items-start justify-between gap-8">
    @php
        // Force load items (handles preventLazyLoading or missing eager-load)
        if (!$invoice->relationLoaded('items')) $invoice->load('items');

        $itemsCount = $invoice->items?->count() ?? 0;
    @endphp

    @if(config('app.debug') && !$isPdf)
        <div class="text-xs text-red-600 mb-2">DEBUG items_count={{ $itemsCount }}</div>
    @endif

    {{-- LEFT: Logo + Garage details --}}
    <div class="flex items-start gap-4 min-w-0">
        {{-- Standard logo frame --}}
        <div class="pdf-logo-frame w-40 h-14 flex items-center justify-start overflow-hidden flex-shrink-0">
            @if($logoUrl)
                <img
                    src="{{ $logoUrl }}"
                    alt="Garage logo"
                    class="max-w-full max-h-full object-contain"
                >
            @else
                <div class="w-full h-full rounded bg-gray-50 border border-gray-200"></div>
            @endif
        </div>

        <div class="min-w-0 pt-0.5 text-center">
            <div class="text-base sm:text-lg font-bold text-gray-900 leading-tight">
                {{ $garage->name ?? $garage->label ?? 'Garage' }}
            </div>

            <div class="mt-1 text-sm text-gray-500 leading-tight space-y-0.5">
                @if($garage?->phone) <div>{{ $garage->phone }}</div> @endif
                @if($garage?->email) <div>{{ $garage->email }}</div> @endif
            </div>
        </div>
    </div>

    {{-- RIGHT: Invoice meta --}}
    <div class="text-right">
        <div class="text-sm text-gray-500">
            {{ $isReceipt ? 'Receipt' : 'Invoice' }}
        </div>
        <div class="text-xl font-extrabold text-gray-900 leading-tight">
            {{ $invoice->invoice_number }}
        </div>

        <div class="text-xs uppercase tracking-wide text-gray-500">
            {{ $isReceipt ? 'Receipt No' : 'Invoice No' }}
        </div>
        <div class="text-xl font-extrabold text-gray-900 leading-tight">
            {{ $invoice->invoice_number }}
        </div>

        {{-- 🔥 LPO (Insurance Reference) --}}
        @if(!empty($invoice->lpo_number))
            <div class="mt-1 text-sm text-gray-700">
                <span class="font-semibold">LPO:</span> {{ $invoice->lpo_number }}
            </div>
        @endif
        <div class="mt-3 inline-block text-right">
            <div class="text-xs text-gray-500">Issue Date</div>
            <div class="text-sm font-semibold text-gray-900">
                {{ \Illuminate\Support\Carbon::parse($invoice->issue_date)->format('d M Y') }}
            </div>

            <div class="mt-2 flex items-center justify-end gap-2">
                <span class="text-xs text-gray-500">Status</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs
                    @if(($invoice->status ?? 'draft') === 'sent' || ($invoice->status ?? 'draft') === 'paid')
                        bg-green-100 text-green-700
                    @elseif(($invoice->status ?? 'draft') === 'cancelled')
                        bg-red-100 text-red-700
                    @else
                        bg-gray-100 text-gray-700
                    @endif
                ">
                    {{ ucfirst($invoice->status ?? 'draft') }}
                </span>
            </div>

            <div class="mt-2 flex items-center justify-end gap-2">
                <span class="text-xs text-gray-500">Payment</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs
                    @if(($invoice->payment_status ?? 'unpaid') === 'paid')
                        bg-green-100 text-green-700
                    @elseif(($invoice->payment_status ?? 'unpaid') === 'partial')
                        bg-yellow-100 text-yellow-800
                    @else
                        bg-red-100 text-red-700
                    @endif
                ">
                    {{ ucfirst($invoice->payment_status ?? 'unpaid') }}
                </span>
            </div>
        </div>
    </div>
</div>

<hr class="border-gray-200 my-5">


{{-- CUSTOMER & VEHICLE DETAILS --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-sm">
    {{-- CUSTOMER DETAILS --}}
    <div>
        <h3 class="font-semibold text-gray-900 mb-2">Customer Details</h3>

        <div class="space-y-1">
            <div>
                <span class="text-gray-500">Name:</span>
                <span class="font-medium text-gray-900">
                    {{ $invoice->customer?->name ?? '—' }}
                </span>
            </div>

            <div>
                <span class="text-gray-500">Phone:</span>
                <span class="text-gray-900">
                    {{ $invoice->customer?->phone ?? '—' }}
                </span>
            </div>

            <div>
                <span class="text-gray-500">Email:</span>
                <span class="text-gray-900">
                    {{ $invoice->customer?->email ?? '—' }}
                </span>
            </div>
        </div>
    </div>

    {{-- VEHICLE DETAILS --}}
    <div>
        <h3 class="font-semibold text-gray-900 mb-2">Vehicle Details</h3>

        @if($invoice->vehicle)
            <div class="space-y-1">
                <div>
                    <span class="text-gray-500">Plate:</span>
                    <span class="font-medium text-gray-900">
                        {{ $invoice->vehicle->registration_number
                            ?? $invoice->vehicle->plate_number
                            ?? '—' }}
                    </span>
                </div>

                <div>
                    <span class="text-gray-500">Model:</span>
                    <span class="text-gray-900">
                        {{ trim(($invoice->vehicle->make ?? '') . ' ' . ($invoice->vehicle->model ?? '')) ?: '—' }}
                    </span>
                </div>

                <div>
                    <span class="text-gray-500">Year:</span>
                    <span class="text-gray-900">
                        {{ $invoice->vehicle->year ?? '—' }}
                    </span>
                </div>

                <div>
                    <span class="text-gray-500">VIN:</span>
                    <span class="text-gray-900">
                        {{ $invoice->vehicle->vin ?? '—' }}
                    </span>
                </div>
            </div>
        @else
            <div class="text-gray-500">No vehicle linked.</div>
        @endif
    </div>
</div>

{{-- ITEMS --}}
<div style="margin-top:18px;">
    <h3 style="font-weight:700; font-size:14px; margin-bottom:10px; color:#111827;">
        Items
    </h3>

    @php
        // Ensure loaded (no-op if already loaded)
        if (!$invoice->relationLoaded('items')) {
            try { $invoice->load('items'); } catch (\Throwable $e) {}
        }
        $items = $invoice->items ?? collect();
    @endphp

    @if ($items->isEmpty())
        <div style="font-size:12px; color:#6b7280;">
            No items yet. (Automatically created from labour and parts on the job.)
        </div>
    @else
        <div style="border:1px solid #e5e7eb; border-radius:10px; overflow:auto; max-width:100%; background:#fff;">
            <table style="width:100%; border-collapse:collapse; font-size:12px;">
                <thead style="background:#f9fafb;">
                    <tr style="color:#374151; text-transform:uppercase; font-size:11px;">
                        <th style="padding:10px 12px; text-align:left; border-bottom:1px solid #e5e7eb;">Description</th>
                        <th style="padding:10px 12px; text-align:right; border-bottom:1px solid #e5e7eb; width:70px;">Qty</th>
                        <th style="padding:10px 12px; text-align:right; border-bottom:1px solid #e5e7eb; width:110px;">Unit</th>
                        <th style="padding:10px 12px; text-align:right; border-bottom:1px solid #e5e7eb; width:120px;">Total</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($items as $item)
                        @php
                            $type = $item->item_type ?? 'item';
                            $qty  = ($type === 'labour') ? null : (float)($item->quantity ?? 1);
                            $unit = (float)($item->unit_price ?? 0);
                            $line = (float)($item->line_total ?? (($qty ?? 1) * $unit));
                        @endphp

                        <tr>
                            <td style="padding:10px 12px; border-bottom:1px solid #f3f4f6; vertical-align:top;">
                                <div style="color:#111827; line-height:1.2;">
                                    {{ $item->description }}
                                </div>
                                <div style="font-size:10px; color:#9ca3af; text-transform:uppercase; margin-top:2px;">
                                    {{ $type }}
                                </div>
                            </td>

                            <td style="padding:10px 12px; border-bottom:1px solid #f3f4f6; text-align:right; color:#374151; vertical-align:top;">
                                @if($type === 'labour')
                                    —
                                @else
                                    {{ rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.') }}
                                @endif
                            </td>

                            <td style="padding:10px 12px; border-bottom:1px solid #f3f4f6; text-align:right; color:#374151; vertical-align:top;">
                                KES {{ number_format($unit, 2) }}
                            </td>

                            <td style="padding:10px 12px; border-bottom:1px solid #f3f4f6; text-align:right; color:#111827; font-weight:700; vertical-align:top;">
                                KES {{ number_format($line, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- TOTALS --}}
        <div style="display:flex; justify-content:flex-end; margin-top:14px;">
            <div style="width:100%; border:1px solid #e5e7eb; border-radius:10px; background:#f9fafb; padding:12px 14px; font-size:12px;">
                <div style="display:flex; justify-content:space-between; font-weight:700; color:#111827; margin-bottom:6px;">
                    <span>Subtotal</span>
                    <span>KES {{ number_format((float)($invoice->subtotal ?? 0), 2) }}</span>
                </div>

                <div style="display:flex; justify-content:space-between; font-weight:700; color:#111827; margin-bottom:10px;">
                    <span>VAT (16%)</span>
                    <span>KES {{ number_format((float)($invoice->tax_amount ?? 0), 2) }}</span>
                </div>

                <div style="border-top:1px solid #e5e7eb; padding-top:8px; display:flex; justify-content:space-between; color:#111827;">
                    <span style="font-weight:700;">Total</span>
                    <span style="font-weight:800;">KES {{ number_format((float)($invoice->total_amount ?? 0), 2) }}</span>
                </div>
                @if($isReceipt)
                    <div style="border-top:1px dashed #e5e7eb; margin-top:10px; padding-top:10px;">
                        <div style="display:flex; justify-content:space-between; font-weight:700; color:#111827; margin-bottom:6px;">
                            <span>Paid</span>
                            <span>KES {{ number_format($paidAmount, 2) }}</span>
                        </div>

                        <div style="display:flex; justify-content:space-between; font-weight:700; color:#111827;">
                            <span>Balance</span>
                            <span>KES {{ number_format($balanceAmount, 2) }}</span>
                        </div>

                        @if($payments instanceof \Illuminate\Support\Collection && $payments->count())
                            <div style="margin-top:10px; font-size:11px; color:#374151;">
                                <div style="font-weight:700; margin-bottom:6px; color:#111827;">Payment References</div>

                                <div style="display:flex; flex-direction:column; gap:4px;">
                                    @foreach($payments as $p)
                                        <div style="display:flex; justify-content:space-between; gap:12px;">
                                            <div style="min-width:0;">
                                                <span style="font-weight:700;">KES {{ number_format((float)($p->amount ?? 0), 2) }}</span>
                                                @if(!empty($p->mpesa_receipt_number))
                                                    <span style="color:#6b7280;"> • Ref: {{ $p->mpesa_receipt_number }}</span>
                                                @elseif(!empty($p->reference))
                                                    <span style="color:#6b7280;"> • Ref: {{ $p->reference }}</span>
                                                @endif
                                            </div>

                                            <div style="white-space:nowrap; color:#6b7280;">
                                                @if(!empty($p->paid_at))
                                                    {{ \Illuminate\Support\Carbon::parse($p->paid_at)->format('d M Y H:i') }}
                                                @else
                                                    {{ \Illuminate\Support\Carbon::parse($p->created_at)->format('d M Y H:i') }}
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

{{-- PAYMENT DETAILS (client-facing) --}}
@php
    $garagePayment = $invoice->garage?->payment_methods
        ?? auth()->user()?->garage?->payment_methods
        ?? [];

    $mpesaNumber  = data_get($garagePayment, 'mpesa.number');
    $mpesaType    = data_get($garagePayment, 'mpesa.type', 'paybill');
    $mpesaAccount = data_get($garagePayment, 'mpesa.account');

    if ($mpesaAccount) {
        $mpesaAccount = str_replace('{invoice_number}', $invoice->invoice_number, $mpesaAccount);
    }

    $bankName      = data_get($garagePayment, 'bank.bank_name');
    $bankAccName   = data_get($garagePayment, 'bank.account_name');
    $bankAccNumber = data_get($garagePayment, 'bank.account_number');

    $hasPaymentInfo = !empty($mpesaNumber) || !empty($bankAccNumber);
@endphp

@if(!$isReceipt && ($invoice->payment_status ?? 'unpaid') !== 'paid' && $hasPaymentInfo)
    <div class="mt-5 rounded-xl border border-gray-200 bg-white p-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Payment Details</h3>
            <span class="text-xs text-gray-500">Use these details to pay</span>
        </div>

        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            {{-- M-PESA --}}
            @if($mpesaNumber)
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                    <div class="text-xs text-gray-500">M-PESA</div>
                    <div class="mt-1 text-gray-800">
                        <span class="font-medium">{{ ucfirst($mpesaType) }}:</span>
                        {{ $mpesaNumber }}
                    </div>
                    @if($mpesaAccount)
                        <div class="mt-1 text-gray-800">
                            <span class="font-medium">Account:</span>
                            {{ $mpesaAccount }}
                        </div>
                    @endif
                </div>
            @endif

            {{-- Bank --}}
            @if($bankAccNumber)
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                    <div class="text-xs text-gray-500">Bank Transfer</div>

                    @if($bankName)
                        <div class="mt-1 text-gray-800">
                            <span class="font-medium">Bank:</span> {{ $bankName }}
                        </div>
                    @endif

                    @if($bankAccName)
                        <div class="mt-1 text-gray-800">
                            <span class="font-medium">Account Name:</span> {{ $bankAccName }}
                        </div>
                    @endif

                    <div class="mt-1 text-gray-800">
                        <span class="font-medium">Account No:</span> {{ $bankAccNumber }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif

{{-- CLIENT NOTICE (keep draft warning; don't include internal record-payment form) --}}
<div class="border-t pt-4 mt-6">
    @if(($invoice->status ?? 'draft') === 'draft')
        <div class="p-3 rounded bg-yellow-50 text-yellow-800 text-sm">
            This invoice is <b>Draft</b>. Send it before recording payment.
        </div>
    @elseif(($invoice->status ?? '') === 'cancelled')
        <div class="p-3 rounded bg-gray-50 text-gray-700 text-sm">
            This invoice is <b>Cancelled</b>.
        </div>
    @endif
</div>
