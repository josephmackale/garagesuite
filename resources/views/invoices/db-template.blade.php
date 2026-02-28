<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">

    {{-- Paper size control: ?format=a5 (default) or ?format=a4 --}}
    @php
        $format = $format ?? request()->query('format', 'a5');
        $format = in_array($format, ['a5','a4'], true) ? $format : 'a5';
    @endphp

    <style>
        @page { size: {{ $format === 'a4' ? 'A4' : 'A5' }} portrait; margin: 8mm; }
        html, body { margin:0; padding:0; background:#fff; }
        * { box-sizing: border-box; }
        .print-wrap { padding: 0; }
        /* Make A4 feel “filled” while keeping A5 book-perfect */
        .outer { width: {{ $format === 'a4' ? '190mm' : '132mm' }}; }
        .outer { margin: 0 auto; }
        .dalima-book{
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    </style>

    {{-- DB template CSS --}}
    @if(!empty($css))
        <style>{!! $css !!}</style>
    @endif
</head>

<body class="bg-white">
    <div class="print-wrap">
        {!! $html !!}
    </div>
</body>
</html>
