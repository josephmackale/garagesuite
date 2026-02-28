<x-guest-layout>
    <div class="max-w-4xl mx-auto py-10 px-4">
        <div class="mb-4">
            <h1 class="text-xl font-bold text-gray-900">Insurance Approval Pack</h1>
            <p class="text-sm text-gray-500">
                Pack #{{ $pack->id }} · Version {{ $pack->version ?? 1 }} · Status: {{ strtoupper($pack->status ?? 'draft') }}
            </p>
        </div>

        <div class="bg-white border rounded-xl p-4 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <div><span class="text-gray-500">Case:</span> <b>{{ $job->job_number ?: ('Job #' . $job->job_id) }}</b></div>
                    <div><span class="text-gray-500">Customer:</span> <b>{{ $job->customer_name ?: 'N/A' }}</b></div>
                    <div><span class="text-gray-500">Vehicle:</span> <b>{{ trim(($job->vehicle_make ?? '').' '.($job->vehicle_model ?? '')) }}</b> ({{ $job->vehicle_year ?: '—' }})</div>
                    <div><span class="text-gray-500">Plate:</span> <b>{{ $job->vehicle_reg ?: '—' }}</b></div>
                </div>
                <div>
                    <div><span class="text-gray-500">Currency:</span> <b>{{ $pack->currency ?? 'KES' }}</b></div>
                    <div><span class="text-gray-500">Total:</span> <b>{{ number_format((float)$pack->total_amount, 2) }}</b></div>
                    <div><span class="text-gray-500">Generated:</span> <b>{{ $pack->generated_at }}</b></div>
                    <div><span class="text-gray-500">Submitted:</span> <b>{{ $pack->submitted_at ?: '—' }}</b></div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                {{-- Signed download: reuse your existing pdf route but we need auth for that route,
                     so we generate a signed download link too in the next step. For now show message. --}}
                <span class="text-xs text-gray-500">Download button comes in Step 4.</span>
            </div>
        </div>

        @php
            $signedPdfUrl = URL::temporarySignedRoute(
                'insurance.approval-packs.pdf.share',
                now()->addDays(14),
                ['pack' => $pack->id]
            );
        @endphp

        <div class="mb-4">
            <a href="{{ $signedPdfUrl }}"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg"
            target="_blank">
                Download PDF
            </a>
        </div>

        <div class="mt-6 bg-white border rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b">
                <h2 class="font-semibold text-gray-900">Quotation Snapshot</h2>
            </div>
            <div class="p-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-gray-600">
                        <tr class="border-b">
                            <th class="py-2 pr-3">Type</th>
                            <th class="py-2 pr-3">Description</th>
                            <th class="py-2 pr-3 text-right">Qty</th>
                            <th class="py-2 pr-3 text-right">Unit</th>
                            <th class="py-2 pr-3 text-right">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $it)
                            <tr class="border-b">
                                <td class="py-2 pr-3">{{ $it->line_type }}</td>
                                <td class="py-2 pr-3">
                                    <div class="font-semibold">{{ $it->name }}</div>
                                    @if(!empty($it->description))
                                        <div class="text-xs text-gray-500">{{ $it->description }}</div>
                                    @endif
                                </td>
                                <td class="py-2 pr-3 text-right">{{ number_format((float)$it->qty, 2) }}</td>
                                <td class="py-2 pr-3 text-right">{{ number_format((float)$it->unit_price, 2) }}</td>
                                <td class="py-2 pr-3 text-right">{{ number_format((float)$it->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="4" class="py-2 pr-3 text-right font-bold">Total</td>
                            <td class="py-2 pr-3 text-right font-bold">{{ number_format((float)$pack->total_amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 bg-white border rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b">
                <h2 class="font-semibold text-gray-900">Inspection Photos Snapshot</h2>
            </div>
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($photos as $p)
                    @php
                        $url = asset('storage/' . ltrim($p->storage_path ?? '', '/'));
                    @endphp
                    <div class="border rounded-lg overflow-hidden">
                        <img src="{{ $url }}" class="w-full h-56 object-cover" alt="Inspection photo">
                        <div class="p-2 text-xs text-gray-500">
                            {{ $p->category }} · {{ $p->label }}
                        </div>
                    </div>
                @endforeach

                @if($photos->isEmpty())
                    <div class="text-sm text-gray-500">No photos captured in this pack.</div>
                @endif
            </div>
        </div>
    </div>
</x-guest-layout>
