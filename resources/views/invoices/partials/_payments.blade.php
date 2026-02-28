            {{-- INTERNAL: PAYMENT UPDATE FORM (keep this out of _document) --}}
            <div class="mt-6 space-y-4">
                @if(($invoice->status ?? 'draft') === 'draft')
                    {{-- (already shown inside the document) --}}
                @elseif(($invoice->status ?? '') === 'cancelled')
                    {{-- (already shown inside the document) --}}
                @else

                    @php
                        // Invoice totals (your existing schema)
                        $paid  = (float)($invoice->paid_amount ?? 0);
                        $total = (float)($invoice->total_amount ?? 0);
                        $balance = max(0, $total - $paid);
                        $isPaid = (($invoice->payment_status ?? '') === 'paid') || (($invoice->status ?? '') === 'paid') || ($balance <= 0);

                        // Load payments history (new payments table)
                        $payments = $invoice->relationLoaded('payments')
                            ? $invoice->payments
                            : ($invoice->payments()->latest()->get());

                        // STK endpoint (adjust if yours differs)
                        $stkUrl = url("/api/invoices/{$invoice->id}/pay/stk");

                        $suggestAmount = number_format($balance, 2, '.', '');
                    @endphp

                    {{-- ✅ Payments History + STK Push --}}
                    <div class="bg-white shadow-sm sm:rounded-lg p-5"
                         x-data="{
                            open:false,
                            loading:false,
                            phone:'',
                            amount: '{{ $suggestAmount }}',
                            async sendStk(){
                                if(!this.phone){ alert('Enter phone number'); return; }
                                if(!this.amount || Number(this.amount) <= 0){ alert('Enter amount'); return; }

                                this.loading = true;
                                try {
                                    const res = await fetch(@js($stkUrl), {
                                        method: 'POST',
                                        credentials: 'same-origin',
                                        headers: {
                                            'Accept':'application/json',
                                            'Content-Type':'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'X-Requested-With':'XMLHttpRequest',
                                        },
                                        body: JSON.stringify({ phone: this.phone, amount: this.amount })
                                    });

                                    const data = await res.json().catch(()=> ({}));

                                    if(!res.ok){
                                        alert(data.message || 'STK request failed');
                                        return;
                                    }

                                    alert('✅ STK sent. Customer should enter PIN on phone.');
                                    this.open = false;
                                    window.location.reload();
                                } catch(e){
                                    alert('STK error. Check network / endpoint.');
                                } finally {
                                    this.loading = false;
                                }
                            }
                         }">

                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Payments</div>
                                <div class="text-xs text-gray-500">M-PESA payments & history for this invoice.</div>
                            </div>

                            <div class="text-right">
                                <div class="text-xs text-gray-500">Balance</div>
                                <div class="text-sm font-semibold text-gray-900">KES {{ number_format($balance, 2) }}</div>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-col sm:flex-row sm:items-center gap-2">
                            @if(!$isPaid && $balance > 0)
                                <button type="button"
                                        @click="open=true"
                                        class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-md text-xs font-semibold">
                                    📲 Pay via M-PESA (STK Push)
                                </button>
                            @endif

                            @if($payments->count())
                                <span class="text-xs text-gray-500">
                                    {{ $payments->count() }} payment{{ $payments->count()===1?'':'s' }} recorded.
                                </span>
                            @endif
                        </div>

                        <div class="mt-4 overflow-x-auto">
                            @if($payments->count())
                                <table class="min-w-full text-sm">
                                    <thead>
                                    <tr class="text-left text-xs text-gray-500 border-b">
                                        <th class="py-2 pr-3">Date</th>
                                        <th class="py-2 pr-3">Method</th>
                                        <th class="py-2 pr-3">Phone</th>
                                        <th class="py-2 pr-3">Amount</th>
                                        <th class="py-2 pr-3">Status</th>
                                        <th class="py-2 pr-3">Receipt</th>
                                    </tr>
                                    </thead>
                                        <tbody class="divide-y">
                                        @foreach($payments as $p)

                                            @php
                                                $st = $p->status ?? 'pending';

                                                // ✅ Safe status badge class (no nested ternary = no parse error)
                                                $badgeClass = match ($st) {
                                                    'paid'    => 'bg-green-100 text-green-800',
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'failed'  => 'bg-red-100 text-red-800',
                                                    default   => 'bg-slate-100 text-slate-800',
                                                };
                                            @endphp

                                            <tr>
                                                {{-- Date --}}
                                                <td class="py-2 pr-3 text-xs text-gray-700">
                                                    {{ optional($p->created_at)->format('d M Y H:i') ?: '—' }}
                                                </td>

                                                {{-- Provider --}}
                                                <td class="py-2 pr-3 text-xs text-gray-700">
                                                    {{ strtoupper($p->provider ?? 'mpesa') }}
                                                </td>

                                                {{-- Phone --}}
                                                <td class="py-2 pr-3 text-xs text-gray-700">
                                                    {{ $p->phone ?? '—' }}
                                                </td>

                                                {{-- Amount --}}
                                                <td class="py-2 pr-3 text-xs text-gray-900 font-semibold">
                                                    KES {{ number_format((float) ($p->amount ?? 0), 2) }}
                                                </td>

                                                {{-- Status --}}
                                                <td class="py-2 pr-3">
                                                    <span class="px-2 py-1 rounded text-xs font-semibold {{ $badgeClass }}">
                                                        {{ ucfirst($st) }}
                                                    </span>
                                                </td>
                                            </tr>

                                        @endforeach
                                        </tbody>

                                </table>
                            @else
                                <div class="text-xs text-gray-500 mt-2">No payments recorded yet.</div>
                            @endif
                        </div>

                        {{-- STK Modal --}}
                        <div x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center" style="display:none;">
                            <div class="absolute inset-0 bg-black/40" @click="open=false"></div>

                            <div class="relative bg-white w-full max-w-md rounded-lg shadow-lg p-5">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">Send STK Push</div>
                                        <div class="text-xs text-gray-500">Customer will enter PIN on their phone.</div>
                                    </div>
                                    <button type="button" @click="open=false" class="text-gray-500 hover:text-gray-800">✕</button>
                                </div>

                                <div class="mt-4 space-y-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Phone (07xx… / 2547xx…)</label>
                                        <input type="text" x-model="phone"
                                               class="w-full border-gray-300 rounded-md shadow-sm"
                                               placeholder="e.g. 0712345678">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Amount (KES)</label>
                                        <input type="number" step="1" min="1" x-model="amount"
                                               class="w-full border-gray-300 rounded-md shadow-sm">
                                        <div class="text-[11px] text-gray-500 mt-1">
                                            Suggested: Balance KES {{ number_format($balance, 2) }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-end gap-2">
                                    <button type="button" @click="open=false"
                                            class="px-4 py-2 bg-white border border-gray-200 rounded-md text-xs font-semibold">
                                        Cancel
                                    </button>

                                    <button type="button" @click="sendStk()"
                                            :disabled="loading"
                                            class="px-4 py-2 bg-green-600 text-white rounded-md text-xs font-semibold">
                                        <span x-show="!loading">Send STK</span>
                                        <span x-show="loading">Sending…</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ✅ Your existing manual "Record Payment" form (unchanged) --}}
                    <form method="POST" action="{{ route('invoices.updatePayment', $invoice) }}" class="text-sm">
                        @csrf

                        @php
                            $paidAtValue = old('paid_at');
                            if (!$paidAtValue) {
                                $paidAtValue = ($invoice->paid_at ?? null)
                                    ? \Carbon\Carbon::parse($invoice->paid_at)->format('Y-m-d')
                                    : now()->format('Y-m-d');
                            }

                            $method = old('payment_method', $invoice->payment_method ?? '');
                        @endphp

                        <div class="rounded-lg border border-gray-200 bg-gray-50/40 p-4">
                            <div class="flex items-start justify-between gap-4 mb-3">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">Record Payment</div>
                                    <div class="text-xs text-gray-500">Save payment details to enable receipt download.</div>
                                </div>

                                <div class="text-right">
                                    <div class="text-xs text-gray-500">Balance</div>
                                    <div class="text-sm font-semibold text-gray-900">KES {{ number_format($balance, 2) }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
                                {{-- Amount --}}
                                <div class="lg:col-span-3">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Amount Paid (KES)</label>
                                    <input type="number" step="0.01" min="0" name="paid_amount"
                                           value="{{ old('paid_amount', $invoice->paid_amount ?? 0) }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm">
                                    @error('paid_amount')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                                </div>

                                {{-- Method --}}
                                <div class="lg:col-span-3">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Payment Method</label>
                                    <select name="payment_method" class="w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="" {{ $method==='' ? 'selected' : '' }}>Select method</option>
                                        <option value="cash" {{ $method==='cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="mpesa_paybill" {{ $method==='mpesa_paybill' ? 'selected' : '' }}>M-PESA PayBill</option>
                                        <option value="mpesa_till" {{ $method==='mpesa_till' ? 'selected' : '' }}>M-PESA Till</option>
                                        <option value="bank_transfer" {{ $method==='bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                        <option value="card" {{ $method==='card' ? 'selected' : '' }}>Card</option>
                                        <option value="other" {{ $method==='other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('payment_method')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                                </div>

                                {{-- Reference --}}
                                <div class="lg:col-span-4">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Reference (optional)</label>
                                    <input type="text" name="payment_reference"
                                           value="{{ old('payment_reference', $invoice->payment_reference ?? '') }}"
                                           placeholder="e.g. MPESA code / Bank ref"
                                           class="w-full border-gray-300 rounded-md shadow-sm">
                                    @error('payment_reference')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                                </div>

                                {{-- Date --}}
                                <div class="lg:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Payment Date</label>
                                    <input type="date" name="paid_at" value="{{ $paidAtValue }}"
                                           class="w-full border-gray-300 rounded-md shadow-sm">
                                    @error('paid_at')<div class="text-xs text-red-600 mt-1">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mt-4">
                                <div class="flex items-center gap-2">
                                    <button type="submit"
                                            class="px-4 py-2 bg-indigo-600 text-white rounded-md text-xs font-semibold">
                                        Record Payment
                                    </button>

                                    @if(((float)($invoice->paid_amount ?? 0)) > 0)
                                        <a href="{{ route('invoices.receipt-pdf', $invoice) }}"
                                           class="px-4 py-2 bg-gray-900 text-white rounded-md text-xs font-semibold">
                                            Download Receipt
                                        </a>
                                    @endif
                                </div>

                                <div class="text-xs text-gray-500">
                                    Tip: Amount updates status automatically (unpaid / partial / paid).
                                </div>
                            </div>

                            @error('payment')
                                <div class="text-xs text-red-600 mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </form>
                @endif
            </div>