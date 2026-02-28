{{-- ============================================================
 | Individual Payer (Direct Customer Billing)
 | Used when payer_type = individual
 | No extra details required
 ============================================================ --}}

<input type="hidden" name="payer_type" value="individual">

{{-- Keep Customer Context --}}
@if(request('customer_id'))
    <input type="hidden" name="customer_id" value="{{ request('customer_id') }}">
@endif

{{-- Keep Vehicle Context --}}
@if(request('vehicle_id'))
    <input type="hidden" name="vehicle_id" value="{{ request('vehicle_id') }}">
@endif


<div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">

    <div class="flex items-start gap-3">

        {{-- Icon --}}
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold">
            👤
        </div>

        <div class="flex-1">

            <h4 class="text-sm font-semibold text-indigo-900">
                Individual Customer
            </h4>

            <p class="mt-1 text-sm text-indigo-700 leading-relaxed">
                This job will be billed directly to the customer.
                No company or insurance details are required.
            </p>

        </div>

    </div>

</div>


{{-- Mark payer as ready for Alpine gate --}}
<input
    type="hidden"
    name="payer_ready"
    value="1"
    x-init="$dispatch('payer-ready')"
/>
