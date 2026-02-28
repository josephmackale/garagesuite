{{-- resources/views/pdf/invoice.blade.php --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>

    <style>
        @page { margin: 22px 26px 110px 26px; }

        * { box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 0;
            padding: 0;
        }

        /* Optional: watermark like your old template */
        .watermark {
            position: fixed;
            top: 42%;
            left: 8%;
            width: 84%;
            text-align: center;
            opacity: 0.05;
            font-size: 64px;
            font-weight: 900;
            letter-spacing: 2px;
            transform: rotate(-20deg);
            z-index: -1;
        }

        /* Optional: fixed footer zone (kept for future use) */
        .footer {
            position: fixed;
            left: 0; right: 0;
            bottom: -80px;
        }
        .footer-box {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 14px;
        }
        .footer-header {
            font-size: 12px;
            font-weight: 900;
            margin: 0 0 8px 0;
            padding-bottom: 6px;
            border-bottom: 1px solid #eef2f7;
        }
        .small { font-size: 11px; color:#6b7280; line-height:1.45; }
    </style>
</head>

<body>
@php
    // Prefer invoice->garage (more correct) then fallback to passed $garage
    $g = $invoice->garage ?? ($garage ?? null);

    $garageName = (string)($g?->name ?? $g?->label ?? 'Garage');

    // Logo absolute path for PDF safety (if you want the same logo inside _document)
    // NOTE: your _document currently uses asset('storage/...') which DOMPDF may not load depending on config.
    // We'll precompute this and make it available as $pdfLogoAbs if you decide to use it in _document.
    $logoPath = $g?->logo_path;
    $pdfLogoAbs = $logoPath ? public_path('storage/' . $logoPath) : null;
@endphp

{{-- Watermark --}}
<div class="watermark">{{ $garageName }}</div>

{{-- ================= INVOICE DOCUMENT ================= --}}
{{-- This is the single source of truth: your drafted invoice layout --}}
<div style="padding: 0;">
    @include('invoices._document', ['invoice' => $invoice])
</div>

{{-- ================= OPTIONAL FOOTER ================= --}}
{{-- Kept for later. If you don't want any footer at all, remove this whole block. --}}
@if(false)
    <div class="footer">
        <div class="footer-box">
            <div class="footer-header">Payment & Acknowledgement</div>
            <div class="small">
                Thank you for your business.
            </div>
        </div>
    </div>
@endif

</body>
</html>
