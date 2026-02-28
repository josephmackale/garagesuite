<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .muted { color: #666; }
        .h1 { font-size: 18px; font-weight: 700; margin: 0 0 6px; }
        .h2 { font-size: 14px; font-weight: 700; margin: 18px 0 8px; }
        .box { border: 1px solid #ddd; padding: 10px; border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 7px; vertical-align: top; }
        th { background: #f6f6f6; text-align: left; }
        .right { text-align: right; }
        .grid { width: 100%; }
        .photo { width: 48%; display: inline-block; margin: 0 1% 12px; }
        .photo img { width: 100%; border: 1px solid #ddd; }
        .small { font-size: 11px; }
    </style>
</head>
<body>

    <div class="h1">Insurance Approval Pack</div>
    <div class="muted small">
        Pack #{{ $pack->id }} · Version {{ $pack->version ?? 1 }} · Status: {{ strtoupper($pack->status ?? 'draft') }}
    </div>

    <div style="height:10px"></div>

    <div class="box">
        <table class="grid" style="border:none">
            <tr>
                <td style="border:none; width: 50%;">
                    <div><b>Case:</b> {{ $jobRow->job_number ?: ('Job #' . $jobRow->job_id) }}</div>
                    <div><b>Customer:</b> {{ $jobRow->customer_name ?: 'N/A' }}</div>
                    <div><b>Vehicle:</b> {{ $jobRow->vehicle_make }} {{ $jobRow->vehicle_model }} ({{ $jobRow->vehicle_year }})</div>
                    <div><b>Plate:</b> {{ $jobRow->vehicle_reg }}</div>
                </td>
                <td style="border:none; width: 50%;">
                    <div><b>Currency:</b> {{ $pack->currency ?? 'KES' }}</div>
                    <div><b>Total:</b> {{ number_format((float)$pack->total_amount, 2) }}</div>
                    <div><b>Generated:</b> {{ $pack->generated_at }}</div>
                    <div><b>Submitted:</b> {{ $pack->submitted_at ?: '-' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="h2">Quotation Snapshot</div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Type</th>
                <th>Description</th>
                <th style="width: 10%;" class="right">Qty</th>
                <th style="width: 18%;" class="right">Unit</th>
                <th style="width: 18%;" class="right">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $it)
                <tr>
                    <td>{{ $it->line_type }}</td>
                    <td>
                        <b>{{ $it->name }}</b>
                        @if(!empty($it->description))
                            <div class="muted small">{{ $it->description }}</div>
                        @endif
                    </td>
                    <td class="right">{{ number_format((float)$it->qty, 2) }}</td>
                    <td class="right">{{ number_format((float)$it->unit_price, 2) }}</td>
                    <td class="right">{{ number_format((float)$it->line_total, 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="4" class="right"><b>Total</b></td>
                <td class="right"><b>{{ number_format((float)$pack->total_amount, 2) }}</b></td>
            </tr>
        </tbody>
    </table>

    <div class="h2">Inspection Photos Snapshot</div>

    @php
        $photosArr = $photos->all();
    @endphp

    @if(count($photosArr))
        <table width="100%" cellspacing="0" cellpadding="6" style="border:none;">
            @foreach(collect($photosArr)->chunk(2) as $row)
                <tr>
                    @foreach($row as $p)
                        @php
                            $localPath = storage_path('app/public/' . ltrim($p->storage_path ?? '', '/'));
                        @endphp

                        <td width="50%" valign="top" style="border:none; padding:6px;">
                            @if($localPath && file_exists($localPath))
                                <img src="file://{{ $localPath }}"
                                    style="width:100%; max-height:260px; object-fit:cover; border:1px solid #ddd;"
                                    alt="Photo">
                            @else
                                <div class="box small">
                                    Missing file: {{ $p->storage_path }}
                                </div>
                            @endif

                            <div class="muted small" style="margin-top:4px;">
                                {{ $p->category }} · {{ $p->label }} · #{{ $p->id }}
                            </div>
                        </td>
                    @endforeach

                    {{-- keep 2 columns even if odd photo count --}}
                    @if($row->count() === 1)
                        <td width="50%" style="border:none;"></td>
                    @endif
                </tr>
            @endforeach
        </table>
    @else
        <div class="muted">No photos captured in this pack.</div>
    @endif

</body>
</html>
