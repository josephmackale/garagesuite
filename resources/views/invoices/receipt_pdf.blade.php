<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size:12px; color:#111; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:6px 8px; border-bottom:1px solid #eee; vertical-align:top; }
        th { font-weight:700; }
        .text-right { text-align:right; }
        .box { border:1px solid #ddd; padding:14px; border-radius:8px; }
        .small { font-size:11px; color:#666; line-height:1.45; }
        .muted { color:#777; }
        .badge { display:inline-block; padding:2px 8px; border:1px solid #ddd; border-radius:999px; font-size:10px; }
        .totals { width:55%; margin-left:auto; margin-top:10px; }
        .grand { font-size:13px; }

        @page { margin: 22px 26px 26px 26px; }
    </style>
</head>
<body>

@php
    $garage = $garage ?? $invoice->garage ?? null;

    $paid      = (float)($invoice->paid_amount ?? 0);
    $total     = (float)($invoice->total_amount ?? 0);
    $balance   = max(0, $total - $paid);

    // Receipt should show what was actually received:
    // If partially paid, show paid amount as "Amount Received", and still show balance remaining.
    $amountReceived = $paid > 0 ? $paid : $total;

    $plate = $invoice->vehicle->plate_number
        ?? $invoice->vehicle->registration_number
        ?? $invoice->vehicle->reg_no
        ?? '';

    // Payment date fallback:
    // Prefer a dedicated field if you add one later (paid_at), otherwise updated_at is a safe fallback.
    $paidAt = $invoice->paid_at
        ?? ($invoice->payment_date ?? null)
        ?? ($invoice->updated_at ?? null);

    // Payment method/reference fallbacks (wire these to your schema later)
    $paymentMethod = $invoice->payment_method
        ?? $invoice->paid_via
        ?? $invoice->payment_channel
        ?? '—';

    $paymentRef = $invoice->payment_reference
        ?? $invoice->mpesa_receipt
        ?? $invoice->transaction_reference
        ?? null;

    $isFullyPaid = ($total > 0 && $balance <= 0.009);

    $statusLabel = $isFullyPaid ? 'Paid' : 'Partially Paid';

    $thanks =
        "Payment received with thanks. This receipt confirms " .
        ($isFullyPaid ? "full settlement" : "a payment toward") .
        " Invoice #{$invoice->invoice_number}. " .
        "For any questions, please contact " . ($garage->name ?? 'our garage') . ".";
@endphp

{{-- ================= HEADER ================= --}}
<table style="margin-bottom:14px;">
    <tr>
        <td style="width:62%;">
            <div style="font-size:18px; font-weight:800;">
                {{ $garage->name ?? 'Garage' }}
            </div>
            <div class="small muted">
                @if($garage?->phone)<strong>Phone:</strong> {{ $garage->phone }}<br>@endif
                @if($garage?->email)<strong>Email:</strong> {{ $garage->email }}<br>@endif
                @if($garage?->location)<strong>Location:</strong> {{ $garage->location }}@endif
            </div>
        </td>

        <td style="width:38%; text-align:right;">
            <div style="font-size:20px; font-weight:900;">RECEIPT</div>

            <div class="small" style="margin-top:6px;">
                <div><strong>For Invoice:</strong> #{{ $invoice->invoice_number }}</div>
                <div>
                    <strong>Date:</strong>
                    {{ $paidAt ? \Carbon\Carbon::parse($paidAt)->format('d M Y') : \Carbon\Carbon::now()->format('d M Y') }}
                </div>

                <div style="margin-top:6px;">
                    <span class="badge">Status: <strong>{{ $statusLabel }}</strong></span>
                </div>
            </div>

            {{-- AMOUNT RECEIVED --}}
            <div style="margin-top:10px; border:1px solid #111; padding:8px 10px; border-radius:8px;">
                <div class="small muted">AMOUNT RECEIVED</div>
                <div style="font-size:14px; font-weight:900;">
                    KES {{ number_format($amountReceived, 2) }}
                </div>
            </div>
        </td>
    </tr>
</table>

{{-- ================= CUSTOMER / VEHICLE ================= --}}
<div class="box" style="margin-bottom:14px;">
    <table>
        <tr>
            <td style="width:50%;">
                <div style="font-weight:800; margin-bottom:6px;">Received From</div>
                <div class="small">
                    {{ $invoice->customer?->name ?? '—' }}<br>
                    @if($invoice->customer?->phone){{ $invoice->customer->phone }}<br>@endif
                    @if($invoice->customer?->email){{ $invoice->customer->email }}@endif
                </div>
            </td>

            <td style="width:50%; border-left:1px solid #eee; padding-left:10px;">
                <div style="font-weight:800; margin-bottom:6px;">Vehicle</div>
                <div class="small">
                    @if($plate)<strong>Plate:</strong> {{ $plate }}<br>@endif
                    {{ $invoice->vehicle?->make }} {{ $invoice->vehicle?->model }}<br>
                    @if($invoice->vehicle?->year)<strong>Year:</strong> {{ $invoice->vehicle->year }}@endif
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ================= PAYMENT DETAILS ================= --}}
<div class="box" style="margin-bottom:14px;">
    <div style="font-weight:800; margin-bottom:8px;">Payment Details</div>

    <table>
        <tr>
            <td class="muted" style="width:35%;">Payment Method</td>
            <td>{{ $paymentMethod }}</td>
        </tr>

        @if($paymentRef)
        <tr>
            <td class="muted">Reference</td>
            <td>{{ $paymentRef }}</td>
        </tr>
        @endif

        <tr>
            <td class="muted">Invoice Total</td>
            <td>KES {{ number_format($total, 2) }}</td>
        </tr>

        <tr>
            <td class="muted">Amount Received</td>
            <td><strong>KES {{ number_format($amountReceived, 2) }}</strong></td>
        </tr>

        @if(!$isFullyPaid)
        <tr>
            <td class="muted">Balance Remaining</td>
            <td><strong>KES {{ number_format($balance, 2) }}</strong></td>
        </tr>
        @endif
    </table>
</div>

{{-- ================= FOOTNOTE / THANK YOU ================= --}}
<div class="small muted">
    {{ $thanks }}
</div>

</body>
</html>
