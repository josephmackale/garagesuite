{{-- resources/views/billing/index.blade.php --}}
<x-app-layout>
    @php
        $user   = auth()->user();
        $garage = $user?->garage;

        $now = now();

        $trialEndsAt = $garage?->trial_ends_at;
        $subEndsAt   = $garage?->subscription_expires_at;

        $isSuspended   = ($garage?->status === 'suspended');
        $isSubActive   = $subEndsAt && $now->lt($subEndsAt);
        $isTrialActive = $trialEndsAt && $now->lt($trialEndsAt);

        if ($isSuspended) {
            $state = 'suspended';
        } elseif ($isSubActive) {
            $state = 'active';
        } elseif ($isTrialActive) {
            $state = 'trial_active';
        } elseif ($subEndsAt) {
            $state = 'subscription_expired';
        } elseif ($trialEndsAt) {
            $state = 'trial_expired';
        } else {
            $state = 'no_subscription';
        }

        // Header pill (short)
        $pillText = match ($state) {
            'active'               => $subEndsAt ? 'Active • valid until '.$subEndsAt->format('d M Y') : 'Active',
            'trial_active'         => $trialEndsAt ? 'Trial • ends '.$trialEndsAt->format('d M Y') : 'Trial active',
            'subscription_expired' => 'Subscription expired • renew to continue',
            'trial_expired'        => 'Trial ended • subscription required',
            'no_subscription'      => 'Subscription required',
            'suspended'            => 'Suspended • contact support',
            default                => 'Subscription',
        };

        $pillStyle = match ($state) {
            'active'       => 'border-green-200 bg-green-50 text-green-800',
            'trial_active' => 'border-blue-200 bg-blue-50 text-blue-800',
            default        => 'border-red-200 bg-red-50 text-red-800',
        };

        // Notifications (compact, replaces the big status card)
        $noticeTitle = match ($state) {
            'subscription_expired' => 'Your subscription has expired',
            'trial_expired'        => 'Your trial has ended',
            'no_subscription'      => 'Subscription required',
            'suspended'            => 'Account suspended',
            default                => null,
        };

        $noticeBody = match ($state) {
            'subscription_expired' => $subEndsAt
                ? 'Expired on '.$subEndsAt->format('d M Y, H:i').'. Renew now to restore full access.'
                : 'Renew now to restore full access.',
            'trial_expired' => $trialEndsAt
                ? 'Ended on '.$trialEndsAt->format('d M Y, H:i').'. Subscribe to continue using the system.'
                : 'Subscribe to continue using the system.',
            'no_subscription' => 'A subscription is required to continue using the system.',
            'suspended' => 'This garage has been suspended by the administrator. Payments and access are blocked until reactivated.',
            default => null,
        };

        $noticeClass = match ($state) {
            'suspended' => 'border-gray-200 bg-gray-50 text-gray-800',
            default     => 'border-amber-200 bg-amber-50 text-amber-900',
        };

        $prefillPhone = $garage?->phone ?: $user?->phone;
        $defaultAmount = 500; // adjust
        $defaultReference = 'GARAGESUITE-'.$garage?->id;
    @endphp

    {{-- ===== Header area (title + pill + actions) ===== --}}
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            {{-- Move Back + Support into header row --}}
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl  text-gray-800 hover:bg-gray-50">
                    <x-lucide-arrow-left class="w-4 h-4" />
                    Back to dashboard
                </a>

                <div class="inline-flex items-center gap-2 px-4 py-2  text-gray-700 text-sm">
                    <span class="font-semibold">Support:</span>
                    @if($garage?->phone)
                        <span class="inline-flex items-center gap-1">📞 {{ $garage->phone }}</span>
                    @endif
                    @if($garage?->email)
                        <span class="inline-flex items-center gap-1">✉️ {{ $garage->email }}</span>
                    @endif
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-5">

            {{-- Middleware error --}}
            @if(session('error'))
                <div class="rounded-2lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            {{-- ✅ Notifications (trial/sub expiry moved here) --}}
            @if($noticeTitle)
                <div class="rounded-2xl border p-4 {{ $noticeClass }}">
                    <div class="font-semibold">{{ $noticeTitle }}</div>
                    <div class="text-sm mt-1">{{ $noticeBody }}</div>

                    @if(in_array($state, ['subscription_expired','trial_expired','no_subscription'], true))
                        <div class="text-sm mt-2">
                            <span class="font-semibold">Read-only mode</span> is active until you renew.
                        </div>
                    @endif
                </div>
            @endif

            {{-- ✅ ONE card only: M-PESA STK Prompt (Disabled / Coming Soon) --}}
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden opacity-80">
                <div class="p-6 sm:p-8">

                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-semibold text-gray-900">Lipa na M-PESA</h3>

                                {{-- Coming soon badge --}}
                                <span class="inline-flex items-center rounded-full bg-gray-100 border border-gray-300 px-2.5 py-0.5 text-xs font-semibold text-gray-600">
                                    Coming Soon
                                </span>
                            </div>

                            <p class="text-sm text-gray-600 mt-1">
                                M-PESA integration is currently being activated.
                            </p>
                        </div>

                        <div class="text-sm text-gray-500 text-right">
                            <div class="font-medium text-gray-700">Customer Care</div>
                            <div>0722221290 / 0111056560</div>
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-gray-200 bg-gray-50 p-4 sm:p-5">
                        <div class="flex items-start gap-3">

                            {{-- Disabled radio --}}
                            <input type="radio" checked disabled class="mt-1 opacity-50" />

                            <div class="flex-1">

                                <div class="font-semibold text-gray-500">
                                    Instant M-PESA Prompt (STK Push)
                                </div>

                                <div class="text-sm text-gray-400">
                                    Send STK Push prompt to M-PESA phone
                                </div>

                                <ul class="text-sm text-gray-400 list-disc pl-5 space-y-1 mt-3">
                                    <li>Enter your phone number and click Initiate Payment.</li>
                                    <li>Confirm the prompt and enter your M-PESA PIN.</li>
                                    <li>After successful payment, your subscription updates automatically.</li>
                                </ul>

                                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">

                                    {{-- Phone --}}
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-400 mb-1">
                                            Cellphone Number
                                        </label>

                                        <input type="text"
                                            disabled
                                            placeholder="2547XXXXXXXX"
                                            value="{{ $prefillPhone }}"
                                            class="w-full rounded-lg bg-gray-100 border-gray-300 text-gray-400 cursor-not-allowed">

                                        <p class="text-xs text-gray-400 mt-1">
                                            Format: 2547XXXXXXXX
                                        </p>
                                    </div>

                                    {{-- Amount --}}
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-400 mb-1">
                                            Amount (KES)
                                        </label>

                                        <input type="number"
                                            disabled
                                            value="{{ $defaultAmount }}"
                                            class="w-full rounded-lg bg-gray-100 border-gray-300 text-gray-400 cursor-not-allowed">
                                    </div>

                                </div>

                                <div class="mt-4 flex flex-wrap items-center gap-3">

                                    {{-- Disabled button --}}
                                    <button type="button"
                                            disabled
                                            class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl text-sm font-semibold
                                                bg-gray-300 text-gray-600 cursor-not-allowed">

                                        <span>🚧</span>
                                        <span>Activation in Progress</span>
                                    </button>

                                    <span class="text-sm text-gray-400">
                                        Payments will be enabled soon.
                                    </span>

                                </div>

                                {{-- Info note --}}
                                <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
                                    We are currently finalizing on this, you will be able to pay directly from here.
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>


        </div>
    </div>

    <script>
        (function () {
            const btn = document.getElementById('btn_initiate');
            if (!btn) return;

            const phoneEl  = document.getElementById('mpesa_phone');
            const amountEl = document.getElementById('mpesa_amount');
            const refEl    = document.getElementById('mpesa_reference');
            const resultEl = document.getElementById('stk_result');

            const btnText = document.getElementById('btn_text');
            const btnIcon = document.getElementById('btn_icon');

            const apiUrl = "{{ url('/api/mpesa/stk/initiate') }}";

            function showResult(type, html) {
                resultEl.classList.remove('hidden');
                resultEl.classList.remove('border-green-200','bg-green-50','text-green-800');
                resultEl.classList.remove('border-red-200','bg-red-50','text-red-800');
                resultEl.classList.remove('border-amber-200','bg-amber-50','text-amber-900');

                if (type === 'success') {
                    resultEl.classList.add('border-green-200','bg-green-50','text-green-800');
                } else if (type === 'warning') {
                    resultEl.classList.add('border-amber-200','bg-amber-50','text-amber-900');
                } else {
                    resultEl.classList.add('border-red-200','bg-red-50','text-red-800');
                }
                resultEl.innerHTML = html;
            }

            function setLoading(isLoading) {
                btn.disabled = isLoading;
                if (isLoading) {
                    btn.classList.add('opacity-80');
                    btnText.textContent = 'Sending STK Prompt...';
                    btnIcon.textContent = '⏳';
                } else {
                    btn.classList.remove('opacity-80');
                    btnText.textContent = 'Initiate Payment';
                    btnIcon.textContent = '➡️';
                }
            }

            function normalizePhone(raw) {
                if (!raw) return '';
                let p = String(raw).trim().replace(/\s+/g,'');
                if (p.startsWith('+')) p = p.slice(1);
                if (p.startsWith('07') && p.length === 10) p = '254' + p.slice(1);
                if (p.length === 9 && p.startsWith('7')) p = '254' + p;
                return p;
            }

            btn.addEventListener('click', async () => {
                const phone = normalizePhone(phoneEl.value);
                const amount = Number(amountEl.value || 0);
                const reference = (refEl.value || '').trim();

                if (!phone || !/^2547\d{8}$/.test(phone)) {
                    showResult('error', 'Please enter a valid phone number in the format <b>2547XXXXXXXX</b>.');
                    return;
                }
                if (!amount || amount < 1) {
                    showResult('error', 'Please enter a valid amount (KES).');
                    return;
                }
                if (!reference) {
                    showResult('error', 'Please enter a reference (e.g., GARAGESUITE-1).');
                    return;
                }

                setLoading(true);
                showResult('warning', 'Preparing request...');

                try {
                    const res = await fetch(apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            garage_id: {{ (int) ($garage?->id ?? 0) }},
                            amount: amount,
                            phone: phone,
                            reference: reference
                        })
                    });

                    let data = null;
                    const text = await res.text();
                    try { data = text ? JSON.parse(text) : null; } catch (e) { data = { raw: text }; }

                    if (!res.ok) {
                        const msg = (data && (data.message || data.error)) ? (data.message || data.error) : 'Request failed.';
                        showResult('error', `<b>Payment request failed.</b><br>${msg}`);
                        return;
                    }

                    const desc = data?.ResponseDescription || data?.message || 'STK prompt sent successfully.';
                    const checkout = data?.CheckoutRequestID ? `<div><b>CheckoutRequestID:</b> ${data.CheckoutRequestID}</div>` : '';
                    const merchant = data?.MerchantRequestID ? `<div><b>MerchantRequestID:</b> ${data.MerchantRequestID}</div>` : '';

                    showResult('success', `
                        <div class="font-semibold mb-1">STK Prompt Sent ✅</div>
                        <div>${desc}</div>
                        ${merchant}
                        ${checkout}
                        <div class="mt-2">Check your phone (${phone}) and enter your M-PESA PIN.</div>
                    `);

                } catch (err) {
                    showResult('error', 'Network/server error. Please try again, or check Laravel logs.');
                } finally {
                    setLoading(false);
                }
            });
        })();
    </script>
</x-app-layout>
