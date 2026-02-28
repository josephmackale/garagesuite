{{-- resources/views/settings/index.blade.php --}}
<x-app-layout>
    @php
        $tab    = request('tab', 'branding');
        $user   = auth()->user();
        $garage = $user?->garage;

        $tabLinkBase = 'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border transition';
        $tabActive   = 'bg-indigo-50 text-indigo-700 border-indigo-200';
        $tabIdle     = 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50';

        // Payment methods JSON (standardize on payment_methods)
        $payment = $garage?->payment_methods ?? [];

        $mpesaType    = old('payment_methods.mpesa.type', data_get($payment, 'mpesa.type', 'paybill'));
        $mpesaNumber  = old('payment_methods.mpesa.number', data_get($payment, 'mpesa.number', ''));
        $mpesaAccount = old('payment_methods.mpesa.account', data_get($payment, 'mpesa.account', 'INV-{invoice_number}'));

        $bankName      = old('payment_methods.bank.bank_name', data_get($payment, 'bank.bank_name', ''));
        $bankAccName   = old('payment_methods.bank.account_name', data_get($payment, 'bank.account_name', $garage?->name ?? ''));
        $bankAccNumber = old('payment_methods.bank.account_number', data_get($payment, 'bank.account_number', ''));
    @endphp

    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Branding & Documents</h1>
        </div>

        <a href="{{ route('settings.home') }}"
        class="inline-flex items-center mb-3 text-sm text-gray-600 hover:text-gray-900">
            ← Back to Settings
        </a>

        @if (session('success'))
            <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- TOP TABS --}}
        <div class="flex flex-wrap gap-2 mb-6">
           <a href="{{ route('settings.branding', ['tab' => 'branding']) }}"
               class="{{ $tabLinkBase }} {{ $tab === 'branding' ? $tabActive : $tabIdle }}">
                <x-lucide-palette class="w-4 h-4" />
                Branding
            </a>
        </div>

        {{-- CONTENT (FULL WIDTH) --}}
        @if($tab === 'branding')
            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm w-full">
                <div class="flex items-start justify-between gap-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Garage Logo</h2>
                        <p class="text-sm text-gray-500 mt-1">Shown on invoices, receipts, PDFs, and emails.</p>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 md:grid-cols-12 gap-6">
                    {{-- Preview --}}
                    <div class="md:col-span-5 min-w-0">
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                            <div class="text-xs text-gray-500 mb-3">Preview</div>
                              <div class="flex items-center justify-center h-40 rounded-xl bg-white border border-gray-200 overflow-hidden">
                                  @if($garage?->logo_path)
                                      <img
                                          src="{{ asset('storage/'.$garage->logo_path) }}"
                                          alt="Garage logo"
                                          class="max-h-full max-w-full object-contain p-4"
                                      >
                                  @else
                                      <div class="text-center px-6">
                                          <div class="mx-auto w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center mb-3">
                                              <x-lucide-image class="w-5 h-5 text-indigo-600" />
                                          </div>
                                          <div class="text-sm font-semibold text-gray-700">No logo uploaded</div>
                                          <div class="text-xs text-gray-500 mt-1">Upload a PNG for best results.</div>
                                      </div>
                                  @endif
                              </div>

                            <p class="mt-3 text-xs text-gray-500">
                                Recommended: PNG with transparent background. Max 2MB.
                            </p>
                        </div>
                    </div>

                    {{-- Upload / Remove --}}
                    <div class="md:col-span-7 min-w-0">
                        <div class="rounded-2xl border border-gray-200 p-5">
                            <div class="flex items-center gap-2">
                                <x-lucide-upload class="w-4 h-4 text-gray-600" />
                                <div class="font-semibold text-gray-900">Upload a new logo</div>
                            </div>

                            <form method="POST"
                                  action="{{ route('garage.logo.store') }}"
                                  enctype="multipart/form-data"
                                  class="mt-4 space-y-4">
                                @csrf

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Logo file</label>
                                    <input type="file"
                                           name="logo"
                                           accept="image/png,image/jpeg,image/webp"
                                           required
                                           class="block w-full text-sm text-gray-700
                                                  file:mr-4 file:py-2 file:px-4
                                                  file:rounded-lg file:border-0
                                                  file:text-sm file:font-semibold
                                                  file:bg-indigo-600 file:text-white
                                                  hover:file:bg-indigo-700
                                                  rounded-lg border border-gray-200 bg-white">
                                    <p class="mt-2 text-xs text-gray-500">
                                        Allowed: PNG/JPG/WebP. Prefer PNG.
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                                        <x-lucide-check class="w-4 h-4" />
                                        Save Logo
                                    </button>

                                    @if($garage?->logo_path)
                                        <button type="button"
                                                onclick="if(confirm('Remove your garage logo?')) document.getElementById('removeLogoForm').submit();"
                                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                            <x-lucide-trash-2 class="w-4 h-4" />
                                            Remove
                                        </button>
                                    @endif
                                </div>
                            </form>

                            @if($garage?->logo_path)
                                <form id="removeLogoForm" method="POST" action="{{ route('garage.logo.destroy') }}" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @endif
                        </div>

                        <div class="mt-6 rounded-2xl border border-dashed border-gray-200 p-5">
                            <div class="text-sm font-semibold text-gray-900">Next</div>
                            <p class="text-sm text-gray-500 mt-1">
                                Invoice numbering, PDF footer text, SMS sender ID, templates.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        @elseif($tab === 'garage')
            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm w-full">
                <div class="flex items-start justify-between gap-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Garage Profile</h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Update your garage details and payment instructions shown on invoices.
                        </p>
                    </div>

                    <div class="text-xs text-gray-500">
                        Garage Code:
                        <span class="font-semibold text-gray-900">{{ $garage->garage_code ?? '—' }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('settings.update') }}" class="mt-6 space-y-8">
                    @csrf

                    {{-- BASIC DETAILS --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="rounded-xl border border-gray-200 p-4">
                            <label class="text-xs text-gray-500">Garage Name</label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name', $garage->name ?? '') }}"
                                   required
                                   class="mt-2 w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>

                        <div class="rounded-xl border border-gray-200 p-4">
                            <label class="text-xs text-gray-500">Phone</label>
                            <input type="text"
                                   name="phone"
                                   value="{{ old('phone', $garage->phone ?? $user->phone ?? '') }}"
                                   class="mt-2 w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                   placeholder="07XXXXXXXX">
                        </div>

                        <div class="rounded-xl border border-gray-200 p-4">
                            <label class="text-xs text-gray-500">Email</label>
                            <input type="email"
                                   name="email"
                                   value="{{ old('email', $garage->email ?? $user->email ?? '') }}"
                                   class="mt-2 w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                   placeholder="garage@email.com">
                        </div>

                        <div class="rounded-xl border border-gray-200 p-4">
                            <label class="text-xs text-gray-500">Status</label>
                            @php $st = old('status', $garage->status ?? 'active'); @endphp
                            <select name="status"
                                    class="mt-2 w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="active" {{ $st === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $st === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="rounded-xl border border-gray-200 p-4 sm:col-span-2">
                            <label class="text-xs text-gray-500">Address</label>
                            <input type="text"
                                   name="address"
                                   value="{{ old('address', $garage->address ?? '') }}"
                                   class="mt-2 w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                   placeholder="Street / Building / Area">
                        </div>

                        <div class="rounded-xl border border-gray-200 p-4">
                            <label class="text-xs text-gray-500">City</label>
                            <input type="text"
                                   name="city"
                                   value="{{ old('city', $garage->city ?? '') }}"
                                   class="mt-2 w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                   placeholder="Nairobi">
                        </div>

                        <div class="rounded-xl border border-gray-200 p-4">
                            <label class="text-xs text-gray-500">Country</label>
                            <input type="text"
                                   name="country"
                                   value="{{ old('country', $garage->country ?? '') }}"
                                   class="mt-2 w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                   placeholder="Kenya">
                        </div>
                    </div>

                    {{-- PAYMENT DETAILS --}}
                    <div class="pt-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                                <x-lucide-wallet class="w-4 h-4 text-gray-700" />
                                Payment Details
                            </h3>
                            <div class="text-xs text-gray-500">Shown automatically on unpaid invoices</div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                            {{-- M-PESA --}}
                            <div class="rounded-2xl border border-gray-200 p-5">
                                <div class="flex items-center justify-between">
                                    <div class="font-semibold text-gray-900 flex items-center gap-2">
                                        <x-lucide-smartphone class="w-4 h-4 text-gray-700" />
                                        M-PESA
                                    </div>
                                    <div class="text-xs text-gray-500">Paybill / Till</div>
                                </div>

                                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-500">Type</label>
                                        <select name="payment_methods[mpesa][type]"
                                                class="mt-2 w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                            <option value="paybill" {{ $mpesaType === 'paybill' ? 'selected' : '' }}>Paybill</option>
                                            <option value="till" {{ $mpesaType === 'till' ? 'selected' : '' }}>Till</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500">Number</label>
                                        <input type="text"
                                               name="payment_methods[mpesa][number]"
                                               value="{{ $mpesaNumber }}"
                                               class="mt-2 w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                               placeholder="123456">
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label class="block text-xs text-gray-500">Account / Reference</label>
                                        <input type="text"
                                               name="payment_methods[mpesa][account]"
                                               value="{{ $mpesaAccount }}"
                                               class="mt-2 w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                               placeholder="INV-{invoice_number}">
                                        <p class="mt-2 text-xs text-gray-500">
                                            Tip: use <span class="font-mono">INV-&#123;invoice_number&#125;</span> so each invoice fills the reference automatically.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- BANK --}}
                            <div class="rounded-2xl border border-gray-200 p-5">
                                <div class="flex items-center justify-between">
                                    <div class="font-semibold text-gray-900 flex items-center gap-2">
                                        <x-lucide-landmark class="w-4 h-4 text-gray-700" />
                                        Bank Transfer
                                    </div>
                                    <div class="text-xs text-gray-500">Optional</div>
                                </div>

                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-xs text-gray-500">Bank Name</label>
                                        <input type="text"
                                               name="payment_methods[bank][bank_name]"
                                               value="{{ $bankName }}"
                                               class="mt-2 w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                               placeholder="KCB / Equity / Co-op...">
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500">Account Name</label>
                                        <input type="text"
                                               name="payment_methods[bank][account_name]"
                                               value="{{ $bankAccName }}"
                                               class="mt-2 w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                               placeholder="{{ $garage?->name ?? 'Account name' }}">
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-500">Account Number</label>
                                        <input type="text"
                                               name="payment_methods[bank][account_number]"
                                               value="{{ $bankAccNumber }}"
                                               class="mt-2 w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                               placeholder="XXXXXXXXXX">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SAVE --}}
                    <div class="pt-6 border-t border-gray-100 flex items-center justify-end gap-3">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                            <x-lucide-save class="w-4 h-4" />
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

        @else
            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm w-full">
                <h2 class="text-lg font-semibold text-gray-900">Billing</h2>
                <p class="text-sm text-gray-500 mt-1">Coming soon.</p>

                <div class="mt-5">
                    <a href="{{ route('billing.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                        <x-lucide-arrow-right class="w-4 h-4" />
                        Go to Billing
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
