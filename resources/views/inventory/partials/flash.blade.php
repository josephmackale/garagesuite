{{-- inventory/partials/flash.blade.php --}}
@if (session('success'))
    <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
        {{ session('success') }}
    </div>
@endif
