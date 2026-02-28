<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Job Card - {{ $job->job_number ?? 'JOB-'.$job->id }}</title>

    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .header p {
            margin: 2px 0;
        }
        .section {
            margin-bottom: 12px;
        }
        .section-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 4px;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 2px 0;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            width: 25%;
        }
        .value {
            width: 75%;
        }
        .two-col {
            width: 100%;
        }
        .two-col td {
            width: 50%;
        }
        .box {
            border: 1px solid #ddd;
            padding: 6px;
            min-height: 40px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #555;
        }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div class="header">
        <h1>{{ $job->garage->name ?? 'Garage' }}</h1>
        @if($job->garage)
            <p>{{ $job->garage->address ?? '' }} {{ $job->garage->city ?? '' }}</p>
            <p>Phone: {{ $job->garage->phone ?? '' }}  Email: {{ $job->garage->email ?? '' }}</p>
        @endif
        <p><strong>JOB CARD</strong></p>
    </div>

    {{-- JOB SUMMARY --}}
    <div class="section">
        <div class="section-title">Job Summary</div>
        <table>
            <tr>
                <td class="label">Job Number:</td>
                <td class="value">{{ $job->job_number ?? 'JOB-'.$job->id }}</td>
            </tr>
            <tr>
                <td class="label">Date:</td>
                <td class="value">
                    {{ optional($job->job_date)->format('d M Y') ?? optional($job->created_at)->format('d M Y') }}
                </td>
            </tr>
            <tr>
                <td class="label">Status:</td>
                <td class="value">{{ ucfirst(str_replace('_',' ', $job->status)) }}</td>
            </tr>
            <tr>
                <td class="label">Service Type:</td>
                <td class="value">{{ $job->service_type ?? '' }}</td>
            </tr>
        </table>
    </div>

    {{-- CUSTOMER & VEHICLE --}}
    <div class="section">
        <div class="section-title">Customer & Vehicle</div>
        <table class="two-col">
            <tr>
                <td>
                    <table>
                        <tr>
                            <td class="label">Customer:</td>
                            <td class="value">{{ $job->customer->name ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Phone:</td>
                            <td class="value">{{ $job->customer->phone ?? '' }}</td>
                        </tr>
                    </table>
                </td>
                <td>
                    <table>
                        <tr>
                            <td class="label">Vehicle:</td>
                            <td class="value">
                                @if($job->vehicle)
                                    {{ $job->vehicle->make }} {{ $job->vehicle->model }}
                                    @if($job->vehicle->year)
                                        ({{ $job->vehicle->year }})
                                    @endif
                                @else
                                    
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Reg No:</td>
                            <td class="value">{{ $job->vehicle->registration_number ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Mileage:</td>
                            <td class="value">
                                @if($job->mileage)
                                    {{ number_format($job->mileage) }} km
                                @else
                                    
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- COMPLAINT / DIAGNOSIS --}}
    <div class="section">
        <div class="section-title">Complaint (Customer)</div>
        <div class="box">
            {!! nl2br(e($job->complaint ?: '')) !!}
        </div>
    </div>

    <div class="section">
        <div class="section-title">Diagnosis (Mechanic)</div>
        <div class="box">
            {!! nl2br(e($job->diagnosis ?: '')) !!}
        </div>
    </div>

    {{-- WORK DONE / PARTS USED --}}
    <div class="section">
        <div class="section-title">Work Done</div>
        <div class="box">
            {!! nl2br(e($job->work_done ?: '')) !!}
        </div>
    </div>

    <div class="section">
        <div class="section-title">Parts Used</div>
        <div class="box">
            {!! nl2br(e($job->parts_used ?: '')) !!}
        </div>
    </div>

    {{-- COST SUMMARY --}}
    <div class="section">
        <div class="section-title">Cost Summary</div>
        <table>
            <tr>
                <td class="label">Labour Cost:</td>
                <td class="value">
                    @if(!is_null($job->labour_cost))
                        KES {{ number_format($job->labour_cost, 2) }}
                    @else
                        
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Parts Cost:</td>
                <td class="value">
                    @if(!is_null($job->parts_cost))
                        KES {{ number_format($job->parts_cost, 2) }}
                    @else
                        
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Estimated Cost:</td>
                <td class="value">
                    @if(!is_null($job->estimated_cost))
                        KES {{ number_format($job->estimated_cost, 2) }}
                    @else
                        
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Final Cost:</td>
                <td class="value">
                    @if(!is_null($job->final_cost))
                        <strong>KES {{ number_format($job->final_cost, 2) }}</strong>
                    @else
                        
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- SIGNATURES / FOOTER --}}
    <div class="section">
        <table class="two-col">
            <tr>
                <td>
                    <div class="section-title">Customer Signature</div>
                    <div class="box" style="height: 50px;"></div>
                </td>
                <td>
                    <div class="section-title">Mechanic / Service Advisor</div>
                    <div class="box" style="height: 50px;">
                        @if($job->creator)
                            {{ $job->creator->name }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Garage Code: {{ $job->garage->garage_code ?? '' }}
         Generated: {{ now()->format('d M Y H:i') }}
    </div>

</body>
</html>

