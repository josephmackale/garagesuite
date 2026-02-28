<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">

  @php
    $title = ($garage->name ?? 'Invoice') . ' - Invoice ' . ($invoice->invoice_number ?? $invoice->id);
    $amount = number_format((float)($invoice->total_amount ?? 0), 2);
    $desc = "Invoice {$invoice->invoice_number} of KES {$amount}";
  @endphp

  <!-- WhatsApp Preview -->
  <meta property="og:title" content="{{ $title }}">
  <meta property="og:site_name" content="{{ $garage->name }}">
  <meta property="og:description" content="{{ $desc }}">
  <meta property="og:image" content="{{ $logoUrl }}">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ url()->current() }}">

  <!-- Redirect to PDF -->
  <meta http-equiv="refresh" content="0;url={{ $pdfUrl }}">

  <title>{{ $title }}</title>
</head>
<body></body>
</html>
