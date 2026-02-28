@if (session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
@endif
