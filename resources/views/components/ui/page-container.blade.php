@props([
    'mt' => 'mt-5',
])

<div {{ $attributes->merge([
        'class' => "max-w-6xl mx-auto px-3 sm:px-4 lg:px-6 {$mt}"
    ]) }}>
    {{ $slot }}
</div>
