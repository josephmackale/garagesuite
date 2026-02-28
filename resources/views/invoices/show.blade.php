{{-- resources/views/invoices/show.blade.php --}}

<x-app-layout>
    {{-- HEADER --}}
    <x-slot name="header">
        <div class="flex items-center justify-between w-full">
            <h2 class="font-semibold text-lg sm:text-xl text-gray-800 leading-tight">
                Invoice {{ $invoice->invoice_number }}
            </h2>

            <div class="flex items-center gap-1 sm:gap-2">
                @php
                    $isInsurance = (($invoice->job->payer_type ?? '') === 'insurance');
                @endphp
                @if(($invoice->status ?? 'draft') === 'draft')
                    <div x-data="{ open: false }" class="relative flex items-center gap-2">
                        {{-- Issue Invoice --}}
                        <form method="POST" action="{{ route('invoices.issue', $invoice) }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center justify-center p-2 rounded-md text-white bg-indigo-600 border border-indigo-600"
                                    title="Send invoice">
                                <x-lucide-check class="w-4 h-4" />
                                <span class="hidden sm:inline text-xs ml-1">Send</span>
                            </button>
                        </form>

                        {{-- Issue via... dropdown --}}
                        <button type="button"
                                @click="open = !open"
                                class="inline-flex items-center justify-center p-2 rounded-md text-gray-700 bg-white border border-gray-200"
                                title="Send via">
                            <x-lucide-send class="w-4 h-4" />
                            <span class="hidden sm:inline text-xs ml-1">Send via</span>
                            <x-lucide-chevron-down class="w-4 h-4 ml-1" />
                        </button>

                        <div x-show="open"
                             x-transition
                             @click.away="open=false"
                             class="absolute right-0 top-full mt-2 w-52 bg-white border border-gray-200 rounded-md shadow-lg z-50 overflow-hidden">

                            {{-- WhatsApp --}}
                            <form method="POST" action="{{ route('invoices.issue', $invoice) }}">
                                @csrf
                                <input type="hidden" name="action" value="whatsapp">
                                <button type="submit"
                                        class="w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-50">
                                    <x-lucide-message-circle class="w-4 h-4 text-green-600" />
                                    <span>Send via WhatsApp</span>
                                </button>
                            </form>

                            {{-- Email --}}
                            <form method="POST" action="{{ route('invoices.issue', $invoice) }}">
                                @csrf
                                <input type="hidden" name="action" value="email">
                                <button type="submit"
                                        class="w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-50">
                                    <x-lucide-mail class="w-4 h-4 text-blue-600" />
                                    <span>Send via Email</span>
                                </button>
                            </form>

                            <div class="border-t"></div>

                            {{-- PDF --}}
                            <div class="border-t"></div>

                            {{-- PDF (A5 / A4 options) --}}
                            <div class="px-2 py-2">
                                <div class="text-[11px] font-semibold text-gray-500 px-2 mb-2">Download PDF</div>

                                <a href="{{ route('invoices.pdf', $invoice) }}?format=a5"
                                class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm rounded hover:bg-gray-50">
                                    <span class="flex items-center gap-2">
                                        <x-lucide-download class="w-4 h-4 text-gray-700" />
                                        <span>A5 Book (Portrait)</span>
                                    </span>
                                    <span class="text-[11px] font-semibold text-gray-500">A5</span>
                                </a>

                                <a href="{{ route('invoices.pdf', $invoice) }}?format=a4"
                                class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm rounded hover:bg-gray-50">
                                    <span class="flex items-center gap-2">
                                        <x-lucide-download class="w-4 h-4 text-gray-700" />
                                        <span>A4 Copy (Portrait)</span>
                                    </span>
                                    <span class="text-[11px] font-semibold text-gray-500">A4</span>
                                </a>
                            </div>

                        </div>
                    </div>
                    @else
                        {{-- Sent/Paid: Share dropdown --}}
                        <div x-data="{ open:false }" class="relative flex items-center gap-2">
                            <button type="button"
                                    @click="open=!open"
                                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-700 bg-white border border-gray-200"
                                    title="Share">
                                <x-lucide-share-2 class="w-4 h-4" />
                                <span class="hidden sm:inline text-xs ml-1">Share</span>
                                <x-lucide-chevron-down class="w-4 h-4 ml-1" />
                            </button>

                            <div x-show="open"
                                x-transition
                                @click.away="open=false"
                                class="absolute right-0 top-full mt-2 w-56 bg-white border border-gray-200 rounded-md shadow-lg z-50 overflow-hidden">

                                {{-- Email --}}
                                <form method="POST" action="{{ route('invoices.issue', $invoice) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="email">
                                    <button type="submit"
                                            class="w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-50">
                                        <x-lucide-mail class="w-4 h-4 text-blue-600" />
                                        <span>Email</span>
                                    </button>
                                </form>

                                {{-- WhatsApp --}}
                                <form method="POST" action="{{ route('invoices.issue', $invoice) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="whatsapp">
                                    <button type="submit"
                                            class="w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-50">
                                        <x-lucide-message-circle class="w-4 h-4 text-green-600" />
                                        <span>WhatsApp</span>
                                    </button>
                                </form>

                                <div class="border-t"></div>

                                {{-- PDFs --}}
                                <a href="{{ route('invoices.pdf', $invoice) }}?format=a5"
                                class="w-full flex items-center justify-between gap-2 px-4 py-2 text-sm hover:bg-gray-50">
                                    <span class="flex items-center gap-2">
                                        <x-lucide-download class="w-4 h-4 text-gray-700" />
                                        <span>PDF (A5)</span>
                                    </span>
                                    <span class="text-[11px] font-semibold text-gray-500">A5</span>
                                </a>

                                <a href="{{ route('invoices.pdf', $invoice) }}?format=a4"
                                class="w-full flex items-center justify-between gap-2 px-4 py-2 text-sm hover:bg-gray-50">
                                    <span class="flex items-center gap-2">
                                        <x-lucide-download class="w-4 h-4 text-gray-700" />
                                        <span>PDF (A4)</span>
                                    </span>
                                    <span class="text-[11px] font-semibold text-gray-500">A4</span>
                                </a>
                            </div>
                        </div>

                        {{-- Insurance: Proceed to Claim --}}
                        @if($isInsurance)
                            <a href="{{ route('jobs.insurance.claim.show', $invoice->job) }}"
                            class="inline-flex items-center justify-center p-2 rounded-md text-white bg-indigo-600 border border-indigo-600"
                            title="Proceed to Claim">
                                <x-lucide-file-text class="w-4 h-4" />
                                <span class="hidden sm:inline text-xs ml-1">Claim</span>
                            </a>
                        @endif
                    @endif
                    @if($isInsurance)
                        {{-- Insurance invoices should return to insurance workflow, not generic job --}}
                        <a href="{{ route('jobs.insurance.show', $invoice->job) }}"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-700 bg-white border border-gray-200"
                        title="Back to Insurance Workflow">
                            <x-lucide-shield class="w-4 h-4" />
                            <span class="hidden sm:inline text-xs ml-1">Insurance</span>
                        </a>
                    @else
                        {{-- Non-insurance invoices: back to job --}}
                        <a href="{{ route('jobs.show', $invoice->job) }}"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-700 bg-white border border-gray-200"
                        title="Back to job">
                            <x-lucide-arrow-left class="w-4 h-4" />
                            <span class="hidden sm:inline text-xs ml-1">Back</span>
                        </a>
                    @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6">


        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

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

            @if (session('error'))
                <div class="mb-4 text-sm text-red-700 bg-red-100 px-4 py-2 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 text-sm text-red-700 bg-red-100 px-4 py-2 rounded">
                    <div class="font-semibold mb-1">Please fix the following:</div>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Invoice Paper (shared / garage-aware) --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                @php
                    // ✅ If template exists but does NOT support items, fallback to default view
                    // Marker strategy: your DB templates should include at least one of these.
                    $tpl = $invoicePreviewHtml ?? '';
                    $templateSupportsItems = false;

                    if (!empty($tpl)) {
                        $hay = strtolower($tpl);
                        // Accept any of these markers/keywords as "supports items"
                        $templateSupportsItems =
                            str_contains($hay, '{{items}}') ||
                            str_contains($hay, '{{items_table}}') ||
                            str_contains($hay, 'data-items') ||
                            str_contains($hay, 'invoice->items') ||
                            str_contains($hay, '@foreach') ||
                            str_contains($hay, 'particulars') ||  // your blue stationery templates often have this
                            str_contains($hay, 'qty');
                    }
                @endphp

                @if(!empty($tpl) && $templateSupportsItems)
                    {!! $tpl !!}
                @else
                    {{-- ✅ Fallback always prints items correctly --}}
                    @include('invoices._document', ['invoice' => $invoice])
                @endif
            </div>



            @php
                $status = ($invoice->status ?? 'draft');
                $payerType = $invoice->job->payer_type ?? null;

                // ✅ Base rule
                $showPayments = !in_array($status, ['draft', 'cancelled'], true);

                // ✅ Insurance rule: hide payments unless you explicitly allow it
                // (Choose the statuses you consider "payment stage")
                if ($payerType === 'insurance') {
                    $showPayments = in_array($status, ['paid', 'partial'], true);
                    // If you want to allow on "sent/issued" too, add them:
                    // $showPayments = in_array($status, ['sent','issued','partial','paid'], true);
                }
            @endphp

            @if($showPayments)
                @include('invoices.partials._payments', ['invoice' => $invoice])
            @endif

        </div>
    </div>
</x-app-layout>
