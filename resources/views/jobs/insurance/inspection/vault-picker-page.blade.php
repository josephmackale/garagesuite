@php
    $content = view('jobs.insurance.inspection.vault-picker', get_defined_vars())->render();
    $slot = new \Illuminate\Support\HtmlString($content);
@endphp

@include('layouts.app', ['slot' => $slot])