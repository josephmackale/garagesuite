{{-- resources/views/jobs/job-card-pdf.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Job Card {{ $job->job_number ?? ('Job #'.$job->id) }}</title>

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 0;
            padding: 24px;
        }

        .page {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .garage-left {
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .garage-logo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            overflow: hidden;
        }

        .garage-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .garage-name {
            font-size: 18px;
            font-weight: 700;
        }

        .garage-sub {
            font-size: 11px;
            color: #6b7280;
        }

        .job-meta {
            text-align: right;
            font-size: 11px;
        }

        .job-meta strong {
            font-size: 13px;
        }

        .qr-wrapper {
            margin-top: 6px;
        }

        h2.section-title {
            font-size: 13px;
            font-weight: 600;
            margin: 18px 0 6px;
        }

        table.meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.meta-table th,
        table.meta-table td {
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }

        table.meta-table th {
            background: #f9fafb;
            font-weight: 600;
            font-size: 11px;
            text-align: left;
            width: 20%;
        }

        .box {
            border: 1px solid #e5e7eb;
            min-height: 65px;
            padding: 6px 8px;
            white-space: pre-wrap;
        }

        .muted {
            color: #9ca3af;
            font-style: italic;
        }

        table.parts-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }

        table.parts-table th,
        table.parts-table td {
            border: 1px solid #e5e7eb;
            padding: 5px 6px;
            font-size: 11px;
        }

        table.parts-table th {
            background: #f9fafb;
            text-align: left;
            font-weight: 600;
        }

        .signature-row {
            margin-top: 26px;
            display: flex;
            justify-content: space-between;
            gap: 24px;
        }

        .signature-block {
            flex: 1;
            font-size: 11px;
        }

        .signature-line {
            margin-top: 28px;
            border-bottom: 1px solid #9ca3af;
            height: 1px;
        }

        .signature-label {
            margin-top: 4px;
            color: #6b7280;
        }

        /* Checklist page */
        .two-column {
            display: flex;
            gap: 16px;
        }

        .two-column > div {
            flex: 1;
        }

        table.checklist-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        table.checklist-table th,
        table.checklist-table td {
            border: 1px solid #e5e7eb;
            padding: 4px 5px;
            font-size: 10px;
        }

        table.checklist-table th {
            background: #f9fafb;
            font-weight: 600;
        }

        .tick-cell {
            text-align: center;
            width: 20px;
        }

        .page-break {
            page-break-after: always;
        }

        /* Watermark */
        .watermark {
            position: fixed;
            top: 45%;
            left: 5%;
            font-size: 72px;
            font-weight: 700;
            color: #e5e7eb;
            opacity: 0.2;
            transform: rotate(-25deg);
        }
    </style>
</head>
<body>
@php
    $garage = auth()->user()->garage ?? $job->garage ?? null;
@endphp

<div class="watermark">GARAGESUITE</div>

{{-- PAGE 1: Job Card --}}
<div class="page">
    {{-- HEADER --}}
    <div class="header-row">
        <div class="garage-left">
            <div class="garage-logo">
                @if(!empty($logoBase64))
                    <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo">
                @endif
            </div>
            <div>
                <div class="garage-name">
                    {{ $garage->name ?? 'Garage Name' }}
                </div>
                @if($garage?->address)
                    <div class="garage-sub">{{ $garage->address }}</div>
                @endif
                @if($garage?->phone)
                    <div class="garage-sub">Tel: {{ $garage->phone }}</div>
                @endif
            </div>
        </div>

        <div class="job-meta">
            <div><strong>JOB CARD</strong></div>
            <div>No: {{ $job->job_number ?? ('Job #'.$job->id) }}</div>
            <div>Date: {{ optional($job->job_date ?? $job->created_at)->format('d M Y') }}</div>
            <div>Status: {{ ucfirst(str_replace('_', ' ', $job->status ?? 'pending')) }}</div>

            @if(!empty($qrSvg))
                <div class="qr-wrapper">
                    {{-- QR code for quick access to job in system --}}
                    {!! $qrSvg !!}
                </div>
            @endif
        </div>
    </div>

    {{-- CUSTOMER + VEHICLE --}}
    <h2 class="section-title">Customer & Vehicle</h2>
    <table class="meta-table">
        <tr>
            <th>Customer</th>
            <td>
                {{ $job->vehicle?->customer?->name ?? '—' }}<br>
                @if($job->vehicle?->customer?->phone)
                    Phone: {{ $job->vehicle->customer->phone }}
                @endif
            </td>
            <th>Job No.</th>
            <td>{{ $job->job_number ?? $job->id }}</td>
        </tr>
        <tr>
            <th>Vehicle</th>
            <td>
                {{ $job->vehicle?->registration_number ?? '—' }}<br>
                {{ $job->vehicle?->make }} {{ $job->vehicle?->model }}
            </td>
            <th>Mileage In</th>
            <td>{{ $job->mileage ? number_format($job->mileage).' km' : '________________' }}</td>
        </tr>
        <tr>
            <th>Service Type</th>
            <td>{{ $job->service_type ?: '________________' }}</td>
            <th>Technician</th>
            <td>________________</td>
        </tr>
    </table>

    {{-- CUSTOMER REQUESTED SERVICES --}}
    <h2 class="section-title">Customer Requested Services</h2>
    <div class="box">
        @if($job->customer_complaint ?? null)
            {{ $job->customer_complaint }}
        @elseif($job->complaint ?? null)
            {{ $job->complaint }}
        @else
            <span class="muted">Record what the customer requested or complained about.</span>
        @endif
    </div>

    {{-- DIAGNOSIS --}}
    <h2 class="section-title">Diagnosis (Mechanic)</h2>
    <div class="box">
        @if($job->diagnosis)
            {{ $job->diagnosis }}
        @else
            <span class="muted">Mechanic to record diagnosis here.</span>
        @endif
    </div>

    {{-- FOUND ISSUES --}}
    <h2 class="section-title">Found Issues During Inspection</h2>
    <div class="box">
        <span class="muted">List additional issues noted during inspection (beyond customer complaint).</span>
    </div>

    {{-- WORK DONE --}}
    <h2 class="section-title">Work Done</h2>
    <div class="box">
        @if($job->work_done ?? null)
            {{ $job->work_done }}
        @else
            <span class="muted">Mechanic to list work carried out, including labour tasks performed.</span>
        @endif
    </div>

    {{-- PARTS USED (no prices) --}}
    <h2 class="section-title">Parts Used</h2>
    <table class="parts-table">
        <thead>
        <tr>
            <th style="width: 55%;">Part Description</th>
            <th style="width: 15%;">Qty</th>
            <th style="width: 30%;">Notes</th>
        </tr>
        </thead>
        <tbody>
        @for($i = 0; $i < 6; $i++)
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        @endfor
        </tbody>
    </table>

    {{-- INTERNAL NOTES --}}
    <h2 class="section-title">Internal Notes</h2>
    <div class="box">
        @if($job->internal_notes ?? null)
            {{ $job->internal_notes }}
        @elseif($job->notes ?? null)
            {{ $job->notes }}
        @else
            <span class="muted">For workshop / management use only.</span>
        @endif
    </div>

    {{-- SIGNATURES --}}
    <div class="signature-row">
        <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-label">Mechanic Signature & Date</div>
        </div>
        <div class="signature-block">
            <div class="signature-line"></div>
            <div class="signature-label">Supervisor / Service Advisor</div>
        </div>
    </div>
</div>

<div class="page-break"></div>

{{-- PAGE 2: Inspection Checklist --}}
<div class="page">
    <h2 class="section-title">Vehicle Inspection Checklist</h2>

    <div class="two-column">
        <div>
            <table class="checklist-table">
                <thead>
                <tr>
                    <th>Exterior & Tyres</th>
                    <th class="tick-cell">OK</th>
                    <th class="tick-cell">Attn</th>
                    <th class="tick-cell">N/A</th>
                </tr>
                </thead>
                <tbody>
                @foreach(['Body damage', 'Windscreen & windows', 'Wipers & washers', 'Tyre tread & condition', 'Tyre pressure', 'Spare wheel & tools'] as $item)
                    <tr>
                        <td>{{ $item }}</td>
                        <td class="tick-cell">□</td>
                        <td class="tick-cell">□</td>
                        <td class="tick-cell">□</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <table class="checklist-table">
                <thead>
                <tr>
                    <th>Lights & Electrical</th>
                    <th class="tick-cell">OK</th>
                    <th class="tick-cell">Attn</th>
                    <th class="tick-cell">N/A</th>
                </tr>
                </thead>
                <tbody
