{{-- resources/views/admin/garages/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">Garages</h2>
                <p class="mt-1 text-sm text-gray-500">Manage onboarding, status, trials, and activations.</p>
            </div>

            {{-- Modal trigger --}}
            <button type="button"
                    id="openGarageModalBtn"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white
                           rounded-lg hover:bg-indigo-700 transition">
                <x-lucide-notebook-pen class="w-4 h-4" />
                <span>Register Garage</span>
            </button>
        </div>
    </x-slot>

    <div class="space-y-4">
        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
            <form method="GET" action="{{ route('admin.garages.index') }}"
                  class="flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-between">
                <div class="flex gap-2">
                    <input name="search" value="{{ request('search') }}"
                           class="w-full sm:w-72 rounded-xl border-slate-200 text-sm"
                           placeholder="Search name, code, phone..." />
                    <select name="status" class="rounded-xl border-slate-200 text-sm">
                        <option value="">All statuses</option>
                        @foreach(['trial','active','expired','suspended'] as $st)
                            <option value="{{ $st }}" @selected(request('status')===$st)>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold hover:bg-slate-50">
                        Filter
                    </button>
                    <a href="{{ route('admin.garages.index') }}"
                       class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Garage</th>
                            <th class="text-left px-4 py-3">Code</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-left px-4 py-3">Trial ends</th>
                            <th class="text-left px-4 py-3">Sub expires</th>
                            <th class="text-right px-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($garages as $g)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $g->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $g->phone ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $g->garage_code }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full border px-2 py-0.5 text-xs font-semibold
                                        {{ $g->status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : '' }}
                                        {{ $g->status === 'trial' ? 'bg-blue-50 text-blue-700 border-blue-100' : '' }}
                                        {{ $g->status === 'expired' ? 'bg-amber-50 text-amber-800 border-amber-100' : '' }}
                                        {{ $g->status === 'suspended' ? 'bg-red-50 text-red-700 border-red-100' : '' }}
                                    ">
                                        {{ ucfirst($g->status ?? 'unknown') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    {{ $g->trial_ends_at ? \Illuminate\Support\Carbon::parse($g->trial_ends_at)->format('d M Y') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    {{ $g->subscription_expires_at ? \Illuminate\Support\Carbon::parse($g->subscription_expires_at)->format('d M Y') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-3 justify-end">
                                        <a href="{{ route('admin.garages.show', $g) }}"
                                           class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                                            Open →
                                        </a>

                                        <form method="POST" action="{{ route('admin.impersonation.start', $g) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">
                                                Impersonate
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                                    No garages found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4">
                {{ $garages->links() }}
            </div>
        </div>
    </div>

    {{-- =========================
         Register Garage Modal
         ========================= --}}
    @php
        $shouldOpenModal = session('open_create_modal') || $errors->any();
    @endphp


    <div id="garageModal"
        class="fixed inset-0 z-50 {{ $shouldOpenModal ? '' : 'hidden' }} flex items-center justify-center"
        aria-hidden="{{ $shouldOpenModal ? 'false' : 'true' }}">

        {{-- Backdrop --}}
        <div id="garageModalBackdrop" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        {{-- Modal panel --}}
        <div class="relative w-full max-w-3xl mx-auto px-4 py-10 flex min-h-screen items-center">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden max-h-[85vh] flex flex-col">
                {{-- Header --}}
                <div class="flex items-start justify-between gap-4 p-5 border-b border-slate-100">
                    <div>
                        <div class="text-base font-semibold text-slate-900">Register Garage</div>
                        <div class="mt-1 text-xs text-slate-500">Create a garage + owner who can log in immediately.</div>
                    </div>

                    <button type="button" id="closeGarageModalBtn"
                            class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold hover:bg-slate-50">
                        Close
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-5 overflow-y-auto">
                    @if($errors->any())
                        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-900 mb-5">
                            <div class="font-semibold mb-1">Fix the following:</div>
                            <ul class="list-disc ml-5 space-y-1">
                                @foreach($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.garages.store') }}" class="space-y-6">
                        @csrf

                        {{-- Garage details --}}
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Garage details</h3>

                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Garage name *</label>
                                    <input name="garage_name" value="{{ old('garage_name') }}"
                                           class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Garage phone</label>
                                    <input name="garage_phone" value="{{ old('garage_phone') }}"
                                           class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-slate-600">City / area</label>
                                    <input name="garage_city" value="{{ old('garage_city') }}"
                                           class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Trial days *</label>
                                    <input type="number" name="trial_days" value="{{ old('trial_days', 7) }}"
                                           class="mt-1 w-full rounded-xl border-slate-200 text-sm" min="1" max="60" required>
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="text-xs font-semibold text-slate-600">Address / landmark</label>
                                    <input name="garage_address" value="{{ old('garage_address') }}"
                                           class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                </div>

                                {{-- NEW: Garage Type --}}
                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Garage type *</label>
                                    <select id="garage_type" name="garage_type"
                                            class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                        <option value="standard" @selected(old('garage_type','standard') === 'standard')>
                                            Standard Garage
                                        </option>
                                        <option value="insurance" @selected(old('garage_type') === 'insurance')>
                                            Insurance Partner Garage
                                        </option>
                                    </select>
                                </div>

                                {{-- Insurance settings (conditional) --}}
                                <div class="sm:col-span-2" id="insurance_box" style="display:none;">
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                        <div class="text-sm font-semibold text-slate-900">Insurance settings</div>
                                        <div class="mt-1 text-xs text-slate-500">Only applies if this garage is an insurance partner.</div>

                                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                                <input type="checkbox" name="insurance_require_claim" value="1"
                                                       class="rounded border-slate-300"
                                                       @checked(old('insurance_require_claim', 1))>
                                                Require claim number
                                            </label>

                                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                                <input type="checkbox" name="insurance_require_assessor" value="1"
                                                       class="rounded border-slate-300"
                                                       @checked(old('insurance_require_assessor', 1))>
                                                Require assessor name
                                            </label>

                                            <div class="sm:col-span-2">
                                                <div class="text-xs font-semibold text-slate-600 mb-2">Default payer</div>
                                                <div class="flex flex-wrap gap-4 text-sm text-slate-700">
                                                    <label class="inline-flex items-center gap-2">
                                                        <input type="radio" name="insurance_default_payer" value="insurance"
                                                               @checked(old('insurance_default_payer','insurance') === 'insurance')>
                                                        Insurance
                                                    </label>
                                                    <label class="inline-flex items-center gap-2">
                                                        <input type="radio" name="insurance_default_payer" value="customer"
                                                               @checked(old('insurance_default_payer') === 'customer')>
                                                        Customer
                                                    </label>
                                                    <label class="inline-flex items-center gap-2">
                                                        <input type="radio" name="insurance_default_payer" value="mixed"
                                                               @checked(old('insurance_default_payer') === 'mixed')>
                                                        Mixed
                                                    </label>
                                                </div>
                                            </div>

                                            <label class="flex items-center gap-2 text-sm text-slate-700 sm:col-span-2">
                                                <input type="checkbox" name="insurance_enable_widgets" value="1"
                                                       class="rounded border-slate-300"
                                                       @checked(old('insurance_enable_widgets', 1))>
                                                Enable insurance dashboard widgets
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Owner login --}}
                        <div class="border-t pt-6">
                            <h3 class="text-sm font-semibold text-slate-900">Owner login</h3>
                            <p class="mt-1 text-xs text-slate-500">This owner can log in immediately using email + password.</p>

                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Owner name *</label>
                                    <input name="owner_name" value="{{ old('owner_name') }}"
                                           class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Owner email *</label>
                                    <input type="email" name="owner_email" value="{{ old('owner_email') }}"
                                           class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="text-xs font-semibold text-slate-600">Temporary password *</label>
                                    <input type="text" name="owner_password" value="{{ old('owner_password') }}"
                                           class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                    <p class="mt-1 text-xs text-slate-500">Share this with the owner. They can change later.</p>
                                </div>
                            </div>
                        </div>

                        {{-- Footer buttons --}}
                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" id="cancelGarageModalBtn"
                                    class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold hover:bg-slate-50">
                                Cancel
                            </button>

                            <button type="submit"
                                    class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                Create garage
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal + insurance toggle JS (no dependencies) --}}
    <script>
        (function () {
            const modal = document.getElementById('garageModal');
            const openBtn = document.getElementById('openGarageModalBtn');
            const closeBtn = document.getElementById('closeGarageModalBtn');
            const cancelBtn = document.getElementById('cancelGarageModalBtn');
            const backdrop = document.getElementById('garageModalBackdrop');

            function openModal() {
                if (!modal) return;
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.documentElement.classList.add('overflow-hidden');

            }

            function closeModal() {
                if (!modal) return;
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.documentElement.classList.remove('overflow-hidden');

            }

            if (openBtn) openBtn.addEventListener('click', openModal);
            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
            if (backdrop) backdrop.addEventListener('click', closeModal);

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeModal();
            });

            // Insurance settings toggle
            const typeEl = document.getElementById('garage_type');
            const box = document.getElementById('insurance_box');

            function syncInsuranceBox() {
                const isInsurance = (typeEl && typeEl.value === 'insurance');
                if (box) box.style.display = isInsurance ? '' : 'none';
            }

            if (typeEl) {
                typeEl.addEventListener('change', syncInsuranceBox);
                syncInsuranceBox();
            }

            // If Laravel returned validation errors, server already rendered modal open (not hidden).
            // Still ensure insurance UI is correct on load.
            syncInsuranceBox();
        })();
    </script>
</x-app-layout>
