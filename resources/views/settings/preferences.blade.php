<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <div class="font-semibold mb-1">Please fix the following:</div>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ============================================================
                 PREFERENCES FORM (GENERAL + DOCUMENTS + INSURANCE RULES)
                 ============================================================ --}}
            <form method="POST"
                  action="{{ route('settings.preferences.update') }}"
                  class="bg-white shadow-sm sm:rounded-2xl border border-gray-100 overflow-hidden">
                @csrf

                {{-- Header --}}
                <div class="px-6 py-5 border-b">
                    <div class="text-base font-semibold text-gray-900">Preferences</div>
                    <div class="mt-1 text-sm text-gray-500">
                        Currency, taxes, numbering, defaults and insurance workflow behavior.
                    </div>
                </div>

                {{-- =========================
                     General
                     ========================= --}}
                <div class="px-6 py-5 border-b">
                    <h3 class="text-sm font-semibold text-gray-900">General</h3>
                </div>

                <div class="px-6 py-6 grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Currency --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Currency
                        </label>
                        <select name="currency"
                                class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @php
                                $currencyVal = old('currency', $prefs->currency ?? 'KES');
                            @endphp
                            <option value="KES" @selected($currencyVal === 'KES')>KES – Kenyan Shilling</option>
                            <option value="USD" @selected($currencyVal === 'USD')>USD – US Dollar</option>
                            <option value="EUR" @selected($currencyVal === 'EUR')>EUR – Euro</option>
                        </select>
                    </div>

                    {{-- VAT --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Default VAT / Tax (%)
                        </label>
                        <input type="number" step="0.01" name="tax_rate"
                               value="{{ old('tax_rate', $prefs->tax_rate ?? 16) }}"
                               class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="16">
                        <p class="mt-1 text-xs text-gray-500">Used as default when creating invoices/estimates.</p>
                    </div>

                </div>

                {{-- =========================
                     Documents
                     ========================= --}}
                <div class="px-6 py-5 border-t border-b">
                    <h3 class="text-sm font-semibold text-gray-900">Documents</h3>
                </div>

                <div class="px-6 py-6 grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Invoice Numbering --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Invoice Numbering
                        </label>
                        @php
                            $invNumVal = old('invoice_numbering', $prefs->invoice_numbering ?? 'auto');
                        @endphp
                        <select name="invoice_numbering"
                                class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="auto" @selected($invNumVal === 'auto')>Automatic (INV-0001)</option>
                            <option value="yearly" @selected($invNumVal === 'yearly')>Yearly Reset (INV-2026-001)</option>
                        </select>
                    </div>

                    {{-- Date Format --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Date Format
                        </label>
                        @php
                            $dateFmtVal = old('date_format', $prefs->date_format ?? 'd/m/Y');
                        @endphp
                        <select name="date_format"
                                class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="d/m/Y" @selected($dateFmtVal === 'd/m/Y')>DD/MM/YYYY</option>
                            <option value="m/d/Y" @selected($dateFmtVal === 'm/d/Y')>MM/DD/YYYY</option>
                            <option value="Y-m-d" @selected($dateFmtVal === 'Y-m-d')>YYYY-MM-DD</option>
                        </select>
                    </div>

                </div>

                {{-- =========================
                     Insurance Rules (under Preferences)
                     ========================= --}}
                <div class="px-6 py-5 border-t border-b">
                    <div class="flex items-center gap-2">
                        <div class="text-sm font-semibold text-gray-900">Insurance & Claims</div>
                        <span class="text-[11px] px-2 py-1 rounded-full bg-indigo-50 text-indigo-700 font-medium">
                            Workflow
                        </span>
                    </div>
                    <div class="mt-1 text-sm text-gray-500">
                        Controls inspection → quotation → approval → repair flow enforcement.
                    </div>
                </div>

                <div class="px-6 py-6 grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Require Inspection --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Require Inspection Before Quotation
                        </label>
                        @php
                            $reqInspect = (int) old('insurance_require_inspection', $prefs->insurance_require_inspection ?? 1);
                        @endphp
                        <select name="insurance_require_inspection"
                                class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="1" @selected($reqInspect === 1)>Yes (Recommended)</option>
                            <option value="0" @selected($reqInspect === 0)>No</option>
                        </select>
                    </div>

                    {{-- Require Approval --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Require Insurer Approval
                        </label>
                        @php
                            $reqApproval = (int) old('insurance_require_approval', $prefs->insurance_require_approval ?? 1);
                        @endphp
                        <select name="insurance_require_approval"
                                class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="1" @selected($reqApproval === 1)>Yes</option>
                            <option value="0" @selected($reqApproval === 0)>No</option>
                        </select>
                    </div>

                    {{-- Default Payer --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Default Insurance Payer
                        </label>
                        @php
                            $payerDefault = old('insurance_default_payer', $prefs->insurance_default_payer ?? 'insurer');
                        @endphp
                        <select name="insurance_default_payer"
                                class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="insurer" @selected($payerDefault === 'insurer')>Insurer Pays</option>
                            <option value="customer" @selected($payerDefault === 'customer')>Customer Pays</option>
                            <option value="split" @selected($payerDefault === 'split')>Split Payment</option>
                        </select>
                    </div>

                    {{-- Lock Repair --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Lock Repair Before Approval
                        </label>
                        @php
                            $lockRepair = (int) old('insurance_lock_repair', $prefs->insurance_lock_repair ?? 1);
                        @endphp
                        <select name="insurance_lock_repair"
                                class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="1" @selected($lockRepair === 1)>Enabled</option>
                            <option value="0" @selected($lockRepair === 0)>Disabled</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Prevents moving to Repair stage until approved.</p>
                    </div>

                </div>

                {{-- Actions --}}
                <div class="px-6 py-4 bg-gray-50 flex items-center justify-end gap-3">
                    <a href="{{ route('settings.home') }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>

                    <button type="submit"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Save Preferences
                    </button>
                </div>
            </form>

            {{-- ============================================================
                 INSURERS (LIST + ADD)  — under Preferences
                 Separate form from Preferences for clarity + validation.
                 ============================================================ --}}
            <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100 overflow-hidden">

                <div class="px-6 py-5 border-b">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="text-sm font-semibold text-gray-900">Partner Insurers</div>
                            <div class="mt-1 text-sm text-gray-500">
                                Manage the insurers available when creating Insurance jobs.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-6 space-y-4">

                    {{-- List --}}
                    <div class="space-y-2">
                        @forelse($insurers ?? [] as $insurer)
                            <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white px-4 py-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-900 truncate">
                                        {{ $insurer->name }}
                                    </div>
                                    <div class="text-xs text-gray-500 truncate">
                                        {{ $insurer->email ?? 'No contact email' }}
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('settings.insurers.destroy', $insurer->id) }}"
                                      onsubmit="return confirm('Remove this insurer?');"
                                      class="shrink-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-xs font-semibold text-red-600 hover:underline">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-sm text-gray-600">
                                No insurers added yet.
                            </div>
                        @endforelse
                    </div>

                    {{-- Add insurer --}}
                    <form method="POST" action="{{ route('settings.insurers.store') }}" class="pt-4 border-t">
                        @csrf

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-1">
                                <label class="block text-sm font-medium text-gray-700">Insurer Name</label>
                                <input type="text"
                                       name="new_insurer_name"
                                       value="{{ old('new_insurer_name') }}"
                                       class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="APA Insurance" />
                            </div>

                            <div class="sm:col-span-1">
                                <label class="block text-sm font-medium text-gray-700">Email (optional)</label>
                                <input type="email"
                                       name="new_insurer_email"
                                       value="{{ old('new_insurer_email') }}"
                                       class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="claims@insurer.co.ke" />
                            </div>

                            <div class="sm:col-span-1 flex items-end justify-end">
                                <button type="submit"
                                        class="w-full sm:w-auto inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                    Add Insurer
                                </button>
                            </div>
                        </div>

                        <p class="mt-2 text-xs text-gray-500">
                            Tip: Keep insurer names consistent (e.g., “Jubilee Insurance”, “APA Insurance”).
                        </p>
                    </form>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
