{{-- resources/views/invoices/pdf.blade.php --}}

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4; margin: 12mm; }
        html, body { margin: 0; padding: 0; width: 100%; }

        /* ✅ Kill Tailwind layout constraints in PDF */
        .container { max-width: none !important; width: 100% !important; }
        [class*="max-w-"] { max-width: none !important; }
        .mx-auto { margin-left: 0 !important; margin-right: 0 !important; }

        /* tables should expand */
        table { width: 100% !important; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="p-6">
        @php
            $mode = $mode ?? 'invoice'; // invoice|receipt
            $paidAmount  = (float) ($paidAmount ?? ($invoice->paid_amount ?? 0));
            $totalAmount = (float) ($totalAmount ?? ($invoice->total_amount ?? 0));
            $balanceAmount = (float) ($balanceAmount ?? max($totalAmount - $paidAmount, 0));
            $payments = $payments ?? collect();
        @endphp

        {{-- ✅ Single canonical document --}}
        @include('invoices._document', [
            'invoice'       => $invoice,
            'mode'          => $mode,
            'payments'      => $payments,
            'paidAmount'    => $paidAmount,
            'totalAmount'   => $totalAmount,
            'balanceAmount' => $balanceAmount,
        ])
    </div>
    <style id="pdf-layout-reset">
    /* A4 page */
    @page { size: A4; margin: 12mm; }

    html, body {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* 🚨 Kill Tailwind layout constraints */
    [class*="max-w-"] {
        max-width: none !important;
        width: 100% !important;
    }

    .mx-auto {
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    .container {
        max-width: none !important;
        width: 100% !important;
    }

    /* ensure tables stretch */
    table {
        width: 100% !important;
    }
    </style>
</body>
</html>
