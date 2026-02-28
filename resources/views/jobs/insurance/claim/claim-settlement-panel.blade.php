<div class="mt-6 p-6 bg-gray-50 rounded-lg border">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold">Insurance Claim Settlement</h3>
        <span class="text-sm px-2 py-1 rounded bg-yellow-100 text-yellow-700">
            Not Submitted
        </span>
    </div>

    <p class="text-sm text-gray-600 mt-2">
        This invoice is payable by the insurer, not the customer.
        Generate and submit the claim pack to request settlement.
    </p>

    <div class="mt-4">
        <form method="POST" action="{{ route('jobs.insurance.claim.generate', $job) }}">
            @csrf

            <button type="button"
                    class="px-4 py-2 bg-black text-white rounded hover:bg-gray-800"
                    onclick="this.disabled=true; this.closest('form').submit();">
                {{ $hasPack ? 'Regenerate (New Version)' : 'Generate Claim Pack' }}
            </button>
        </form>
    </div>

    <div class="mt-4 text-sm text-gray-500">
        Invoice Amount: <span class="font-medium">
            {{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency }}
        </span>
    </div>
</div>