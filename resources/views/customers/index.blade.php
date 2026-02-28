<x-app-layout>
    @php
        $totalCustomers = method_exists($customers, 'total') ? $customers->total() : count($customers);

        $initials = function ($name) {
            $name = trim((string) $name);
            if ($name === '') return '—';
            $parts = preg_split('/\s+/', $name);
            $first = mb_substr($parts[0] ?? '', 0, 1);
            $last  = mb_substr(end($parts) ?: '', 0, 1);
            return strtoupper($first . $last) ?: '—';
        };
    @endphp

    {{-- Toolbar --}}
    <div class="max-w-6xl mx-auto px-3 sm:px-4 lg:px-6">
        <div class="flex items-center gap-3">
            <form method="GET" class="flex-1">
                <div class="flex items-center h-11 rounded-full border border-gray-200 bg-white shadow-sm px-4">
                    <x-lucide-search class="w-4 h-4 text-gray-400 shrink-0" />
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search"
                        class="ml-3 w-full h-full bg-transparent border-0 p-0 text-sm text-gray-900
                               placeholder:text-gray-400 focus:ring-0 focus:outline-none"
                    >
                </div>
            </form>

            <button type="button"
                    class="h-11 w-11 inline-flex items-center justify-center rounded-2xl
                           border border-gray-200 bg-white shadow-sm
                           text-gray-700 hover:bg-gray-50 active:bg-gray-100"
                    aria-label="Filters">
                <x-lucide-sliders-horizontal class="w-5 h-5" />
            </button>

            {{-- ✅ Modal trigger --}}
            <button
                type="button"
                id="openCustomerCreate"
                class="h-11 inline-flex items-center justify-center gap-2
                       bg-emerald-600 px-6 text-sm font-semibold text-white shadow-sm
                       hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
            >
                <x-lucide-plus class="w-4 h-4" />
                <span>ADD CUSTOMER</span>
            </button>
        </div>
    </div>

    {{-- Content --}}
    <div class="max-w-6xl mx-auto px-3 sm:px-4 lg:px-6 mt-5 space-y-4">
        <div class="space-y-2">
            @forelse($customers as $customer)
                <div
                    onclick="window.location='{{ route('customers.show', $customer) }}'"
                    class="rounded-lg border border-gray-200 bg-white shadow-sm px-5 py-4
                           cursor-pointer hover:bg-gray-50 transition"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="rounded-full shrink-0 flex items-center justify-center
                                        border border-gray-200 bg-gray-50
                                        font-semibold uppercase leading-none text-emerald-700"
                                 style="width:3.2em;height:3.2em;font-size:1rem;">
                                {{ $initials($customer->name) }}
                            </div>

                            <div class="min-w-0">
                                <a href="{{ route('customers.show', $customer) }}"
                                   class="block text-sm font-semibold text-gray-900 truncate hover:underline">
                                    {{ $customer->name }}
                                </a>

                                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-600">
                                    @if($customer->phone)
                                        <span class="inline-flex items-center gap-1.5">
                                            <x-lucide-phone class="w-4 h-4 text-gray-400" />
                                            {{ $customer->phone }}
                                        </span>
                                    @endif

                                    @if($customer->email)
                                        <span class="inline-flex items-center gap-1.5 min-w-0">
                                            <x-lucide-mail class="w-4 h-4 text-gray-400 shrink-0" />
                                            <span class="truncate">{{ $customer->email }}</span>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0" onclick="event.stopPropagation();">
                            <div class="relative">
                                <button type="button"
                                        class="h-10 w-10 inline-flex items-center justify-center rounded-xl
                                               bg-gray-50 border border-gray-200 text-gray-700 hover:bg-gray-100"
                                        onclick="event.stopPropagation(); this.nextElementSibling.classList.toggle('hidden')">
                                    <x-lucide-more-vertical class="w-5 h-5" />
                                </button>

                                <div class="hidden absolute right-0 mt-2 w-44 rounded-xl border border-gray-200 bg-white shadow-lg z-20"
                                     onclick="event.stopPropagation();">
                                    <form action="{{ route('customers.destroy', $customer) }}" method="POST"
                                          onsubmit="return confirm('Delete this customer?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-xl">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm px-5 py-8 text-center">
                    <x-lucide-users class="mx-auto h-6 w-6 text-gray-400 mb-2" />
                    <div class="text-sm font-semibold text-gray-900">No customers found</div>
                    <div class="mt-1 text-sm text-gray-500">Try a different search or add your first customer.</div>
                </div>
            @endforelse
        </div>

        <div class="pt-1">
            {{ $customers->links() }}
        </div>
    </div>

    {{-- ✅ True overlay modal (printed at end of <body>) --}}
    @push('modals')
        @php $openCustomerModal = $errors->any(); @endphp

        <div id="customerCreateModal" class="{{ $openCustomerModal ? '' : 'hidden' }} fixed inset-0 z-[999999]">
            {{-- Premium backdrop --}}
            <div id="customerCreateBackdrop" class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px]"></div>

            <div class="absolute inset-0 flex items-center justify-center p-4">
                {{-- Slimmer modal --}}
                <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl border border-slate-100 overflow-hidden">

                    {{-- Premium Modal Header (avatar + title + subtitle) --}}
                    <div class="relative px-6 pt-6 pb-5 border-b border-slate-100">
                        {{-- Close Button --}}
                        <button
                            type="button"
                            id="closeCustomerCreateTop"
                            class="absolute right-5 top-5 rounded-xl p-2
                                   text-slate-500 hover:bg-slate-100
                                   focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                            aria-label="Close"
                        >
                            ✕
                        </button>

                        <div class="flex flex-col items-center text-center">
                            {{-- Premium round avatar --}}
                            <div class="mb-3">
                                <div class="h-18 w-18 rounded-full bg-gradient-to-b from-indigo-500/30 via-slate-200 to-transparent p-[2px]">
                                        <div class="h-10 w-10 rounded-full bg-slate-50 flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                      d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.118a7.5 7.5 0 0115 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.5-1.632z" />
                                            </svg>
                                        </div>
                                </div>
                            </div>

                            <div class="text-lg font-semibold tracking-tight text-slate-900">Create Customer</div>
                            <div class="mt-1 text-sm text-slate-500">Add a customer record for your garage.</div>
                        </div>
                    </div>

                    <div class="px-6 py-5 max-h-[80vh] overflow-auto">
                        <form method="POST" action="{{ route('customers.store') }}">
                            @csrf
                            @include('customers._form', [
                                'customer' => new \App\Models\Customer,
                                'buttonLabel' => 'Create Customer',
                            ])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal    = document.getElementById('customerCreateModal');
                const openBtn  = document.getElementById('openCustomerCreate');
                const backdrop = document.getElementById('customerCreateBackdrop');
                const closeTop = document.getElementById('closeCustomerCreateTop');
                const closeBottom = document.getElementById('closeCustomerCreateBottom'); // from _form

                if (!modal || !openBtn) return;

                function openModal() {
                    modal.classList.remove('hidden');
                    document.documentElement.classList.add('overflow-hidden');
                    document.body.classList.add('overflow-hidden');
                }

                function closeModal() {
                    modal.classList.add('hidden');
                    document.documentElement.classList.remove('overflow-hidden');
                    document.body.classList.remove('overflow-hidden');
                }

                openBtn.addEventListener('click', function (e) { e.preventDefault(); openModal(); });
                backdrop && backdrop.addEventListener('click', closeModal);
                closeTop && closeTop.addEventListener('click', closeModal);
                closeBottom && closeBottom.addEventListener('click', function(e){ e.preventDefault(); closeModal(); });

                window.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
                });

                // Phone: combine country prefix with local number into hidden `phone` (if the new split inputs exist)
                const country = document.getElementById('phone_country');
                const local   = document.getElementById('phone_local');
                const full    = document.getElementById('phone_full');

                function syncPhone() {
                    if (!country || !local || !full) return;
                    const c = (country.value || '').trim();
                    const l = (local.value || '').replace(/\s+/g,'').trim();
                    full.value = l ? `${c}${l}` : '';
                }

                country && country.addEventListener('change', syncPhone);
                local && local.addEventListener('input', syncPhone);
                syncPhone();

                @if ($errors->any())
                    openModal();
                @endif
            });
        </script>
    @endpush
</x-app-layout>
