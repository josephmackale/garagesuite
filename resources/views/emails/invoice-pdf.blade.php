<!doctype html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <p>Hello,</p>
    <p>Please find attached your invoice <strong>{{ $invoice->invoice_number ?? ('INV-'.$invoice->id) }}</strong>.</p>
    <p>Thank you.</p>
</body>
</html>
