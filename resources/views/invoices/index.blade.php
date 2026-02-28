<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Invoices
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('info'))
                <div class="mb-4 text-sm text-blue-700 bg-blue-100 px-4 py-2 rounded">
                    {{ session('info') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-gray-600">
                                <th class="pb-2">Invoice #</th>
                                <th class="pb-2">Date</th>
                                <th class="pb-2">Customer</th>
                                <th class="pb-2">Vehicle</th>
                                <th class="pb-2 text-right">Total</th>
                                <th class="pb-2 text-right">Status</th>
                                <th class="pb-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($invoices as $invoice)
                            <tr class="border-b last:border-0">
                                <td class="py-2">
                                    {{ $invoice->invoice_number }}
                                </td>
                                <td class="py-2">
                                    {{ \Illuminate\Support\Carbon::parse($invoice->issue_date)->format('d M Y') }}
                                </td>
                                <td class="py-2">
                                    {{ $invoice->customer?->name }}
                                </td>
                                <td class="py-2">
                                    @if($invoice->vehicle)
                                        {{ $invoice->vehicle->registration_number }}
                                        – {{ $invoice->vehicle->make }} {{ $invoice->vehicle->model }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-2 text-right">
                                    KES {{ number_format($invoice->total_amount, 2) }}
                                </td>
                                <td class="py-2 text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs
                                        @if($invoice->status === 'paid')
                                            bg-green-100 text-green-700
                                        @elseif($invoice->status === 'cancelled')
                                            bg-red-100 text-red-700
                                        @else
                                            bg-gray-100 text-gray-700
                                        @endif
                                    ">
                                        {{ ucfirst($invoice->status) }}
                                    </span>
                                </td>
                                <td class="py-2 text-right">
                                    <a href="{{ route('invoices.show', $invoice) }}"
                                       class="text-indigo-600 text-xs hover:underline">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-center text-gray-500">
                                    No invoices yet.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $invoices->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
