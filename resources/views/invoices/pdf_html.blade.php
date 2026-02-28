<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-white">
    <div class="p-6">
        @include('invoices._document', ['invoice' => $invoice])
    </div>
</body>
</html>
