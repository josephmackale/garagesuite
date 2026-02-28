<form method="POST" action="{{ route('jobs.store') }}" class="space-y-8" id="createJobForm">
    @csrf

    {{-- Keep customer context when launched from customer page --}}
    @if(!empty($customerId))
        <input type="hidden" name="customer_id" value="{{ $customerId }}">
    @endif

    @include('jobs._form', [
        'submitLabel' => 'Save Job',
        'selectedVehicleId' => $selectedVehicleId ?? null,
        'hideBasics' => $hideBasics ?? false,

    ])
</form>
