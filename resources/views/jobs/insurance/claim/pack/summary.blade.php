<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Claim Summary</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 6px; }
        h2 { font-size: 13px; margin: 14px 0 6px; }
        .muted { color: #666; font-size: 11px; }
        .row { margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        th { background: #f5f5f5; text-align: left; }
    </style>
</head>
<body>
    <h1>Insurance Claim Pack — Summary</h1>
    <div class="muted">Job #{{ $job->id }} • Generated {{ now()->format('Y-m-d H:i') }}</div>

    <div class="row">
        <strong>Customer:</strong> {{ $job->customer->name ?? '—' }} ({{ $job->customer->phone ?? '—' }})<br>
        <strong>Vehicle:</strong>
        {{ trim(($job->vehicle->make ?? '').' '.($job->vehicle->model ?? '')) ?: '—' }}
        • Plate: {{ $job->vehicle->plate_number ?? '—' }}<br>
        <strong>Insurer:</strong>
        {{ $job->insuranceDetail->insurer->name ?? ($job->insuranceDetail->insurer_name ?? '—') }}<br>
        <strong>Policy #:</strong> {{ $job->insuranceDetail->policy_number ?? '—' }} •
        <strong>Claim #:</strong> {{ $job->insuranceDetail->claim_number ?? '—' }}
    </div>

    <h2>Invoice</h2>
    @if($invoice)
        <div class="row">
            <strong>Invoice #:</strong> {{ $invoice->invoice_number ?? ('#'.$invoice->id) }}<br>
            <strong>Status:</strong> {{ ucfirst($invoice->status ?? 'draft') }}<br>
            <strong>Total:</strong> KES {{ number_format((float)($invoice->total_amount ?? 0), 2) }}
        </div>
    @else
        <div class="muted">No invoice found for this job.</div>
    @endif

    <h2>Approved Scope</h2>
    @if($approvalPack && $approvalPack->items && count($approvalPack->items))
        <table>
            <thead>
                <tr>
                    <th style="width:55%;">Description</th>
                    <th style="width:10%;">Qty</th>
                    <th style="width:15%;">Unit</th>
                    <th style="width:20%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($approvalPack->items as $it)
                    <tr>
                        <td>{{ $it->description ?? $it->name ?? '—' }}</td>
                        <td>{{ $it->qty ?? 1 }}</td>
                        <td>{{ number_format((float)($it->unit_price ?? 0), 2) }}</td>
                        <td>{{ number_format((float)($it->total ?? (($it->qty ?? 1) * ($it->unit_price ?? 0))), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="muted">No approved approval pack found yet.</div>
    @endif
</body>
</html>